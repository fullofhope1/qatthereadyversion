<?php
require_once 'config/db.php';
require_once 'includes/Autoloader.php';

$date = '2026-04-30';
$sql = "SELECT s.*, p.price_per_kilo, p.price_per_unit, p.net_cost, p.quantity_kg, p.received_units 
        FROM sales s 
        LEFT JOIN purchases p ON s.purchase_id = p.id 
        WHERE s.sale_date = ? AND s.is_returned = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute([$date]);
$sales = $stmt->fetchAll();

echo "--- Detailed Sales Cost Analysis for $date ---\n";
foreach ($sales as $s) {
    $cost = 0;
    if ($s['unit_type'] === 'weight') {
        $cost = ($s['weight_grams'] / 1000) * $s['price_per_kilo'];
    } else {
        $cost = $s['quantity_units'] * $s['price_per_unit'];
    }
    echo "Sale ID: {$s['id']}, Price: " . number_format($s['price']) . ", Cost: " . number_format($cost) . " (Qty: " . ($s['unit_type'] === 'weight' ? $s['weight_grams'].'g' : $s['quantity_units'].' units') . ", Purchase Price: " . ($s['unit_type'] === 'weight' ? $s['price_per_kilo'].'/kg' : $s['price_per_unit'].'/unit') . ")\n";
    echo "--- Purchase Details for this sale ---\n";
    echo "Purchase ID: {$s['purchase_id']}, Net Cost: " . number_format($s['net_cost']) . ", Qty KG: {$s['quantity_kg']}, Price Per Kilo: " . number_format($s['price_per_kilo']) . "\n";
}
