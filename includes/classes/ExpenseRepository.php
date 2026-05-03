<?php
// includes/classes/ExpenseRepository.php

class ExpenseRepository extends BaseRepository
{

    public function create(array $data)
    {
        $sql = "INSERT INTO expenses (expense_date, description, amount, payment_method, category, staff_id, provider_id, created_by) 
                VALUES (:expense_date, :description, :amount, :payment_method, :category, :staff_id, :provider_id, :created_by)";
        return $this->execute($sql, $data);
    }

    public function update($id, array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE expenses SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->execute($sql, $data);
    }

    public function delete($id)
    {
        return $this->execute("DELETE FROM expenses WHERE id = ?", [$id]);
    }


    public function getTodayExpenses($date, $userId, $role = 'super_admin')
    {
        // Team isolation: Filter by the role of the creator to group Super Admin with their sub-roles
        $sql = "SELECT e.*, s.name as staff_name, p.name as provider_name
                FROM expenses e 
                LEFT JOIN staff s ON e.staff_id = s.id 
                LEFT JOIN providers p ON e.provider_id = p.id
                JOIN users u ON e.created_by = u.id
                WHERE e.expense_date = ? AND u.role = ? 
                ORDER BY e.id DESC";
        return $this->fetchAll($sql, [$date, $role]);
    }

    public function getTotalStaffWithdrawals($staffId)
    {
        return $this->fetchColumn("SELECT SUM(amount) FROM expenses WHERE staff_id = ? AND category = 'Staff' AND expense_date = CURRENT_DATE", [$staffId]) ?: 0;
    }
}
