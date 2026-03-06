<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

$date = '2026-02-16';
echo "=== TARGET DATE: $date ===\n\n";

echo "--- SALES (is_paid=0, Debt) ---\n";
$stmt = $pdo->prepare("SELECT id, sale_date, customer_id, price, weight_grams, debt_type, payment_method, purchase_id, leftover_id FROM sales WHERE sale_date = ? AND payment_method = 'Debt' AND is_paid = 0");
$stmt->execute([$date]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Price: {$row['price']} | Weight: {$row['weight_grams']} | Type: [{$row['debt_type']}] | P_ID: {$row['purchase_id']} | L_ID: {$row['leftover_id']}\n";
}

echo "\n--- PURCHASES (Fresh/Momsi) ---\n";
$stmt = $pdo->prepare("SELECT id, qat_type_id, quantity_kg, status FROM purchases WHERE purchase_date = ? AND status IN ('Fresh', 'Momsi')");
$stmt->execute([$date]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Type: {$row['qat_type_id']} | Qty: {$row['quantity_kg']} | Status: {$row['status']}\n";
}

echo "\n--- SYSTEM STATUS ---\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM sales WHERE sale_date = '$date' AND is_paid = 0 AND payment_method = 'Debt' AND debt_type = 'Daily'");
echo "Count (Strict Daily): " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM sales WHERE sale_date = '$date' AND is_paid = 0 AND payment_method = 'Debt' AND (debt_type IS NULL OR debt_type = '')");
echo "Count (Empty Type): " . $stmt->fetchColumn() . "\n";

echo "\n=== END DUMP ===\n";
