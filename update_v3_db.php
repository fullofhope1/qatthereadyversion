<?php
require 'config/db.php';

// 1. Force Arabic Names for Types (Fixing standard set)
// Adjusting based on standard Yemeni Qat names if user hasn't provided specifics besides "Jamam" variants.
$names = [
    1 => 'جمام نقوة',
    2 => 'جمام كالف',
    3 => 'جمام سمين',
    4 => 'جمام قصار',
    5 => 'صدور نقوة',
    6 => 'صدور عادي',
    7 => 'قطل'
];

try {
    $stmt = $pdo->prepare("UPDATE qat_types SET name = ? WHERE id = ?");
    foreach ($names as $id => $arabicName) {
        $stmt->execute([$arabicName, $id]);
    }
    echo "Types updated.\n";

    // 2. Add Receiver Column if not exists
    $pdo->exec("ALTER TABLE sales ADD COLUMN transfer_receiver VARCHAR(100) DEFAULT NULL AFTER transfer_sender");
    echo "Added transfer_receiver column.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
