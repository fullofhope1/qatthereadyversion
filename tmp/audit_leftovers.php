<?php
require_once 'C:/xampp/htdocs/qat/config/db.php';
require_once 'C:/xampp/htdocs/qat/includes/Autoloader.php';

$date = date('Y-m-d');
$repoL = new LeftoverRepository($pdo);
$repoP = new PurchaseRepository($pdo);
$repoS = new SaleRepository($pdo);
$unitS = new UnitSalesService($repoP, $repoL, $repoS);
$service = new SaleService($repoS, $repoP, new CustomerRepository($pdo), $repoL, $unitS);

echo "TODAY DATE: $date\n";

$stocks = $service->getAvailableLeftoverStock();
echo "STOCKS RETURNED BY SERVICE: " . count($stocks) . "\n";
foreach ($stocks as $s) {
    echo "ID: {$s['id']} | Type: {$s['qat_type_id']} | Provider: {$s['provider_name']} | Status: {$s['status']} | RemKg: {$s['remaining_kg']} | SaleDate: {$s['sale_date']} | SourceDate: {$s['source_date']}\n";
}

echo "\nDATABASE RAW (Active Leftovers):\n";
$raw = $pdo->query("SELECT id, status, qat_type_id, source_date, sale_date, weight_kg, quantity_units FROM leftovers WHERE status NOT IN ('Dropped','Closed','Auto_Dropped')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($raw as $r) {
    echo "ID: {$r['id']} | Status: {$r['status']} | Type: {$r['qat_type_id']} | Source: {$r['source_date']} | SaleDate: {$r['sale_date']} | Weight: {$r['weight_kg']}\n";
}

echo "\nQAT TYPES:\n";
$types = $pdo->query("SELECT id, name FROM qat_types")->fetchAll(PDO::FETCH_ASSOC);
foreach ($types as $t) {
    echo "ID: {$t['id']} | Name: {$t['name']}\n";
}
