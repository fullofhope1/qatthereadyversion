<?php
require 'c:/xampp/htdocs/qat/config/db.php';
require 'c:/xampp/htdocs/qat/includes/classes/BaseRepository.php';
require 'c:/xampp/htdocs/qat/includes/classes/ReportRepository.php';

$repo = new ReportRepository($pdo);
$month = '2026-04';
$year = '2026';

echo "Testing getSalesList for Monthly (2026-04):\n";
$sales = $repo->getSalesList('Monthly', '', $month, $year);
echo "Count: " . count($sales) . "\n";
if (count($sales) > 0) {
    echo "First item date: " . $sales[0]['sale_date'] . "\n";
}

echo "\nTesting getTotals for Monthly (2026-04):\n";
$totalsMonth = $repo->getTotals('Monthly', '', $month, $year);
print_r($totalsMonth);
?>
