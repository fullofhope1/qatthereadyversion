<?php
// includes/classes/CustomerRepository.php

class CustomerRepository extends BaseRepository
{

    public function getAllActive()
    {
        return $this->fetchAll("SELECT * FROM customers WHERE is_deleted = 0 ORDER BY name ASC");
    }

    public function getById($id)
    {
        return $this->fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
    }

    public function getByName($name)
    {
        return $this->fetchOne("SELECT id FROM customers WHERE name = ? AND is_deleted = 0", [$name]);
    }

    public function getByPhone($phone)
    {
        if (empty($phone)) return null;
        return $this->fetchOne("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0", [$phone]);
    }

    public function create($name, $phone = null, $debtLimit = null, $openingBalance = 0)
    {
        $sql = "INSERT INTO customers (name, phone, debt_limit, opening_balance, total_debt) VALUES (?, ?, ?, ?, ?)";
        $this->execute($sql, [$name, $phone ?: null, $debtLimit, $openingBalance, $openingBalance]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name, $phone, $debtLimit)
    {
        $sql = "UPDATE customers SET name = ?, phone = ?, debt_limit = ? WHERE id = ?";
        return $this->execute($sql, [$name, $phone, $debtLimit, $id]);
    }

    public function delete($id)
    {
        return $this->execute("UPDATE customers SET is_deleted = 1 WHERE id = ?", [$id]);
    }

    public function incrementDebt($id, $amount)
    {
        // ✅ FIX #4: Recalculate from sales table to keep total_debt accurate
        return $this->execute(
            "UPDATE customers SET total_debt = (COALESCE(opening_balance, 0) - COALESCE(paid_opening_balance, 0)) + (
                SELECT COALESCE(SUM(price - paid_amount - COALESCE(refund_amount,0)), 0)
                FROM sales WHERE customer_id = ? AND is_paid = 0 AND is_returned = 0
            ) WHERE id = ?",
            [$id, $id]
        );
    }

    public function decrementDebt($id, $amount)
    {
        // ✅ FIX #4: Recalculate from sales table to keep total_debt accurate
        return $this->execute(
            "UPDATE customers SET total_debt = (COALESCE(opening_balance, 0) - COALESCE(paid_opening_balance, 0)) + (
                SELECT COALESCE(SUM(price - paid_amount - COALESCE(refund_amount,0)), 0)
                FROM sales WHERE customer_id = ? AND is_paid = 0 AND is_returned = 0
            ) WHERE id = ?",
            [$id, $id]
        );
    }

    public function getDebtBalance($id)
    {
        return (float)$this->fetchColumn("SELECT total_debt FROM customers WHERE id = ?", [$id]);
    }
}
