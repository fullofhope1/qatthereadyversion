<?php
require 'config/db.php';

// Force Update Qat Names to Arabic
$updates = [
    'Jamam' => 'جمام نقوة',
    'Jamam Naqwah' => 'جمام نقوة',
    'Jamam Kalif' => 'جمام كالف',
    'Jamam Samin' => 'جمام سمين',
    'Jamam Qasar' => 'جمام قصار',
    'Sudur Naqwah' => 'صدور نقوة',
    'Sudur' => 'صدور',
    'Sudur Adi' => 'صدور عادي',
    'Qatal' => 'قطل'
];

try {
    $sql = "UPDATE qat_types SET name = ? WHERE name LIKE ?";
    $stmt = $pdo->prepare($sql);

    foreach ($updates as $eng => $ara) {
        $stmt->execute([$ara, "%$eng%"]);
    }

    echo "Names updated to Arabic.\n";

    // Check current names
    echo "Current Types:\n";
    $types = $pdo->query("SELECT * FROM qat_types")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($types as $t) {
        echo $t['id'] . ": " . $t['name'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
