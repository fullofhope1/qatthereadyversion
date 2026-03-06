<?php
require 'config/db.php';

header('Content-Type: text/plain; charset=utf-8');
$today = date('Y-m-d');

echo "=== CLOSING DATA DEBUG ===\n";
echo "Date: $today\n\n";

// 1. Check Purchases status
echo "--- Purchases for Today ---\n";
$stmt = $pdo->prepare("SELECT id, qat_type_id, quantity_kg, status FROM purchases WHERE purchase_date = ?");
$stmt->execute([$today]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($purchases as $p) {
    echo "ID: {$p['id']}, Type: {$p['qat_type_id']}, Qty: {$p['quantity_kg']}, Status: {$p['status']}\n";
}

// 2. Check Unpaid Daily Debts
echo "\n--- Unpaid Daily Debts for Today ---\n";
$stmt = $pdo->prepare("SELECT id, customer_id, price FROM sales 
                       WHERE sale_date = ? 
                       AND payment_method = 'Debt' 
                       AND debt_type = 'Daily' 
                       AND is_paid = 0");
$stmt->execute([$today]);
$debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($debts) . "\n";
foreach ($debts as $d) {
    echo "Sale ID: {$d['id']}, Cust ID: {$d['customer_id']}, Price: {$d['price']}\n";
}

// 3. Check Leftovers table
echo "\n--- Leftovers Status --- \n";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM leftovers GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Status: {$row['status']}, Count: {$row['count']}\n";
}

echo "\n--- Recent Auto-Close Logs (if any) ---\n";
// (Normally we'd check error logs, but let's check if the purchases are actually moving)
