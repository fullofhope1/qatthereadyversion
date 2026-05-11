<?php
require 'config/db.php';
$sqls = [
    "ALTER TABLE qat_types ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER name",
    "ALTER TABLE leftovers ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER weight_kg",
    "ALTER TABLE refunds ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER refund_type"
];

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "Success: $sql\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
