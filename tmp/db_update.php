<?php
require 'c:/xampp/htdocs/qat/config/db.php';
try {
    $pdo->exec('ALTER TABLE sales ADD COLUMN returned_kg DECIMAL(10,3) DEFAULT 0 AFTER weight_kg');
} catch (PDOException $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec('ALTER TABLE sales ADD COLUMN returned_units INT DEFAULT 0 AFTER quantity_units');
} catch (PDOException $e) { echo $e->getMessage() . "\n"; }

echo "Columns added.";
