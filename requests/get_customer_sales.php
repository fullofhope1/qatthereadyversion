<?php
// requests/get_customer_sales.php
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
    $sales = $refundRepo->getCustomerSalesForReturn($customerId);
    $customerRepo = new CustomerRepository($pdo);
    $totalDebt = $customerRepo->getDebtBalance($customerId);
    echo json_encode(['success' => true, 'sales' => $sales, 'customer_total_debt' => $totalDebt]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
