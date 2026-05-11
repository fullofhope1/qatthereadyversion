<?php
// tmp/test_visibility.php
require 'config/db.php';
require_once 'includes/Autoloader.php';

$saleRepo = new SaleRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);
$unitSalesService = new UnitSalesService($purchaseRepo, $leftoverRepo, $saleRepo);

$service = new SaleService($saleRepo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitSalesService);

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "Testing Visibility for Today: $today\n";

$typeId = $pdo->query("SELECT id FROM qat_types LIMIT 1")->fetchColumn();
if (!$typeId) die("No qat types found to test.");

// Insert a fake leftover that was moved today for tomorrow
$stmt = $pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, sale_date, unit_type) 
                       VALUES (?, 0, ?, 10.5, 'Momsi_Day_1', ?, 'weight')");
$stmt->execute([$today, $typeId, $tomorrow]);
$fakeId = $pdo->lastInsertId();

$stock = $service->getTodaysStock($today);
$found = false;
foreach ($stock as $item) {
    if ($item['type'] === 'leftover' && $item['id'] == $fakeId) {
        $found = true;
        break;
    }
}

if (!$found) {
    $allLeftovers = $pdo->query("SELECT id, source_date, sale_date, status FROM leftovers WHERE id = $fakeId")->fetch(PDO::FETCH_ASSOC);
    echo "Debug - Fake Item: " . json_encode($allLeftovers) . "\n";
    echo "Debug - Search Date: $today\n";
}

if ($found) {
    echo "SUCCESS: Newly moved stock is VISIBLE immediately.\n";
} else {
    echo "FAILURE: Newly moved stock is HIDDEN.\n";
}

// Cleanup
$pdo->prepare("DELETE FROM leftovers WHERE id = ?")->execute([$fakeId]);
