<?php
require '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $opening_balance = (float)($_POST['opening_balance'] ?? 0);

    try {
        $repo = new CustomerRepository($pdo);
        $service = new CustomerService($repo);

        $id = $service->addCustomer($name, $phone, null, $opening_balance);

        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
