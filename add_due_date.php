<?php
require 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'due_date'");
    if ($stmt->rowCount() == 0) {
        // Add column
        $pdo->exec("ALTER TABLE sales ADD COLUMN due_date DATE DEFAULT NULL AFTER sale_date");
        echo "Column 'due_date' added.<br>";

        // Backfill: Set due_date = sale_date for all existing rows
        $pdo->exec("UPDATE sales SET due_date = sale_date WHERE due_date IS NULL");
        echo "Backfilled existing sales.<br>";
    } else {
        echo "Column 'due_date' already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
