<?php
require 'config/db.php';

$updates = [
    'Jamam Naqwah' => 'جمام نقوة',
    'Jamam Kalif' => 'جمام كالف',
    'Jamam Samin' => 'جمام سمين',
    'Jamam Qasar' => 'جمام قصار',
    'Sudur Naqwah' => 'صدور نقوة',
    'Sudur Adi' => 'صدور عادي',
    'Qatal' => 'قطل'
];

try {
    foreach ($updates as $eng => $ara) {
        $stmt = $pdo->prepare("UPDATE qat_types SET name = ? WHERE name = ?");
        $stmt->execute([$ara, $eng]);
        echo "Updated '$eng' to '$ara'<br>\n";
    }
    echo "All updates completed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
