<?php
require 'config/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'discount' ");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00 AFTER price");
        echo "Column 'discount' added to sales table.<br>";
    } else {
        echo "Column 'discount' already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
