<?php
require 'config/db.php';

echo "<h2>Database Fix / Migration Script</h2>";

try {
    // 1. Add 'discount' to 'sales'
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'discount'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00 AFTER price");
        echo "✅ Column 'discount' added to 'sales' table.<br>";
    } else {
        echo "ℹ️ Column 'discount' already exists in 'sales'.<br>";
    }

    // 2. Add 'debt_limit' to 'customers'
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'debt_limit'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN debt_limit DECIMAL(10,2) NULL AFTER total_debt");
        echo "✅ Column 'debt_limit' added to 'customers' table.<br>";
    } else {
        echo "ℹ️ Column 'debt_limit' already exists in 'customers'.<br>";
    }

    echo "<h3>Done. Please go back and refresh the page.</h3>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
