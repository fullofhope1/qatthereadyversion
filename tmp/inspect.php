<?php
require_once 'C:/xampp/htdocs/qat/config/db.php';
$out = "=== LEFTOVERS ===\n";
$r = $pdo->query("SELECT id, status, source_date, sale_date, weight_kg, quantity_units, qat_type_id FROM leftovers ORDER BY id DESC LIMIT 20");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $out .= "id={$row['id']} | status='{$row['status']}' | source={$row['source_date']} | sale={$row['sale_date']} | kg={$row['weight_kg']} | units={$row['quantity_units']}\n";
}

$out .= "\n=== PURCHASES ===\n";
$r = $pdo->query("SELECT id, status, purchase_date, quantity_kg, received_units FROM purchases ORDER BY id DESC LIMIT 10");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $out .= "purch_id={$row['id']} | status='{$row['status']}' | date={$row['purchase_date']} | kg={$row['quantity_kg']} | units={$row['received_units']}\n";
}

file_put_contents('C:/xampp/htdocs/qat/tmp/state_after_close.txt', $out);
echo "Done";
