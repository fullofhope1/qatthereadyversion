<?php
require 'c:/xampp/htdocs/qat/config/db.php';
require 'c:/xampp/htdocs/qat/includes/Autoloader.php';

$repo = new DailyCloseRepository($pdo);
$service = new DailyCloseService($repo);

// 1. Create a dummy purchase for today
$today = date('Y-m-d');
$pdo->prepare("DELETE FROM purchases WHERE purchase_date = ? AND created_by = 999")->execute([$today]);
$pdo->prepare("INSERT INTO purchases (qat_type_id, quantity_kg, unit_type, purchase_date, is_received, status, created_by) 
               VALUES (1, 10.0, 'weight', ?, 1, 'Fresh', 999)")->execute([$today]);
$pid = $pdo->lastInsertId();

echo "Created Purchase ID: $pid\n";

// 2. Run Close Day for today
echo "Running Close Day for $today...\n";
$service->closeDay($today);

// 3. Verify leftover exists
$stmt = $pdo->prepare("SELECT * FROM leftovers WHERE purchase_id = ?");
$stmt->execute([$pid]);
$lo = $stmt->fetch();

if ($lo) {
    echo "SUCCESS: Leftover created!\n";
    echo "Status: " . $lo['status'] . "\n";
    echo "Sale Date: " . $lo['sale_date'] . "\n";
    echo "Type ID: " . $lo['qat_type_id'] . "\n";
    echo "Weight: " . $lo['weight_kg'] . "\n";
} else {
    echo "FAILURE: Leftover not found.\n";
}

// Cleanup
$pdo->prepare("DELETE FROM leftovers WHERE purchase_id = ?")->execute([$pid]);
$pdo->prepare("DELETE FROM purchases WHERE id = ?")->execute([$pid]);
