<?php
require 'config/db.php';

try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN transfer_sender VARCHAR(100) DEFAULT NULL AFTER payment_method");
    $pdo->exec("ALTER TABLE sales ADD COLUMN transfer_number VARCHAR(100) DEFAULT NULL AFTER transfer_sender");
    echo "Added transfer_sender and transfer_number to sales table.";
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage();
}
