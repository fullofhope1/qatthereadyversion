<?php
require 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'debt_limit'");
    if ($stmt->rowCount() == 0) {
        // Add column
        $pdo->exec("ALTER TABLE customers ADD COLUMN debt_limit DECIMAL(10,2) NULL AFTER total_debt");
        echo "Column 'debt_limit' added. Default is 0 (No Limit/Check Logic to be decided - user implies strict limit, so maybe 0 means 0 allowed?)<br>";
        echo "Actually, user said 'limit... creating a new customer'. Default of 0 might block everything if strict.";
        echo "Let's set default to a high number or handle 0 as 'No Credit Allowed' depending on user choice. <br>";
        echo "User: 'system will never allow... above his limit'. So 0 means 0 credit allowed.";
    } else {
        echo "Column 'debt_limit' already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
