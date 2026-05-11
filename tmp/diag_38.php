<?php
require 'config/db.php';
require_once 'includes/Autoloader.php';

$repository = new DailyCloseRepository($pdo);
$p = $pdo->query("SELECT * FROM purchases WHERE id = 38")->fetch(PDO::FETCH_ASSOC);

$stats = $repository->getSoldAndManagedForPurchase(38);
$surplusKg = (float)$p['quantity_kg'] - (float)$stats['sold_kg'] - (float)$stats['managed_kg'];
$surplusUnits = (int)($p['received_units'] ?? 0) - (int)$stats['sold_units'] - (int)$stats['managed_units'];

echo "Purchase ID: 38\n";
echo "Quantity Kg: {$p['quantity_kg']}\n";
echo "Sold Kg: {$stats['sold_kg']}\n";
echo "Managed Kg: {$stats['managed_kg']}\n";
echo "Surplus Kg: $surplusKg\n";

echo "Received Units: ".($p['received_units'] ?? 0)."\n";
echo "Sold Units: {$stats['sold_units']}\n";
echo "Managed Units: {$stats['managed_units']}\n";
echo "Surplus Units: $surplusUnits\n";

if ($surplusKg > 0.001 || $surplusUnits > 0) {
    echo "SHOULD MOVE\n";
} else {
    echo "SHOULD NOT MOVE\n";
}
