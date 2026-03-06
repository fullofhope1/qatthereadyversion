<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "--- جرد الديون اليومية ---\n";
$stmt = $pdo->query("SELECT sale_date, COUNT(*) as count, SUM(price) as total FROM sales WHERE is_paid = 0 AND payment_method = 'Debt' AND debt_type = 'Daily' GROUP BY sale_date ORDER BY sale_date ASC");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "لا توجد ديون يومية غير مدفوعة.\n";
} else {
    foreach ($results as $r) {
        echo "التاريخ: {$r['sale_date']} | العدد: {$r['count']} | الإجمالي: {$r['total']}\n";
    }
}

echo "\n--- جرد المشتريات ---\n";
$stmt = $pdo->query("SELECT purchase_date, COUNT(*) as count, status FROM purchases WHERE status IN ('Fresh', 'Momsi') GROUP BY purchase_date, status ORDER BY purchase_date ASC");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $r) {
    echo "التاريخ: {$r['purchase_date']} | العدد: {$r['count']} | الحالة: {$r['status']}\n";
}
echo "\n=== END ===\n";
