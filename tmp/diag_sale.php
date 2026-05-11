<?php
require 'config/db.php';
require 'includes/classes/BaseRepository.php';
require 'includes/classes/BaseService.php';
require 'includes/classes/ProductRepository.php';
require 'includes/classes/CustomerRepository.php';
require 'includes/classes/ProviderRepository.php';
require 'includes/classes/PurchaseRepository.php';
require 'includes/classes/PurchaseService.php';
require 'includes/classes/SaleRepository.php';
require 'includes/classes/SaleService.php';
require 'includes/classes/LeftoverRepository.php';
require 'includes/classes/RefundRepository.php';
require 'includes/classes/RefundService.php';
require 'includes/classes/UnitSalesService.php';

$pRepo = new PurchaseRepository($pdo);
$sRepo = new SaleRepository($pdo);
$cRepo = new CustomerRepository($pdo);
$lRepo = new LeftoverRepository($pdo);
$uServ = new UnitSalesService($pRepo, $lRepo, $sRepo);

$sServ = new SaleService($sRepo, $pRepo, $cRepo, $lRepo, $uServ);

echo "SaleService purchaseRepo: " . (isset($sServ->purchaseRepo) ? 'exists' : 'missing') . "\n";
// Since it's private, we can't check directly without reflection or adding a getter
$reflector = new ReflectionClass('SaleService');
$prop = $reflector->getProperty('purchaseRepo');
$prop->setAccessible(true);
echo "SaleService purchaseRepo instance: " . get_class($prop->getValue($sServ)) . "\n";

$data = [
    'unit_type' => 'weight',
    'purchase_id' => 1,
    'weight_grams' => 1000
];
try {
    echo "Running processSale...\n";
    // We expect it to fail on DB connection if record doesn't exist, but we want to see if it reaches line 117 without null error
    $sServ->processSale($data);
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}
