<?php
// includes/classes/LeftoverRepository.php

class LeftoverRepository extends BaseRepository
{

    public function getById($id, $lock = false)
    {
        $sql = "SELECT * FROM leftovers WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        return $this->fetchOne($sql, [$id]);
    }

    public function getWeight($id, $lock = false)
    {
        $sql = "SELECT weight_kg FROM leftovers WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        return (float)$this->fetchColumn($sql, [$id]) ?: 0.0;
    }

    public function getUnits($id, $lock = false)
    {
        $sql = "SELECT quantity_units FROM leftovers WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        return (int)$this->fetchColumn($sql, [$id]) ?: 0;
    }

    public function getTransferredLeftovers()
    {
        $sql = "SELECT l.*, t.name as type_name, prov.name as provider_name 
                FROM leftovers l 
                JOIN qat_types t ON l.qat_type_id = t.id 
                LEFT JOIN purchases p ON l.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                WHERE l.status IN ('Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2')
                ORDER BY l.source_date DESC, l.id DESC";
        return $this->fetchAll($sql);
    }

    public function getMomsiStock()
    {
        $sql = "SELECT p.*, t.name as type_name, prov.name as provider_name 
                FROM purchases p 
                JOIN qat_types t ON p.qat_type_id = t.id 
                LEFT JOIN providers prov ON p.provider_id = prov.id
                WHERE p.status = 'Momsi'
                ORDER BY p.purchase_date DESC";
        return $this->fetchAll($sql);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, unit_type, quantity_units, status, decision_date, sale_date, notes) 
                VALUES (:source_date, :purchase_id, :qat_type_id, :weight_kg, :unit_type, :quantity_units, :status, :decision_date, :sale_date, :notes)";
        return $this->execute($sql, $data);
    }

    public function restoreInventory($id, $kg, $units)
    {
        // LOGIC REMOVED: We no longer modify the original leftover quantity.
        // Inventory availability is now calculated dynamically.
        return true; 
    }

    /**
     * FIX #16: Check if an active leftover already exists for this purchase today.
     * Prevents creating duplicate leftover records from the same source.
     */
    public function getActiveTodayByPurchaseId($purchaseId)
    {
        $sql = "SELECT id FROM leftovers 
                WHERE purchase_id = ? 
                  AND source_date = CURDATE()
                  AND status IN ('Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2')
                LIMIT 1";
        return $this->fetchOne($sql, [$purchaseId]);
    }
}
