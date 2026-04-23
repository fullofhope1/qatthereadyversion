<?php
// includes/classes/ProductRepository.php

class ProductRepository extends BaseRepository
{

    public function getByName($name)
    {
        return $this->fetchOne("SELECT * FROM qat_types WHERE name = ? LIMIT 1", [$name]);
    }

    public function getById($id)
    {
        return $this->fetchOne("SELECT * FROM qat_types WHERE id = ?", [$id]);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO qat_types (name, description, unit_type, media_path) VALUES (:name, :description, :unit_type, :media_path)";
        $data['unit_type'] = $data['unit_type'] ?? 'weight';
        $data['description'] = $data['description'] ?? '';
        $data['media_path'] = $data['media_path'] ?? null;
        $this->execute($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($id, array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE qat_types SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        $this->execute($sql, $data);
        return (int)$this->getLastInsertId();
    }

    public function getAllActive()
    {
        return $this->fetchAll("SELECT * FROM qat_types WHERE is_deleted = 0");
    }

    public function getActiveProductsWithStats()
    {
        $sql = "SELECT qt.*, 
                SUM(p.received_weight_grams) as total_received_grams,
                MAX(p.created_at) as last_shipment_date,
                MAX(p.purchase_date) as active_date
                FROM qat_types qt
                JOIN purchases p ON qt.id = p.qat_type_id
                WHERE qt.is_deleted = 0
                  AND p.status IN ('Fresh', 'Momsi')
                GROUP BY qt.id
                ORDER BY qt.name ASC";
        return $this->fetchAll($sql);
    }

    public function delete($id)
    {
        return $this->execute("UPDATE qat_types SET is_deleted = 1 WHERE id = ?", [$id]);
    }

    public function getInventoryDailyDebts()
    {
        $sql = "SELECT sale_date, COUNT(*) as count, SUM(price) as total 
                FROM sales 
                WHERE is_paid = 0 AND payment_method = 'Debt' AND debt_type = 'Daily' 
                GROUP BY sale_date ORDER BY sale_date ASC";
        return $this->fetchAll($sql);
    }

    public function getInventoryPurchaseStats()
    {
        $sql = "SELECT purchase_date, COUNT(*) as count, status 
                FROM purchases 
                WHERE status IN ('Fresh', 'Momsi') 
                GROUP BY purchase_date, status ORDER BY purchase_date ASC";
        return $this->fetchAll($sql);
    }
}
