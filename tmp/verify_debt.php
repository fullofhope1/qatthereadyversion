<?php
require 'c:/xampp/htdocs/qat/config/db.php';
require 'c:/xampp/htdocs/qat/includes/Autoloader.php';

$refundRepo = new RefundRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);

$service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo);

// 1. Create a customer with 0 debt
$pdo->prepare("INSERT INTO customers (name, total_debt) VALUES ('Test Zero Debt', 0)")->execute();
$custId = $pdo->lastInsertId();

// 2. Create a dummy sale for this customer
$pdo->prepare("INSERT INTO sales (customer_id, price, paid_amount, sale_date, payment_method, is_paid) 
               VALUES (?, 5000, 5000, CURDATE(), 'Cash', 1)")->execute([$custId]);
$saleId = $pdo->lastInsertId();

echo "Testing Debt Refund for Customer with 0 debt...\n";

$data = [
    'customer_id' => $custId,
    'sale_id' => $saleId,
    'amount' => 1000,
    'refund_type' => 'Debt',
    'weight_kg' => 0.1,
    'quantity_units' => 0,
    'reason' => 'Test'
];

try {
    $service->processRefund($data, 1);
    echo "FAILURE: Refund processed but should have been blocked.\n";
} catch (Exception $e) {
    echo "SUCCESS: Blocked correctly. Message: " . $e->getMessage() . "\n";
}

// Cleanup
$pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$saleId]);
$pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$custId]);
