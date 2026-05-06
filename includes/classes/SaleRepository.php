<?php
// includes/classes/SaleRepository.php

class SaleRepository extends BaseRepository
{
    public function create(array $data)
    {
        $defaults = [
            'sale_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d'),
            'customer_id' => null,
            'qat_type_id' => null,
            'purchase_id' => null,
            'leftover_id' => null,
            'qat_status' => 'Tari',
            'weight_grams' => 0,
            'unit_type' => 'weight',
            'quantity_units' => 0,
            'price' => 0,
            'paid_amount' => 0,
            'refund_amount' => 0,
            'payment_method' => 'Cash',
            'is_paid' => 1,
            'transfer_sender' => null,
            'transfer_receiver' => null,
            'transfer_number' => null,
            'transfer_company' => null,
            'debt_type' => null,
            'notes' => ''
        ];
        $data = array_merge($defaults, $data);

        $sql = "INSERT INTO sales (
            sale_date, due_date, customer_id, qat_type_id, purchase_id, leftover_id, 
            qat_status, weight_grams, unit_type, quantity_units, price, 
            paid_amount, refund_amount, payment_method, is_paid, 
            transfer_sender, transfer_receiver, transfer_number, transfer_company, 
            debt_type, notes
        ) VALUES (
            :sale_date, :due_date, :customer_id, :qat_type_id, :purchase_id, :leftover_id, 
            :qat_status, :weight_grams, :unit_type, :quantity_units, :price, 
            :paid_amount, :refund_amount, :payment_method, :is_paid, 
            :transfer_sender, :transfer_receiver, :transfer_number, :transfer_company, 
            :debt_type, :notes
        )";

        // Only bind keys that exist in the SQL to avoid HY093
        $allowed = array_keys($defaults);
        $filtered = array_intersect_key($data, array_flip($allowed));

        $this->execute($sql, $filtered);
        return (int)$this->getLastInsertId();
    }

    public function getSoldKgByPurchaseId($purchaseId)
    {
        $sold = $this->fetchColumn("SELECT SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)) FROM sales WHERE purchase_id = ? AND is_returned = 0", [$purchaseId]) ?: 0;
        $leftovers = $this->fetchColumn("SELECT SUM(weight_kg) FROM leftovers WHERE purchase_id = ? AND status != 'Reception_Loss'", [$purchaseId]) ?: 0;
        return (float)$sold + (float)$leftovers;
    }

    public function getSoldUnitsByPurchaseId($purchaseId)
    {
        $sold = $this->fetchColumn("SELECT SUM(quantity_units - COALESCE(returned_units, 0)) FROM sales WHERE purchase_id = ? AND is_returned = 0", [$purchaseId]) ?: 0;
        $leftovers = $this->fetchColumn("SELECT SUM(quantity_units) FROM leftovers WHERE purchase_id = ? AND status != 'Reception_Loss'", [$purchaseId]) ?: 0;
        return (int)$sold + (int)$leftovers;
    }

    public function getSoldKgByLeftoverId($leftoverId)
    {
        return $this->fetchColumn("SELECT SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)) FROM sales WHERE leftover_id = ? AND is_returned = 0", [$leftoverId]) ?: 0;
    }

    public function getSoldUnitsByLeftoverId($leftoverId)
    {
        return $this->fetchColumn("SELECT SUM(quantity_units - COALESCE(returned_units, 0)) FROM sales WHERE leftover_id = ? AND is_returned = 0", [$leftoverId]) ?: 0;
    }

    public function getById($id)
    {
        return $this->fetchOne("SELECT * FROM sales WHERE id = ?", [$id]);
    }

    public function updateRefundAmount($id, $amount)
    {
        return $this->execute("UPDATE sales SET refund_amount = refund_amount + ? WHERE id = ?", [$amount, $id]);
    }

    public function updateRefundAmountAndQuantity($id, $amount, $weight, $units)
    {
        return $this->execute(
            "UPDATE sales 
             SET refund_amount = refund_amount + ?,
                 returned_kg = returned_kg + ?,
                 returned_units = returned_units + ?
             WHERE id = ?", 
            [$amount, $weight, $units, $id]
        );
    }

    public function markAsReturned($id)
    {
        return $this->execute("UPDATE sales SET is_returned = 1 WHERE id = ?", [$id]);
    }

    public function logReturn($data)
    {
        $sql = "INSERT INTO refunds (customer_id, sale_id, refund_type, amount, reason, weight_kg, quantity_units, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        return $this->execute($sql, [
            $data['customer_id'], $data['sale_id'], $data['refund_type'], 
            $data['amount'], $data['reason'], $data['weight_kg'], $data['quantity_units']
        ]);
    }

    public function getSalesMap($date = null)
    {
        $sql = "SELECT p.id as purchase_id, 
                       (COALESCE(s.sold_kg, 0) + COALESCE(l.leftover_kg, 0)) as sold_kg, 
                       (COALESCE(s.sold_units, 0) + COALESCE(l.leftover_units, 0)) as sold_units 
                FROM purchases p
                LEFT JOIN (
                    SELECT purchase_id, 
                           SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)) as sold_kg, 
                           SUM(quantity_units - COALESCE(returned_units, 0)) as sold_units 
                    FROM sales 
                    WHERE is_returned = 0 AND purchase_id IS NOT NULL 
                    GROUP BY purchase_id
                ) s ON p.id = s.purchase_id
                LEFT JOIN (
                    SELECT purchase_id, SUM(weight_kg) as leftover_kg, SUM(quantity_units) as leftover_units
                    FROM leftovers 
                    WHERE status != 'Reception_Loss'
                    GROUP BY purchase_id
                ) l ON p.id = l.purchase_id
                WHERE (s.purchase_id IS NOT NULL OR l.purchase_id IS NOT NULL)";
        
        $params = [];
        if ($date) {
            $sql .= " AND p.purchase_date = ?";
            $params[] = $date;
        }
        
        $sql .= " GROUP BY p.id";
        
        return $this->fetchAll($sql, $params);
    }
}
