<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

$stmt = $pdo->query("SELECT id, debt_type, LENGTH(debt_type) as len FROM sales WHERE is_paid = 0 AND payment_method = 'Debt' AND sale_date = '2026-02-16' LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Type: '[{$row['debt_type']}]' | Len: {$row['len']}\n";
}
