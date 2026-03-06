<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

$stmt = $pdo->query("DESCRIBE sales");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['Field'] == 'sale_date') {
        echo "sale_date Type: {$row['Type']}\n";
    }
}

echo "\n--- SAMPLE SALE_DATE VALUES ---\n";
$stmt = $pdo->query("SELECT id, sale_date FROM sales WHERE is_paid = 0 AND payment_method = 'Debt' AND debt_type = 'Daily' LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Value: '{$row['sale_date']}'\n";
}
