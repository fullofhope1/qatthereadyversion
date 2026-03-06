<?php
require 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'paid_amount'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0 AFTER price");
        echo "Column 'paid_amount' added to sales table successfully.";
    } else {
        echo "Column 'paid_amount' already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
