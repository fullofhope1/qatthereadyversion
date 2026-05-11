<?php
require 'config/db.php';
require_once 'includes/Autoloader.php';

$purchaseRepo = new PurchaseRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$refundRepo = new RefundRepository($pdo);
$unitSalesService = new UnitSalesService($purchaseRepo, $leftoverRepo, $saleRepo);
$service = new SaleService($saleRepo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitSalesService, $refundRepo);

$targetDate = '2026-04-15'; // Tomorrow
echo "Fetching unified stock for $targetDate...\n";
$stock = $service->getTodaysStock($targetDate);

$found = false;
foreach ($stock as $item) {
    echo "- [{$item['type']}] {$item['provider_name']} ({$item['status_label']}) rem: {$item['remaining_kg']}kg\n";
    // Check for provider 'أبومالخ' or similar
    if ($item['type'] === 'leftover') {
        $found = true;
    }
}

if ($found) {
    echo "\nSUCCESS: Found the leftover stock!\n";
} else {
    echo "\nFAILURE: Leftover stock not found for tomorrow.\n";
}
