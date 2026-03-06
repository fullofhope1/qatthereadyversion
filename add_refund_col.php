<?php
require 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'refund_amount'");
    if ($stmt->rowCount() == 0) {
        // Add column
        $pdo->exec("ALTER TABLE sales ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0.00 AFTER price");
        echo "Column 'refund_amount' added.<br>";
    } else {
        echo "Column 'refund_amount' already exists.<br>";
    }

    // Add 'Refund' to expenses category enum? 
    // Usually enum is not used, just string in my code. Checked expenses.php - it uses a selects but DB is likely varchar.
    // Confirmed via expenses.php logic - just inserts string.

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
