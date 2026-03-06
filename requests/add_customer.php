<?php
// requests/add_customer.php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $debt_limit = (isset($_POST['debt_limit']) && $_POST['debt_limit'] !== '') ? $_POST['debt_limit'] : null;

    // Validate phone is required and numeric
    if (empty($phone)) {
        header("Location: ../customers.php?error=" . urlencode("رقم الجوال مطلوب"));
        exit;
    }
    if (!preg_match('/^\d{7,15}$/', $phone)) {
        header("Location: ../customers.php?error=" . urlencode("رقم الجوال يجب أن يحتوي على أرقام فقط (7-15 رقم)"));
        exit;
    }

    // Check for duplicate name or phone
    $check = $pdo->prepare("SELECT id FROM customers WHERE (name = ? OR phone = ?) AND is_deleted = 0");
    $check->execute([$name, $phone]);
    if ($check->fetch()) {
        header("Location: ../customers.php?error=" . urlencode("اسم العميل أو رقم الجوال مستخدم بالفعل"));
        exit;
    }

    $sql = "INSERT INTO customers (name, phone, debt_limit) VALUES (?, ?, ?)";
    $pdo->prepare($sql)->execute([$name, $phone, $debt_limit]);

    header("Location: ../customers.php");
}
