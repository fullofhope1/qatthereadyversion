<?php
// requests/delete_staff.php
require '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        if (!$id) {
            throw new Exception("رقم الموظف غير صالح");
        }
        
        $repo = new StaffRepository($pdo);
        
        // Ensure they belong to the current user (admin data isolation)
        $staff = $repo->getById($id);
        if (!$staff) {
            throw new Exception("الموظف غير موجود");
        }
        
        if ($_SESSION['role'] !== 'super_admin' && $staff['created_by'] != $_SESSION['user_id']) {
            throw new Exception("غير مصرح لك بحذف هذا الموظف");
        }
        
        // Optional: Check if staff has active expenses
        // If staff has rows in expenses, typically we shouldn't delete them, but since user requested delete capability, 
        // we'll try to delete. If foreign key constraints fail, it will throw an exception.
        $repo->delete($id);
        
        $returnUrl = $_POST['return_url'] ?? "../staff.php";
        $returnUrl .= (strpos($returnUrl, '?') !== false ? "&" : "?") . "success=1";
        header("Location: $returnUrl");
    } catch (Exception $e) {
        $error = urlencode("لا يمكن حذف الموظف لوجود حركات مالية مرتبطة به، أو حدث خطأ: " . $e->getMessage());
        $returnUrl = $_POST['return_url'] ?? "../staff.php";
        $returnUrl .= (strpos($returnUrl, '?') !== false ? "&" : "?") . "error=$error";
        header("Location: $returnUrl");
    }
}
