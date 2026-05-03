<?php
require_once 'config/db.php';
require_once 'includes/Autoloader.php';

$date = '2026-04-30';
$repo = new ReportRepository($pdo);

$totals = $repo->getTotals('Daily', $date, null, null);
$cogs = $totals['total_cogs'];
$waste = $totals['total_waste_value'];

echo "--- Profit Analysis for $date ---\n";
echo "Gross Sales: " . number_format($totals['gross_sales']) . "\n";
echo "Total Refunds: " . number_format($totals['total_refunds']) . "\n";
echo "Net Sales: " . number_format($totals['total_sales']) . "\n";
echo "COGS (Cost of sold items): " . number_format($cogs) . "\n";
echo "Waste Value (Cost of trashed items): " . number_format($waste) . "\n";
echo "Total Expenses: " . number_format($totals['total_expenses']) . "\n";
echo "Total Compensations: " . (isset($totals['total_compensations']) ? number_format($totals['total_compensations']) : "N/A") . "\n";
echo "Real Profit: " . number_format($totals['real_profit']) . "\n";

// Detailed Waste Check
$sqlWaste = "SELECT l.*, p.price_per_kilo, p.price_per_unit 
             FROM leftovers l 
             JOIN purchases p ON l.purchase_id = p.id 
             WHERE l.decision_date = ? AND l.status IN ('Dropped', 'Auto_Dropped')";
$stmt = $pdo->prepare($sqlWaste);
$stmt->execute([$date]);
$wasteItems = $stmt->fetchAll();

echo "\n--- Detailed Waste Items ---\n";
foreach ($wasteItems as $item) {
    $cost = ($item['unit_type'] === 'weight') ? ($item['weight_kg'] * $item['price_per_kilo']) : ($item['quantity_units'] * $item['price_per_unit']);
    echo "- Purchase ID: {$item['purchase_id']}, Qty: " . ($item['unit_type'] === 'weight' ? $item['weight_kg'].'kg' : $item['quantity_units'].' units') . ", Cost: " . number_format($cost) . " (Status: {$item['status']})\n";
}

// Detailed Refunds/Compensations Check
$sqlRef = "SELECT * FROM refunds WHERE DATE(created_at) = ?";
$stmt = $pdo->prepare($sqlRef);
$stmt->execute([$date]);
$refunds = $stmt->fetchAll();

echo "\n--- Detailed Refunds/Compensations Table ---\n";
$sumRef = 0;
foreach ($refunds as $r) {
    echo "- ID: {$r['id']}, Amount: " . number_format($r['amount']) . ", Type: {$r['refund_type']}, Qty: {$r['weight_kg']}kg/{$r['quantity_units']}u\n";
    $sumRef += $r['amount'];
}
echo "Total from refunds table: " . number_format($sumRef) . "\n";
