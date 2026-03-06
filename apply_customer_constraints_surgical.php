<?php
require 'config/db.php';

try {
    echo "<h3>Surgical Data Cleanup</h3>";

    // 1. Convert empty strings to NULL in phone column
    $pdo->exec("UPDATE customers SET phone = NULL WHERE phone = ''");
    echo "Converted empty phone numbers to NULL.<br>";

    // 2. Find and keep only one 'عرفج'
    $stmt = $pdo->query("SELECT id FROM customers WHERE name = 'عرفج' AND is_deleted = 0 ORDER BY id ASC");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($ids) > 1) {
        $keep = array_shift($ids); // Keep the first (likely oldest)
        $remove = implode(',', $ids);
        echo "Keeping 'عرفج' ID: $keep. Removing IDs: $remove<br>";

        // Before deleting, update any sales pointing to the removed IDs
        $pdo->exec("UPDATE sales SET customer_id = $keep WHERE customer_id IN ($remove)");
        echo "Updated sales records to point to primary ID.<br>";

        $pdo->exec("DELETE FROM customers WHERE id IN ($remove)");
        echo "Deleted redundant 'عرفج' records.<br>";
    } else {
        echo "No duplicates for 'عرفج' found.<br>";
    }

    // 3. Final attempt at constraints
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_NAME (name)");
        echo "<b style='color:green'>Unique index 'UNIQUE_NAME' added successfully.</b><br>";
    } catch (PDOException $e) {
        echo "Failed UNIQUE_NAME again: " . $e->getMessage() . "<br>";
    }

    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_PHONE (phone)");
        echo "<b style='color:green'>Unique index 'UNIQUE_PHONE' added successfully.</b><br>";
    } catch (PDOException $e) {
        echo "Failed UNIQUE_PHONE again: " . $e->getMessage() . "<br>";
    }
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
