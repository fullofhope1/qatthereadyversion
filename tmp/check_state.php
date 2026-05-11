<?php
// DIAGNOSTIC - access via http://localhost/qat/tmp/check_state.php
$pdo = new PDO("mysql:host=localhost;dbname=qat_erp;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$today = date('Y-m-d');
echo "<pre>TODAY: $today\n\n";

echo "=== PURCHASES (Fresh / unclosed last 7 days) ===\n";
$r = $pdo->query("SELECT id, status, purchase_date, quantity_kg, received_units, unit_type, qat_type_id FROM purchases WHERE purchase_date >= DATE_SUB('$today', INTERVAL 7 DAY) ORDER BY id DESC");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row)
    echo "id={$row['id']} status='{$row['status']}' date={$row['purchase_date']} qty_kg={$row['quantity_kg']} units={$row['received_units']} unit_type={$row['unit_type']}\n";

echo "\n=== LEFTOVERS (all, last 10 by id) ===\n";
$r = $pdo->query("SELECT id, status, source_date, sale_date, weight_kg, quantity_units, unit_type, purchase_id FROM leftovers ORDER BY id DESC LIMIT 10");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row)
    echo "id={$row['id']} status='{$row['status']}' source={$row['source_date']} sale={$row['sale_date']} kg={$row['weight_kg']} units={$row['quantity_units']} type={$row['unit_type']} purchase_id={$row['purchase_id']}\n";

echo "\n=== SALES (last 10 by id) ===\n";
$r = $pdo->query("SELECT id, sale_date, purchase_id, leftover_id, weight_grams, weight_kg, quantity_units, unit_type, is_returned FROM sales ORDER BY id DESC LIMIT 10");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row)
    echo "id={$row['id']} date={$row['sale_date']} purchase_id={$row['purchase_id']} leftover_id={$row['leftover_id']} grams={$row['weight_grams']} kg={$row['weight_kg']} units={$row['quantity_units']} type={$row['unit_type']} returned={$row['is_returned']}\n";

echo "\n=== COLUMNS in sales ===\n";
$r = $pdo->query("DESCRIBE sales");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row)
    echo $row['Field']." ".$row['Type']."\n";

echo "\n=== COLUMNS in purchases ===\n";
$r = $pdo->query("DESCRIBE purchases");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row)
    echo $row['Field']." ".$row['Type']."\n";

echo "</pre>";
