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
                WHERE p.is_received = 1 AND p.purchase_date = ? 
                ORDER BY p.received_at DESC";
        return $this->fetchAll($sql, [$date]);
    }

    public function getFreshStockByDate($date)
    {
        $sql = "SELECT p.*, COALESCE(prov.name, 'بدون مورد') as provider_name 
                FROM purchases p 
                LEFT JOIN providers prov ON p.provider_id = prov.id 
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

    public function getRemainingStock($purchaseId)
    {
        $stmt = $this->pdo->prepare("SELECT 
            p.unit_type, p.quantity_kg, p.received_units,
            (SELECT COALESCE(SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)), 0) FROM sales WHERE purchase_id = ? AND is_returned = 0) as sold_kg,
            (SELECT COALESCE(SUM(weight_kg), 0) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Auto_Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2', 'Closed')) as managed_kg,
            (SELECT COALESCE(SUM(quantity_units - COALESCE(returned_units, 0)), 0) FROM sales WHERE purchase_id = ? AND is_returned = 0) as sold_units,
            (SELECT COALESCE(SUM(quantity_units), 0) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Auto_Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2', 'Closed')) as managed_units
            FROM purchases p WHERE p.id = ?");
        $stmt->execute([$purchaseId, $purchaseId, $purchaseId, $purchaseId, $purchaseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return ['kg' => 0, 'units' => 0];

        return [
            'unit_type' => $row['unit_type'],
            'kg' => max(0, (float)$row['quantity_kg'] - (float)$row['sold_kg'] - (float)$row['managed_kg']),
            'units' => max(0, (int)$row['received_units'] - (int)$row['sold_units'] - (int)$row['managed_units'])
        ];
    }
}
