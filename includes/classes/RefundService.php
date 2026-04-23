<?php

require_once 'BaseRepository.php';

class RefundService
{
    private $refundRepo;
    private $customerRepo;
    private $saleRepo;
    private $purchaseRepo;
    private $leftoverRepo;

    public function __construct(
        $refundRepo,
        $customerRepo,
        $saleRepo,
        $purchaseRepo = null,
        $leftoverRepo = null
    ) {
        $this->refundRepo = $refundRepo;
        $this->customerRepo = $customerRepo;
        $this->saleRepo = $saleRepo;
        $this->purchaseRepo = $purchaseRepo;
        $this->leftoverRepo = $leftoverRepo;
    }

    public function getRefundDashboardData()
    {
        return [
            'customers' => $this->customerRepo->getAllActive(),
            'recent_refunds' => $this->refundRepo->getRecentRefunds(10)
        ];
    }

    public function processRefund($data, $userId = null)
    {
        $this->refundRepo->beginTransaction();
        try {
            $saleId = !empty($data['sale_id']) ? $data['sale_id'] : null;
            $amount = (float)$data['amount'];
            $refundType = $data['refund_type'] ?? 'Debt';
            $unitType = $data['unit_type'] ?? 'weight';

            // 1. Validation Logic
            if ($saleId) {
                $sale = $this->saleRepo->getById($saleId);
                if (!$sale) {
                    throw new Exception("Sale record not found for ID: " . var_export($saleId, true));
                }    
                // Inherit unit_type from sale if not explicitly provided
                if (empty($data['unit_type']) && isset($sale['unit_type'])) {
                    $unitType = $sale['unit_type'];
                }

                // A. Amount check
                $maxPossible = (float)$sale['price'] - (float)$sale['refund_amount'];
                if ($amount > $maxPossible + 0.01) {
                    throw new Exception("مبلغ المرتجع ($amount) أكبر من القيمة المتبقية للفاتورة (" . number_format($maxPossible) . ").");
                }

                // B. Same-day restriction for PHYSICAL RETURNS (inventory adjustments)
                // Compensations (financial-only) are allowed any day
                $isPhysicalReturn = !empty($data['weight_kg']) || !empty($data['quantity_units']);
                if ($isPhysicalReturn) {
                    $saleDate = date('Y-m-d', strtotime($sale['sale_date']));
                    $today = date('Y-m-d');
                    if ($saleDate !== $today) {
                        throw new Exception(
                            "المرتجعات العينية تُقبل في نفس يوم البيع فقط. " .
                            "تاريخ البيع: {$saleDate} — اليوم: {$today}. " .
                            "للتعويض عن يوم سابق، استخدم التعويض المالي بدون كميات."
                        );
                    }

                    // C. Inventory check: qty cannot exceed REMAINING returnable quantity
                    // remaining = sold - already_returned (accumulative check)
                    $weight = (float)($data['weight_kg'] ?? 0);
                    $units = (int)($data['quantity_units'] ?? 0);
                    if ($weight <= 0 && $units <= 0) {
                        throw new Exception("يرجى تحديد الكمية المرتجعة.");
                    }

                    $soldWeight = (float)$sale['weight_kg'];
                    $alreadyReturnedKg = (float)($sale['returned_kg'] ?? 0);
                    $remainingReturnableKg = max(0, $soldWeight - $alreadyReturnedKg);

                    $soldUnits = (int)$sale['quantity_units'];
                    $alreadyReturnedUnits = (int)($sale['returned_units'] ?? 0);
                    $remainingReturnableUnits = max(0, $soldUnits - $alreadyReturnedUnits);

                    if ($weight > 0 && $weight > $remainingReturnableKg + 0.001) {
                        throw new Exception(
                            "الوزن المرتجع ({$weight} كجم) أكبر من الكمية القابلة للإرجاع المتبقية (" .
                            number_format($remainingReturnableKg, 3) . " كجم). " .
                            "المباع: {$soldWeight} كجم — تم إرجاع: {$alreadyReturnedKg} كجم مسبقاً."
                        );
                    }
                    if ($units > 0 && $units > $remainingReturnableUnits) {
                        throw new Exception(
                            "الكمية المرتجعة ({$units}) أكبر من الكمية القابلة للإرجاع المتبقية ({$remainingReturnableUnits}). " .
                            "المباع: {$soldUnits} — تم إرجاع: {$alreadyReturnedUnits} مسبقاً."
                        );
                    }
                }

                // C. Debt check
                if ($refundType === 'Debt') {
                    // 1. Check specific sale debt
                    $remainingSaleDebt = (float)$sale['price'] - (float)$sale['paid_amount'] - (float)$sale['refund_amount'];
                    if ($amount > $remainingSaleDebt + 0.01) {
                        throw new Exception("المبلغ المرتجع من الدين ($amount) أكبر من الدين المتبقي للفاتورة (" . number_format($remainingSaleDebt) . ").");
                    }
                    
                    // 2. Check customer total debt
                    $customerDebt = $this->customerRepo->getDebtBalance($data['customer_id']);
                    if ($amount > $customerDebt + 0.01) {
                        throw new Exception("المبلغ المرتجع ($amount) أكبر من إجمالي دين العميل (" . number_format($customerDebt) . ").");
                    }
                }
            }

            // 2. Create refund record
            $data['created_by'] = $userId;
            $data['unit_type'] = $unitType;
            $this->refundRepo->create($data);

            // 3. Financial Adjustments
            if ($refundType === 'Debt') {
                $customerId = $data['customer_id'];
                // A. Direct customer balance update
                $this->customerRepo->decrementDebt($customerId, $amount);
            }

            // B. Apply refund and return quantities to the specific sale record for ALL refund types (Cash/Debt)
            // This ensures Net Sales is universally accurate and handles the new returned_kg/units fields.
            if (!empty($data['sale_id'])) {
                $weight = (float)($data['weight_kg'] ?? 0);
                $units = (int)($data['quantity_units'] ?? 0);
                $this->saleRepo->updateRefundAmountAndQuantity($data['sale_id'], $amount, $weight, $units);
            }

            // 4. Inventory Adjustments
            if (!empty($data['sale_id'])) {
                $sale = $this->saleRepo->getById($data['sale_id']);
                if ($sale) {
                    $weight = (float)($data['weight_kg'] ?? 0);
                    $units = (int)($data['quantity_units'] ?? 0);

                    if ($weight > 0 || $units > 0) {
                        if ($sale['purchase_id'] && $this->purchaseRepo) {
                            // Restore to purchases
                            $this->purchaseRepo->restoreInventory($sale['purchase_id'], $weight, $units);
                        } elseif ($sale['leftover_id'] && $this->leftoverRepo) {
                            // Restore to leftovers
                            $this->leftoverRepo->restoreInventory($sale['leftover_id'], $weight, $units);
                        }
                    }
                }
            }

            $this->refundRepo->commit();
            return true;
        } catch (Exception $e) {
            $this->refundRepo->rollBack();
            throw $e;
        }
    }
}
