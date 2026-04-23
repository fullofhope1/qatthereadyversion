<?php
// includes/classes/StaffService.php

class StaffService extends BaseService
{
    private $staffRepo;

    public function __construct(StaffRepository $staffRepo)
    {
        $this->staffRepo = $staffRepo;
    }

    public function addStaff(array $data)
    {
        return $this->staffRepo->create($data);
    }

    public function getStaffList($userId, $role = null, $subRole = null, $showInactive = false)
    {
        return $this->staffRepo->getWithCurrentWithdrawals($userId, $role, $subRole, $showInactive);
    }

    public function updateStaff($id, array $data)
    {
        return $this->staffRepo->update($id, $data);
    }

    public function deactivateStaff($id)
    {
        return $this->staffRepo->deactivate($id);
    }

    public function getStaffWithdrawals($staffId, $month)
    {
        return $this->staffRepo->getMonthlyWithdrawals($staffId, $month);
    }

    public function getById($id)
    {
        return $this->staffRepo->getById($id);
    }

    public function getTotalWithdrawals($userId)
    {
        return $this->staffRepo->getTotalWithdrawalsForAll($userId);
    }
}
