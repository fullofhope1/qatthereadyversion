<?php
// includes/classes/DebtService.php

class DebtService extends BaseService
{
    protected $debtRepo;

    public function __construct(DebtRepository $debtRepo)
    {
        $this->debtRepo = $debtRepo;
    }

    public function getDebtsData($type)
    {
        $debtors = $this->debtRepo->getDebtorsByType($type);
        $total = array_sum(array_column($debtors, 'due_amount'));

        return [
            'debtors' => $debtors,
            'total' => $total
        ];
    }

    public function rollover($customerId, $debtType)
    {
        return $this->debtRepo->rolloverDebt($customerId, $debtType);
    }

    public function reconcile()
    {
        return $this->debtRepo->reconcileDebts();
    }

    public function rolloverSale($saleId)
    {
        return $this->debtRepo->rolloverSale($saleId);
    }

    public function recordPayment($customerId, $amount, $note, $method = 'Cash', $transferData = [])
    {
        try {
            $this->debtRepo->beginTransaction();

            // 1. Insert Payment Record
            $this->debtRepo->insertPayment($customerId, $amount, $note, $method, null, $transferData);

            // 2. Distribute payment: First reduce Opening Balance, then oldest sales
            $remaining = $amount;
            
            // A. Check Opening Balance
            $cust = $this->debtRepo->getOpeningDebtInfo($customerId);
            $currentOpeningDebt = (float)($cust['opening_balance'] ?? 0) - (float)($cust['paid_opening_balance'] ?? 0);
            
            if ($currentOpeningDebt > 0 && $remaining > 0) {
                $payToOpening = min($remaining, $currentOpeningDebt);
                $this->debtRepo->updateOpeningBalancePayment($customerId, $payToOpening);
                $remaining -= $payToOpening;
            }

            // B. Distribute remaining to sales
            if ($remaining > 0) {
                $unpaidSales = $this->debtRepo->getUnpaidSales($customerId);
                foreach ($unpaidSales as $sale) {
                    if ($remaining <= 0) break;

                    $netPrice = $sale['price'] - $sale['refund_amount'];
                    $needed = $netPrice - $sale['paid_amount'];

                    if ($needed <= 0) continue;

                    if ($remaining >= $needed) {
                        $this->debtRepo->updateSalePayment($sale['id'], $netPrice, true);
                        $remaining -= $needed;
                    } else {
                        $this->debtRepo->updateSalePayment($sale['id'], $sale['paid_amount'] + $remaining, false);
                        $remaining = 0;
                    }
                }
            }

            // 3. Update total_debt cache on customer
            $this->debtRepo->updateCustomerDebt($customerId, $amount);

            $this->debtRepo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->debtRepo->inTransaction()) {
                $this->debtRepo->rollBack();
            }
            throw $e;
        }
    }
}
