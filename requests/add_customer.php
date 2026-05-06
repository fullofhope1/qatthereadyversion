<?php
// requests/add_customer.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $debt_limit = (isset($_POST['debt_limit']) && $_POST['debt_limit'] !== '') ? $_POST['debt_limit'] : null;

    try {
        $repo = new CustomerRepository($pdo);
        $service = new CustomerService($repo);

        $service->addCustomer($name, $phone, $debt_limit);
        header("Location: ../customers.php");
        exit;
    } catch (Exception $e) {
        header("Location: ../customers.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}
