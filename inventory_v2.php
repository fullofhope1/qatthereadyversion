<?php
require 'config/db.php';

$output = "--- جرد الديون اليومية ---\n";
$stmt = $pdo->query("SELECT sale_date, COUNT(*) as count, SUM(price) as total FROM sales WHERE is_paid = 0 AND payment_method = 'Debt' AND debt_type = 'Daily' GROUP BY sale_date ORDER BY sale_date ASC");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $r) {
    $output .= "التاريخ: {$r['sale_date']} | العدد: {$r['count']} | الإجمالي: {$r['total']}\n";
}

$output .= "\n--- جرد المشتريات ---\n";
$stmt = $pdo->query("SELECT purchase_date, COUNT(*) as count, status FROM purchases WHERE status IN ('Fresh', 'Momsi') GROUP BY purchase_date, status ORDER BY purchase_date ASC");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $r) {
    $output .= "التاريخ: {$r['purchase_date']} | العدد: {$r['count']} | الحالة: {$r['status']}\n";
}

file_put_contents('inventory_report.txt', $output);
echo "تم إنشاء التقرير في inventory_report.txt\n";
