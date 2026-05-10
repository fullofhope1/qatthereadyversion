<?php
// includes/classes/ProviderService.php

class ProviderService extends BaseService
{
    private $repository;

    public function __construct(ProviderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listProviders($userId)
    {
        return $this->repository->getAllByUserId($userId);
    }

    public function addProvider($name, $phone, $userId)
    {
        if (empty($name)) {
            throw new Exception("اسم الراعي مطلوب (Provider name is required)");
        }
        
        // Optional phone validation
        if (!empty($phone)) {
            if (!preg_match('/^\d{7,15}$/', $phone)) {
                throw new Exception("رقم الهاتف يجب أن يحتوي على أرقام فقط (Phone must contain only digits)");
            }
            if ($this->repository->getByPhone($phone)) {
                throw new Exception("رقم الهاتف هذا موجود مسبقاً (This phone number already exists)");
            }
        }

        if ($this->repository->getByName($name)) {
            throw new Exception("هذا الاسم موجود مسبقاً (This name already exists)");
        }

        return $this->repository->create($name, $phone, $userId);
    }

    public function updateProvider($id, $name, $phone)
    {
        $existing = $this->repository->getById($id);
        if (!$existing) {
            throw new Exception("الراعي غير موجود (Provider not found)");
        }

        if ($existing['name'] !== $name && $this->repository->getByName($name)) {
            throw new Exception("الاسم الجديد موجود مسبقاً (New name already exists)");
        }

        if (!empty($phone) && $existing['phone'] !== $phone) {
            if (!preg_match('/^\d{7,15}$/', $phone)) {
                throw new Exception("رقم الهاتف يجب أن يحتوي على أرقام فقط (Phone must contain only digits)");
            }
            if ($this->repository->getByPhone($phone)) {
                throw new Exception("رقم الهاتف الجديد موجود مسبقاً (New phone number already exists)");
            }
        }

        return $this->repository->update($id, $name, $phone);
    }

    public function removeProvider($id)
    {
        if ($this->repository->countPurchasesByProviderId($id) > 0) {
            throw new Exception("لا يمكن حذف الراعي لوجود شحنات مسجلة باسمه. يمكنك تعديل الاسم بدلاً من الحذف. (Cannot delete provider with existing purchases)");
        }
        return $this->repository->delete($id);
    }
}
