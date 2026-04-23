<?php
// includes/classes/LeftoverService.php

class LeftoverService extends BaseService
{
    private $leftoverRepo;
    private $purchaseRepo;

    public function __construct(LeftoverRepository $leftoverRepo, PurchaseRepository $purchaseRepo)
    {
        $this->leftoverRepo = $leftoverRepo;
        $this->purchaseRepo = $purchaseRepo;
    }

    public function processDecision($id, $sourceType, $amount, $action, $notes = '')
    {
        $this->purchaseRepo->beginTransaction();
        try {
            $unitType  = 'weight';
            $qatTypeId = 0;
            $purchaseId = null;

            if ($sourceType === 'Fresh') {
                $purchase = $this->purchaseRepo->getById($id, true);
                if (!$purchase) {
                    throw new Exception("الشحنة غير موجودة");
                }
                $unitType   = $purchase['unit_type'];
                $qatTypeId  = $purchase['qat_type_id'];
                $purchaseId = $id;
            } else {
                $leftover = $this->leftoverRepo->getById($id, true);
                if (!$leftover) {
                    throw new Exception("البقايا غير موجودة");
                }
                $unitType   = $leftover['unit_type'];
                $qatTypeId  = $leftover['qat_type_id'];
                $purchaseId = $leftover['purchase_id'];
            }

            // FIX #16: Prevent duplicate active leftover for the same purchase source
            // Check if there's already an active leftover for this purchase today
            if ($purchaseId && $action !== 'Drop') {
                $existing = $this->leftoverRepo->getActiveTodayByPurchaseId($purchaseId);
                if ($existing) {
                    throw new Exception(
                        "يوجد سجل بقايا نشط بالفعل لهذه الشحنة (ID: {$existing['id']}). " .
                        "لا يمكن إنشاء بقايا مكررة من نفس المصدر."
                    );
                }
            }

            $weightKg      = ($unitType === 'weight') ? $amount : 0;
            $quantityUnits = ($unitType === 'units') ? $amount : 0;

            if ($action === 'Drop') {
                $status   = 'Dropped';
                $saleDate = date('Y-m-d');
            } else {
                $status   = ($sourceType === 'Momsi') ? 'Momsi_Day_2' : 'Transferred_Next_Day';
                $saleDate = date('Y-m-d', strtotime('+1 day'));
            }

            $this->leftoverRepo->create([
                'source_date'   => date('Y-m-d'),
                'purchase_id'   => $purchaseId,
                'qat_type_id'   => $qatTypeId,
                'weight_kg'     => $weightKg,
                'quantity_units'=> $quantityUnits,
                'unit_type'     => $unitType,
                'status'        => $status,
                'decision_date' => date('Y-m-d'),
                'sale_date'     => $saleDate,
                'notes'         => $notes
            ]);

            $this->purchaseRepo->commit();
            return true;
        } catch (Exception $e) {
            $this->purchaseRepo->rollBack();
            throw $e;
        }
    }
}
