<?php
// requests/update_staff.php
require '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $repo = new StaffRepository($pdo);
        $service = new StaffService($repo);

        $id = $_POST['id'];
        $data = [
            'daily_salary' => $_POST['daily_salary'],
            'withdrawal_limit' => !empty($_POST['withdrawal_limit']) ? $_POST['withdrawal_limit'] : null
        ];
        if (isset($_POST['name'])) $data['name'] = $_POST['name'];
        if (isset($_POST['role'])) $data['role'] = $_POST['role'];
        if (isset($_POST['phone'])) $data['phone'] = $_POST['phone'];


        $service->updateStaff($id, $data);
        $returnUrl = $_POST['return_url'] ?? "../staff_details.php?id=$id";
        $returnUrl .= (strpos($returnUrl, '?') !== false ? "&" : "?") . "success=1";
        header("Location: $returnUrl");
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        $returnUrl = $_POST['return_url'] ?? "../staff_details.php?id=$id";
        $returnUrl .= (strpos($returnUrl, '?') !== false ? "&" : "?") . "error=$error";
        header("Location: $returnUrl");
    }
}
