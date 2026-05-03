<?php
// includes/classes/PurchaseRepository.php

class PurchaseRepository extends BaseRepository
{

    public function getById($id, $lock = false)
    {
        $sql = "SELECT * FROM purchases WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        return $this->fetchOne($sql, [$id]);
    }

    public function create(array $data)
    {
        $defaults = [
            'purchase_date' => date('Y-m-d'),
            'provider_id' => null,
            'qat_type_id' => null,
            'source_weight_grams' => 0,
            'quantity_kg' => 0,
            'price_per_kilo' => 0,
            'agreed_price' => 0,
            'unit_type' => 'weight',
            'source_units' => 0,
            'price_per_unit' => 0,
            'received_units' => 0,
            'is_received' => 0,
            'status' => 'Fresh',
            'media_path' => null,
            'created_by' => null
        ];
        $data = array_merge($defaults, $data);

        $sql = "INSERT INTO purchases (
            purchase_date, provider_id, qat_type_id, source_weight_grams, 
            quantity_kg, price_per_kilo, agreed_price, unit_type, source_units, 
            price_per_unit, received_units, is_received, status, media_path, created_by
        ) VALUES (
            :purchase_date, :provider_id, :qat_type_id, :source_weight_grams, 
            :quantity_kg, :price_per_kilo, :agreed_price, :unit_type, :source_units, 
            :price_per_unit, :received_units, :is_received, :status, :media_path, :created_by
        )";

        $allowed = array_keys($defaults);
        $filtered = array_intersect_key($data, array_flip($allowed));

        $this->execute($sql, $filtered);
        return (int)$this->getLastInsertId();
    }

    public function update($id, array $data)
    {
        // Auto-recalculate agreed_price and net_cost if price or quantity changes
        if (isset($data['price_per_kilo']) || isset($data['quantity_kg']) || isset($data['price_per_unit']) || isset($data['received_units']) || isset($data['unit_type'])) {
            $p = $this->getById($id);
            if ($p) {
                $unitType = $data['unit_type'] ?? $p['unit_type'];
                if ($unitType === 'weight') {
                    $kg = $data['quantity_kg'] ?? $p['quantity_kg'];
                    $priceKg = $data['price_per_kilo'] ?? $p['price_per_kilo'];
                    $data['agreed_price'] = (float)$kg * (float)$priceKg;
                } else {
                    $units = $data['received_units'] ?? $p['received_units'];
                    $priceUnit = $data['price_per_unit'] ?? $p['price_per_unit'];
                    $data['agreed_price'] = (int)$units * (float)$priceUnit;
                }
                $data['net_cost'] = $data['agreed_price'];
            }
        }

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE purchases SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->execute($sql, $data);
    }

    public function getPendingShipments()
    {
        $sql = "SELECT p.*, t.name as type_name, prov.name as provider_name 
                FROM purchases p 
                LEFT JOIN qat_types t ON p.qat_type_id = t.id 
                LEFT JOIN providers prov ON p.provider_id = prov.id 
                WHERE p.is_received = 0 
                ORDER BY p.created_at ASC";
        return $this->fetchAll($sql);
    }

    public function getTodayShipmentsByUserId($date, $userId)
    {
        $sql = "SELECT p.*, t.name as type_name, prov.name as provider_name 
                FROM purchases p 
                LEFT JOIN qat_types t ON p.qat_type_id = t.id 
                LEFT JOIN providers prov ON p.provider_id = prov.id 
                WHERE p.purchase_date = ? AND p.created_by = ?
                ORDER BY p.created_at DESC";
        return $this->fetchAll($sql, [$date, $userId]);
    }

    public function getTodayReceived($date)
    {
        $sql = "SELECT p.*, t.name as type_name, prov.name as provider_name 
                FROM purchases p 
                LEFT JOIN qat_types t ON p.qat_type_id = t.id 
                LEFT JOIN providers prov ON p.provider_id = prov.id 
                WHERE p.is_received = 1 AND DATE(p.received_at) = ? 
                ORDER BY p.received_at DESC";
        return $this->fetchAll($sql, [$date]);
    }

    public function getFreshStockByDate($date)
    {
        $sql = "SELECT p.*, prov.name as provider_name 
                FROM purchases p 
                JOIN providers prov ON p.provider_id = prov.id 
                WHERE p.purchase_date = ? 
                AND p.status IN ('Fresh', 'Momsi')
                AND p.is_received = 1";
        return $this->fetchAll($sql, [$date]);
    }

    public function getStockQuantity($id, $lock = false)
    {
        $sql = "SELECT quantity_kg FROM purchases WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        return $this->fetchColumn($sql, [$id]) ?: 0;
    }

    public function getStockUnits($id, $lock = false)
    {
        $sql = "SELECT received_units FROM purchases WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        return (int)$this->fetchColumn($sql, [$id]) ?: 0;
    }

    public function recordReceptionLoss($purchaseId, $typeId, $weight, $date)
    {
        $sql = "INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, decision_date, sale_date) 
                VALUES (?, ?, ?, ?, 'Reception_Loss', ?, ?)";
        return $this->execute($sql, [$date, $purchaseId, $typeId, $weight, $date, $date]);
    }

    public function applyDiscount($id, $amount)
    {
        $sql = "UPDATE purchases SET discount_amount = discount_amount + ? WHERE id = ?";
        return $this->execute($sql, [$amount, $id]);
    }

    public function restoreInventory($id, $kg, $units)
    {
        // LOGIC REMOVED: We no longer modify the original purchase quantity.
        // Inventory availability is now calculated dynamically in SaleRepository.
        return true; 
    }
}
