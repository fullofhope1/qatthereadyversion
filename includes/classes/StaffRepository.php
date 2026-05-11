<?php
// includes/classes/StaffRepository.php

class StaffRepository extends BaseRepository
{

    public function create(array $data)
    {
        $sql = "INSERT INTO staff (name, role, daily_salary, withdrawal_limit, created_by) 
                VALUES (:name, :role, :daily_salary, :withdrawal_limit, :created_by)";
        return $this->execute($sql, $data);
    }

    public function getById($id)
    {
        return $this->fetchOne("SELECT * FROM staff WHERE id = ?", [$id]);
    }

    public function getWithCurrentWithdrawals($userId, $role = null, $subRole = null, $showInactive = false)
    {
        $statusFilter = $showInactive ? "" : " AND s.is_active = 1";
        $current_role = $role ?: 'super_admin';

        // Join with users table to filter by the role group (Merchant team vs Supplier team)
        $params = [];
        if ($current_role === 'super_admin') {
            $roleFilter = " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $roleFilter = " AND u.role = ?";
            $params[] = $current_role;
        }

        $sql = "SELECT s.*,
                (SELECT SUM(amount) FROM expenses WHERE staff_id = s.id AND category = 'Staff' AND expense_date = CURRENT_DATE) as current_withdrawals
                FROM staff s
                JOIN users u ON s.created_by = u.id
                WHERE 1=1 $roleFilter $statusFilter
                ORDER BY s.is_active DESC, s.name ASC";
        return $this->fetchAll($sql, $params);
    }

    public function update($id, array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE staff SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->execute($sql, $data);
    }


    public function getMonthlyWithdrawals($staffId, $month)
    {
        $sql = "SELECT * FROM expenses 
                WHERE staff_id = ? 
                AND category = 'Staff'
                AND DATE_FORMAT(expense_date, '%Y-%m') = ?
                ORDER BY expense_date DESC";
        return $this->fetchAll($sql, [$staffId, $month]);
    }

    public function getTotalWithdrawalsForAll($userId)
    {
        return $this->fetchColumn("SELECT SUM(amount) FROM expenses WHERE category = 'Staff' AND created_by = ?", [$userId]) ?: 0;
    }

    public function delete($id)
    {
        return $this->execute("DELETE FROM staff WHERE id = ?", [$id]);
    }

    public function deactivate($id)
    {
        return $this->execute("UPDATE staff SET is_active = 0 WHERE id = ?", [$id]);
    }

    public function activate($id)
    {
        return $this->execute("UPDATE staff SET is_active = 1 WHERE id = ?", [$id]);
    }
}
