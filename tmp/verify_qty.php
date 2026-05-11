<?php
require 'c:/xampp/htdocs/qat/config/db.php';
require 'c:/xampp/htdocs/qat/includes/Autoloader.php';

$refundRepo = new RefundRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);

$service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo);

// 1. Create a dummy sale of 1kg
$pdo->prepare("INSERT INTO customers (name) VALUES ('Test Qty')")->execute();
$custId = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO sales (customer_id, weight_kg, price, sale_date, payment_method) 
               VALUES (?, 1.0, 5000, CURDATE(), 'Cash')")->execute([$custId]);
$saleId = $pdo->lastInsertId();

echo "Testing Excess Quantity Return (Returning 2kg for 1kg sale)...\n";

$data = [
    'customer_id' => $custId,
    'sale_id' => $saleId,
    'amount' => 1000,
    'refund_type' => 'Cash',
    'weight_kg' => 2.0,
    'quantity_units' => 0,
    'reason' => 'Test'
];

try {
    $service->processRefund($data, 1);
    echo "FAILURE: Excess quantity allowed.\n";
} catch (Exception $e) {
    echo "SUCCESS: Blocked. Message: " . $e->getMessage() . "\n";
}

// Cleanup
$pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$saleId]);
$pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$custId]);
