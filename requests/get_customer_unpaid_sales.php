<?php
// requests/get_customer_unpaid_sales.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';

header('Content-Type: application/json');

if (!isset($_GET['customer_id'])) {
    echo json_encode(['error' => 'Missing customer_id']);
    exit;
}

$customerId = (int)$_GET['customer_id'];

try {
    $refundRepo = new RefundRepository($pdo);
    $sales = $refundRepo->getUnpaidSalesWithBalance($customerId);
    echo json_encode(['success' => true, 'sales' => $sales]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
