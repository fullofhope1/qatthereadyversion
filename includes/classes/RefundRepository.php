<?php
// includes/classes/RefundRepository.php

class RefundRepository extends BaseRepository
{
    public function getRecentRefunds($limit = 50)
    {
        $limit = (int)$limit;
        $sql = "SELECT r.*, c.name as cust_name
                FROM refunds r
                LEFT JOIN customers c ON r.customer_id = c.id
                ORDER BY r.id DESC LIMIT $limit";
        return $this->fetchAll($sql);
    }

    public function create($data)
    {
        $sql = "INSERT INTO refunds (customer_id, amount, refund_type, unit_type, reason, weight_kg, quantity_units, sale_id, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $this->execute($sql, [
            $data['customer_id'],
            $data['amount'],
            $data['refund_type'],
            $data['unit_type'] ?? 'weight',
            $data['reason'],
            $data['weight_kg'] ?? 0,
            $data['quantity_units'] ?? 0,
            $data['sale_id'] ?? null,
            $data['created_by'] ?? null
        ]);
        return (int)$this->getLastInsertId();
    }

    public function getRefundsByPeriod($where, $params)
    {
        $sql = "SELECT r.*, c.name as cust_name FROM refunds r LEFT JOIN customers c ON r.customer_id = c.id $where ORDER BY r.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getCustomerSalesForReturn($customerId)
    {
        // RETURNS = same-day only. Only show today's sales that still have returnable qty
        $sql = "SELECT s.id, s.sale_date, s.price, s.paid_amount, s.refund_amount, 
                       (s.price - s.paid_amount - s.refund_amount) as remaining_debt, 
                       s.weight_grams, (s.weight_grams / 1000) as weight_kg, 
                       COALESCE(s.returned_kg, 0) as returned_kg,
                       s.quantity_units, 
                       COALESCE(s.returned_units, 0) as returned_units,
                       s.unit_type, s.payment_method, s.is_paid, 
                       t.name as type_name,
                       -- Net remaining returnable weight (kg)
                       GREATEST(0, (s.weight_grams / 1000) - COALESCE(s.returned_kg, 0)) as remaining_returnable_kg,
                       -- Net remaining returnable units
                       GREATEST(0, s.quantity_units - COALESCE(s.returned_units, 0)) as remaining_returnable_units
                FROM sales s
                LEFT JOIN qat_types t ON s.qat_type_id = t.id
                WHERE s.customer_id = ? 
                AND s.is_returned = 0
                AND DATE(s.sale_date) = CURDATE()
                ORDER BY s.id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    /**
     * Get all of a customer's sales (up to 30 days) for financial compensation.
     * Unlike returns (same-day only), compensations can be applied to older sales.
     */
    public function getUnpaidSalesWithBalance($customerId)
    {
        $sql = "SELECT s.id, s.sale_date, s.price, s.paid_amount, s.refund_amount, 
                       (s.price - s.paid_amount - s.refund_amount) as remaining_debt, 
                       s.weight_grams, (s.weight_grams / 1000) as weight_kg, 
                       COALESCE(s.returned_kg, 0) as returned_kg,
                       s.quantity_units, 
                       COALESCE(s.returned_units, 0) as returned_units,
                       s.unit_type, s.payment_method, s.is_paid, 
                       t.name as type_name,
                       GREATEST(0, (s.weight_grams / 1000) - COALESCE(s.returned_kg, 0)) as remaining_returnable_kg,
                       GREATEST(0, s.quantity_units - COALESCE(s.returned_units, 0)) as remaining_returnable_units
                FROM sales s
                LEFT JOIN qat_types t ON s.qat_type_id = t.id
                WHERE s.customer_id = ? 
                AND s.is_returned = 0
                AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY s.sale_date DESC, s.id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function applyRefundToSale($saleId, $amount)
    {
        $sql = "UPDATE sales SET refund_amount = refund_amount + ? WHERE id = ?";
        return $this->execute($sql, [$amount, $saleId]);
    }
}
