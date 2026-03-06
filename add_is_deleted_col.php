<?php
require 'config/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'is_deleted'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        echo "Column 'is_deleted' added successfully.";
    } else {
        echo "Column 'is_deleted' already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
