<?php
// includes/classes/CustomerService.php

class CustomerService extends BaseService
{
    private $repository;

    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listCustomers()
    {
        return $this->repository->getAllActive();
    }

    public function getCustomer($id)
    {
        return $this->repository->getById($id);
    }

    public function addCustomer($name, $phone, $debtLimit = null)
    {
        $name = trim($name);
        $phone = trim($phone);

        // Validation: Fields required
        if (empty($name)) {
            throw new Exception("الاسم مطلوب (Name is required)");
        }
        if (empty($phone)) {
            throw new Exception("رقم الهاتف مطلوب (Phone is required)");
        }

        // Validation: Phone format (7xxxxxxxxx)
        if (!preg_match('/^\d{7,15}$/', $phone)) {
            throw new Exception("رقم الهاتف غير صحيح - يجب أن يكون أرقاماً فقط (Invalid phone format)");
        }

        // Validation: Name must be unique
        if ($this->repository->getByName($name)) {
            throw new Exception("الاسم موجود مسبقاً (This name already exists)");
        }

        // Validation: Phone must be unique
        if ($this->repository->getByPhone($phone)) {
            throw new Exception("رقم الهاتف موجود مسبقاً (This phone number already exists)");
        }

        return $this->repository->create($name, $phone, $debtLimit);
    }

    public function updateCustomer($id, $name, $phone, $debtLimit)
    {
        $existing = $this->repository->getById($id);
        if (!$existing) {
            throw new Exception("المستخدم غير موجود (Customer not found)");
        }

        // Check name uniqueness if changed
        if ($existing['name'] !== $name) {
            if ($this->repository->getByName($name)) {
                throw new Exception("الاسم الجديد موجود مسبقاً (New name already exists)");
            }
        }

        // Validation: Phone required
        if (empty($phone)) {
            throw new Exception("رقم الهاتف مطلوب (Phone is required)");
        }

        // Check phone uniqueness if changed
        if ($existing['phone'] !== $phone) {
            if ($this->repository->getByPhone($phone)) {
                throw new Exception("رقم الهاتف الجديد موجود مسبقاً (New phone number already exists)");
            }
        }

        return $this->repository->update($id, $name, $phone, $debtLimit);
    }

    public function removeCustomer($id)
    {
        return $this->repository->delete($id);
    }
}
