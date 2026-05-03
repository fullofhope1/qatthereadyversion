<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $expenseRepo = new ExpenseRepository($pdo);
        $depositRepo = new DepositRepository($pdo);
        $staffRepo = new StaffRepository($pdo);
        $service = new ExpenseService($expenseRepo, $depositRepo, $staffRepo);

        $id = (int)$_POST['id'];
        $data = [
            'category' => $_POST['category'],
            'description' => $_POST['description'] ?? '',
            'amount' => (float)$_POST['amount'],
            'payment_method' => $_POST['payment_method'] ?? 'Cash',
            'staff_id' => ($_POST['category'] === 'Staff') && !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null,
            'provider_id' => ($_POST['category'] === 'تسديد مورد') && !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : null
        ];

        $service->updateExpense($id, $data);
        header("Location: ../expenses.php?success=1");
    } catch (Exception $e) {
        header("Location: ../expenses.php?error=" . urlencode($e->getMessage()));
    }
}
