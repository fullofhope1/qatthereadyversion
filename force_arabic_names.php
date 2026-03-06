<?php
require 'config/db.php';

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
    echo "Names updated to Arabic successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
