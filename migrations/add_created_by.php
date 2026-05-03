<?php
require 'config/db.php';
$queries = [
    "ALTER TABLE customers ADD COLUMN IF NOT EXISTS created_by INT NULL",
    "ALTER TABLE qat_types ADD COLUMN IF NOT EXISTS created_by INT NULL",
    "ALTER TABLE sales ADD COLUMN IF NOT EXISTS created_by INT NULL",
    "ALTER TABLE purchases ADD COLUMN IF NOT EXISTS created_by INT NULL",
    "ALTER TABLE expenses ADD COLUMN IF NOT EXISTS created_by INT NULL",
    "ALTER TABLE staff ADD COLUMN IF NOT EXISTS created_by INT NULL",
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Success: $sql\n";
    } catch (Exception $e) {
        echo "Error on $sql: " . $e->getMessage() . "\n";
    }
}
echo "Migration finished.\n";
