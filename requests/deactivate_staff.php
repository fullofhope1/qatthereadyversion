<?php
// requests/deactivate_staff.php
require '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            throw new Exception("رقم الموظف غير صالح");
        }
        
        $repo = new StaffRepository($pdo);
        $service = new StaffService($repo);
        
        // Data isolation check: Ensure staff belongs to current user
        $staff = $repo->getById($id);
        if (!$staff) {
            throw new Exception("الموظف غير موجود");
        }
        
        // Super Admin is isolated too, but can reactivate any staff if we allow it. 
        // Based on user feedback "super admin is just a name", we stick to creator isolation.
        if ($_SESSION['role'] !== 'super_admin' && $staff['created_by'] != $_SESSION['user_id']) {
            throw new Exception("غير مصرح لك بتعديل حالة هذا الموظف");
        }
        
        if (isset($_POST['activate']) && $_POST['activate'] == 1) {
            $repo->activate($id);
            $msg = "تم إعادة تنشيط الموظف بنجاح";
        } else {
            $service->deactivateStaff($id);
            $msg = "تم إلغاء تنشيط الموظف بنجاح";
        }
        
        $showInactive = (isset($_POST['activate']) && $_POST['activate'] == 1) ? 0 : 1;
        header("Location: ../staff.php?success=1&msg=" . urlencode($msg) . "&show_inactive=" . $showInactive);
    } catch (Exception $e) {
        header("Location: ../staff.php?error=" . urlencode($e->getMessage()));
    }
}
