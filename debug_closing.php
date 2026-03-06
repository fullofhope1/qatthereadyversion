<?php
require 'config/db.php';

echo "<h2>Closing Process Diagnostic</h2>";
echo "<hr>";

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "<p><strong>Today:</strong> $today</p>";
echo "<p><strong>Tomorrow:</strong> $tomorrow</p>";
echo "<hr>";

// Get all qat types
$types = $pdo->query("SELECT * FROM qat_types")->fetchAll();

echo "<h3>Surplus Calculation (Same as Closing Page)</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Type</th><th>Bought (kg)</th><th>Sold (kg)</th><th>Surplus (kg)</th></tr>";

foreach ($types as $t) {
    $stmtBuy = $pdo->prepare("SELECT SUM(quantity_kg) FROM purchases WHERE qat_type_id = ? AND purchase_date = ?");
    $stmtBuy->execute([$t['id'], $today]);
    $bought = $stmtBuy->fetchColumn() ?: 0;

    $stmtSell = $pdo->prepare("SELECT SUM(weight_kg) FROM sales WHERE qat_type_id = ? AND sale_date = ?");
    $stmtSell->execute([$t['id'], $today]);
    $sold = $stmtSell->fetchColumn() ?: 0;

    $surplus = $bought - $sold;

    echo "<tr>";
    echo "<td>{$t['name']}</td>";
    echo "<td>" . number_format($bought, 3) . "</td>";
    echo "<td>" . number_format($sold, 3) . "</td>";
    echo "<td style='color:" . ($surplus > 0 ? 'red' : 'green') . "'>" . number_format($surplus, 3) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Existing Momsi Purchases</h3>";
$momsi = $pdo->query("SELECT * FROM purchases WHERE status = 'Momsi' ORDER BY purchase_date DESC")->fetchAll();

if (count($momsi) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Date</th><th>Type ID</th><th>Vendor</th><th>Quantity</th></tr>";
    foreach ($momsi as $m) {
        echo "<tr>";
        echo "<td>{$m['purchase_date']}</td>";
        echo "<td>{$m['qat_type_id']}</td>";
        echo "<td>{$m['vendor_name']}</td>";
        echo "<td>{$m['quantity_kg']} kg</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ NO MOMSI PURCHASES FOUND IN DATABASE</p>";
}

echo "<hr>";
echo "<h3>All Purchases Today</h3>";
$todayPurchases = $pdo->query("SELECT * FROM purchases WHERE purchase_date = '$today'")->fetchAll();
echo "<p>Found: " . count($todayPurchases) . " purchase(s)</p>";

echo "<h3>All Sales Today</h3>";
$todaySales = $pdo->query("SELECT * FROM sales WHERE sale_date = '$today'")->fetchAll();
echo "<p>Found: " . count($todaySales) . " sale(s)</p>";
