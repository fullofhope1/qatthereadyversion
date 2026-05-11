<?php
require 'config/db.php';
require_once 'includes/Autoloader.php';

$purchaseRepo = new PurchaseRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$unitSalesService = new UnitSalesService($purchaseRepo, $leftoverRepo, $saleRepo);

echo "Testing SaleService instantiation...\n";
try {
    $service = new SaleService($saleRepo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitSalesService);
    echo "SUCCESS: SaleService instantiated with 5 arguments.\n";
    
    echo "Testing getAvailableLeftoverStock...\n";
    $stocks = $service->getAvailableLeftoverStock();
    echo "SUCCESS: Found " . count($stocks) . " leftover items.\n";
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
