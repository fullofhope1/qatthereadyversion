<?php
// includes/classes/ExpenseService.php

class ExpenseService extends BaseService
{
    private $expenseRepo;
    private $depositRepo;
    private $staffRepo;

    public function __construct(ExpenseRepository $expenseRepo, DepositRepository $depositRepo, StaffRepository $staffRepo)
    {
        $this->expenseRepo = $expenseRepo;
        $this->depositRepo = $depositRepo;
        $this->staffRepo = $staffRepo;
    }

    public function addExpense(array $data)
    {
        // Validation: Check Staff Limit (Strict Prevention)
        if ($data['category'] === 'Staff' && !empty($data['staff_id'])) {
            $staff = $this->staffRepo->getById($data['staff_id']);
            if ($staff) {
                // Use withdrawal_limit if set, otherwise fallback to daily_salary
                $limit = ($staff['withdrawal_limit'] !== null) ? (float)$staff['withdrawal_limit'] : (float)$staff['daily_salary'];
                
                if ($limit > 0) {
                    $used = (float)$this->expenseRepo->getTotalStaffWithdrawals($data['staff_id']);
                    $rem = $limit - $used;
                    if ($data['amount'] > $rem + 0.1) {
                        throw new Exception("تجاوز السقف المسموح للعامل (" . $staff['name'] . ")! الراتب/السقف اليومي: " . number_format($limit) . " - المسحوب مسبقاً: " . number_format($used) . " - المتبقي المتاح: " . number_format($rem));
                    }
                }
            }
        }
        return $this->expenseRepo->create($data);
    }

    public function updateExpense($id, array $data)
    {
        // Validation logic can be added here if needed for updates too
        return $this->expenseRepo->update($id, $data);
    }

    public function deleteExpense($id)
    {
        return $this->expenseRepo->delete($id);
    }

    public function addDeposit(array $data)
    {
        return $this->depositRepo->create($data);
    }

    public function updateDeposit($id, array $data)
    {
        return $this->depositRepo->update($id, $data);
    }

    public function deleteDeposit($id)
    {
        return $this->depositRepo->delete($id);
    }
}
