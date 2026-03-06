<?php
require 'config/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'transfer_company'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add transfer_company column after transfer_number
        $pdo->exec("ALTER TABLE sales ADD COLUMN transfer_company VARCHAR(100) DEFAULT NULL AFTER transfer_number");
        echo "✓ Successfully added transfer_company column to sales table.\n";
    } else {
        echo "ℹ Column transfer_company already exists in sales table.\n";
    }

    echo "\nDatabase updated successfully!\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
