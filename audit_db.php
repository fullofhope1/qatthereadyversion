<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

$today = date('Y-m-d');
echo "=== COMPREHENSIVE DATABASE AUDIT ($today) ===\n\n";

// 1. All Unpaid Daily Debts
echo "--- ALL UNPAID DAILY DEBTS ---\n";
$stmt = $pdo->query("SELECT id, sale_date, customer_id, price, debt_type, payment_method FROM sales WHERE is_paid = 0 AND payment_method = 'Debt' AND debt_type = 'Daily'");
$debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Count: " . count($debts) . "\n";
foreach ($debts as $d) {
    echo "ID: {$d['id']}, Date: {$d['sale_date']}, CID: {$d['customer_id']}, Price: {$d['price']}, Type: {$d['debt_type']}\n";
}

// 2. All Fresh/Momsi Purchases
echo "\n--- ALL FRESH/MOMSI PURCHASES ---\n";
$stmt = $pdo->query("SELECT id, purchase_date, qat_type_id, quantity_kg, status FROM purchases WHERE status IN ('Fresh', 'Momsi')");
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Count: " . count($purchases) . "\n";
foreach ($purchases as $p) {
    echo "ID: {$p['id']}, Date: {$p['purchase_date']}, Type: {$p['qat_type_id']}, Qty: {$p['quantity_kg']}, Status: {$p['status']}\n";
}

// 3. Leftovers Check
echo "\n--- ACTIVE LEFTOVERS ---\n";
$stmt = $pdo->query("SELECT id, source_date, qat_type_id, weight_kg, status FROM leftovers WHERE status = 'Transferred_Next_Day'");
$leftovers = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Count: " . count($leftovers) . "\n";
foreach ($leftovers as $l) {
    echo "ID: {$l['id']}, Source: {$l['source_date']}, Type: {$l['qat_type_id']}, Wt: {$l['weight_kg']}, Status: {$l['status']}\n";
}

// 4. Check for any NULLs or weird values in debt_type or payment_method
echo "\n--- WEIRD VALUES CHECK ---\n";
$stmt = $pdo->query("SELECT DISTINCT debt_type FROM sales");
echo "Debt Types: " . implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN)) . "\n";
$stmt = $pdo->query("SELECT DISTINCT payment_method FROM sales");
echo "Payment Methods: " . implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN)) . "\n";

echo "\n=== END AUDIT ===\n";
