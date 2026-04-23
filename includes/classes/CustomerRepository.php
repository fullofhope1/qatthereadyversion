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

    public function create($name, $phone, $debtLimit = null)
    {
        $sql = "INSERT INTO customers (name, phone, debt_limit) VALUES (?, ?, ?)";
        $this->execute($sql, [$name, $phone, $debtLimit]);
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
        return $this->execute("UPDATE customers SET total_debt = total_debt + ? WHERE id = ?", [$amount, $id]);
    }

    public function decrementDebt($id, $amount)
    {
        // FIX #7: Use GREATEST(0,...) to prevent total_debt from going negative
        return $this->execute("UPDATE customers SET total_debt = GREATEST(0, total_debt - ?) WHERE id = ?", [$amount, $id]);
    }

    public function getDebtBalance($id)
    {
        return (float)$this->fetchColumn("SELECT total_debt FROM customers WHERE id = ?", [$id]);
    }
}
