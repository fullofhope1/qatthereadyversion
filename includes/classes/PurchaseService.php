<?php
// includes/classes/PurchaseService.php

class PurchaseService extends BaseService
{
    private $purchaseRepo;
    private $productRepo;

    public function __construct(PurchaseRepository $purchaseRepo, ProductRepository $productRepo)
    {
        $this->purchaseRepo = $purchaseRepo;
        $this->productRepo = $productRepo;
    }

    public function sourceShipment(array $data)
    {
        // Automatic Linking: Find or Create the Type
        $type = $this->productRepo->getByName($data['type_name']);
        if ($type) {
            $data['qat_type_id'] = $type['id'];
        } else {
            $data['qat_type_id'] = $this->productRepo->create([
                'name' => $data['type_name'],
                'description' => 'Auto-created from sourcing',
                'media_path' => $data['media_path'] ?? null
            ]);
        }

        // Triple-Stream Logic (Weight vs Count)
        $data['unit_type'] = $data['unit_type'] ?? 'weight';

        if ($data['unit_type'] === 'weight') {
            $weightKg = (float)($data['source_weight_grams'] ?? 0) / 1000;
            $data['agreed_price'] = (float)$weightKg * (float)($data['price_per_kilo'] ?? 0);
            $data['source_units'] = 0;
            $data['price_per_unit'] = 0;
        } else {
            // Qabdah / Qartas
            $data['source_units'] = (int)($data['source_units'] ?? 0);
            $data['price_per_unit'] = (float)($data['price_per_unit'] ?? 0);
            $data['agreed_price'] = (float)$data['source_units'] * $data['price_per_unit'];

            $data['source_weight_grams'] = 0;
            $data['price_per_kilo'] = 0;
        }

        $data['quantity_kg'] = 0; // Not received yet
        $data['received_units'] = 0; // Not received yet
        $data['is_received'] = 0;
        $data['status'] = 'Fresh';

        // Clean up data for repo
        unset($data['type_name']);

        return $this->purchaseRepo->create($data);
    }

    public function receiveShipment($id, $receivedWeightGrams, $receivedUnits = 0)
    {
        $quantityKg = (float)$receivedWeightGrams / 1000;
        $receivedUnits = (int)$receivedUnits;

        $this->purchaseRepo->beginTransaction();
        try {
            $purchase = $this->purchaseRepo->getById($id, true);
            if (!$purchase) {
                throw new Exception("الشحنة غير موجودة");
            }

            // Logic: Recalculate Agreed Price based on actual received amount
            $newAgreedPrice = $purchase['agreed_price'];
            if ($purchase['unit_type'] === 'weight') {
                if ($purchase['price_per_kilo'] > 0) {
                    $newAgreedPrice = (float)$quantityKg * (float)$purchase['price_per_kilo'];
                }
            } else {
                if ($purchase['price_per_unit'] > 0) {
                    $newAgreedPrice = (int)$receivedUnits * (float)$purchase['price_per_unit'];
                }
            }

            $this->purchaseRepo->update($id, [
                'received_weight_grams' => $receivedWeightGrams,
                'quantity_kg' => $quantityKg,
                'received_units' => $receivedUnits,
                'agreed_price' => $newAgreedPrice,
                'is_received' => 1,
                'received_at' => date('Y-m-d H:i:s'),
                'purchase_date' => getOperationalDate()
            ]);

            // Record loss if weight-based and significant
            if ($purchase['unit_type'] === 'weight') {
                $sourceKg = (int)($purchase['source_weight_grams'] ?? 0) / 1000;
                $lossKg = $sourceKg - $quantityKg;
                if ($lossKg > 0.001) {
                    $this->purchaseRepo->recordReceptionLoss($id, $purchase['qat_type_id'], $lossKg, date('Y-m-d'));
                }
            }

            // Sync media
            if ($purchase['media_path']) {
                $this->productRepo->update($purchase['qat_type_id'], [
                    'media_path' => $purchase['media_path']
                ]);
            }

            $this->purchaseRepo->commit();
        } catch (Exception $e) {
            $this->purchaseRepo->rollBack();
            throw $e;
        }
    }

    public function getPending()
    {
        return $this->purchaseRepo->getPendingShipments();
    }

    public function addPurchase(array $data)
    {
        $data['is_received'] = 1;
        if (($data['unit_type'] ?? 'weight') === 'weight') {
            $data['source_weight_grams'] = $data['source_weight_grams'] ?? ($data['quantity_kg'] * 1000);
        } else {
            $data['received_units'] = $data['received_units'] ?? $data['source_units'];
        }
        return $this->purchaseRepo->create($data);
    }

    public function getReceivedToday($date)
    {
        return $this->purchaseRepo->getTodayReceived($date);
    }

    public function applyDiscount($id, $amount)
    {
        return $this->purchaseRepo->applyDiscount($id, $amount);
    }
}
