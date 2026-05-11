<?php
require_once 'C:/xampp/htdocs/qat/config/db.php';

$today = date('Y-m-d');
echo "TODAY: $today\n\n";

// Active leftovers
$leftovers = $pdo->query("SELECT id, status, source_date, sale_date, weight_kg, quantity_units, unit_type, purchase_id, qat_type_id FROM leftovers WHERE status NOT IN ('Dropped','Auto_Dropped','Closed') ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "=== ACTIVE LEFTOVERS ===\n";
foreach ($leftovers as $l) {
    echo "id={$l['id']} status={$l['status']} source={$l['source_date']} sale_date={$l['sale_date']} kg={$l['weight_kg']} units={$l['quantity_units']} type={$l['unit_type']}\n";
}

echo "\n=== RECENT FRESH/MOMSI PURCHASES ===\n";
$purchases = $pdo->query("SELECT id, status, purchase_date, quantity_kg, received_units, unit_type, qat_type_id FROM purchases WHERE status IN ('Fresh','Momsi') OR (status IS NULL OR status = '') ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($purchases as $p) {
    echo "id={$p['id']} status={$p['status']} date={$p['purchase_date']} kg={$p['quantity_kg']} units={$p['received_units']}\n";
}

echo "\n=== ALL LEFTOVERS (last 10) ===\n";
$all = $pdo->query("SELECT id, status, source_date, sale_date, weight_kg, quantity_units FROM leftovers ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $l) {
    echo "id={$l['id']} status={$l['status']} source={$l['source_date']} sale_date={$l['sale_date']} kg={$l['weight_kg']}\n";
}
