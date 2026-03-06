<?php
require 'config/db.php';

try {
    echo "<h3>Robust Cleanup & Constraints</h3>";

    // 1. Check for IDs 23 and 24
    $ids = $pdo->query("SELECT id FROM customers WHERE id IN (23, 24)")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($ids)) {
        echo "Found IDs: " . implode(', ', $ids) . ". Deleting now...<br>";
        $pdo->exec("DELETE FROM customers WHERE id IN (23, 24)");
        echo "Deleted.<br>";
    } else {
        echo "IDs 23 and 24 not found. Likely already deleted or never existed accurately.<br>";
    }

    // 2. Drop existing non-unique index if it exists (idx_customer_name)
    try {
        $pdo->exec("ALTER TABLE customers DROP INDEX idx_customer_name");
        echo "Dropped non-unique index 'idx_customer_name'.<br>";
    } catch (PDOException $e) {
        echo "Index 'idx_customer_name' might not exist or already dropped: " . $e->getMessage() . "<br>";
    }

    // 3. Add Unique Name index
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_NAME (name)");
        echo "Unique index 'UNIQUE_NAME' added.<br>";
    } catch (PDOException $e) {
        echo "Failed to add UNIQUE_NAME: " . $e->getMessage() . "<br>";
    }

    // 4. Add Unique Phone index
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_PHONE (phone)");
        echo "Unique index 'UNIQUE_PHONE' added.<br>";
    } catch (PDOException $e) {
        echo "Failed to add UNIQUE_PHONE: " . $e->getMessage() . "<br>";
    }
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
