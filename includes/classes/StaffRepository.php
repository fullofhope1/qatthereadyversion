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

    public function getWithCurrentWithdrawals($userId, $role = null, $subRole = null)
    {
        // super_admin sees ALL staff
        if ($role === 'super_admin') {
            $sql = "SELECT s.*,
                    (SELECT SUM(amount) FROM expenses WHERE staff_id = s.id AND category = 'Staff') as current_withdrawals
                    FROM staff s
                    ORDER BY s.name ASC";
            return $this->fetchAll($sql);
        }

        $sql = "SELECT s.*,
                (SELECT SUM(amount) FROM expenses WHERE staff_id = s.id AND category = 'Staff') as current_withdrawals
                FROM staff s
                WHERE s.created_by = ?
                ORDER BY s.name ASC";
        return $this->fetchAll($sql, [$userId]);
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
}
