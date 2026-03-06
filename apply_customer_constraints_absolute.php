<?php
require 'config/db.php';

try {
    echo "<h3>Absolute Data Cleanup (Including Deleted Records)</h3>";

    // 1. Convert ALL empty strings to NULL
    $pdo->exec("UPDATE customers SET phone = NULL WHERE phone = ''");
    echo "Standardized ALL empty phone numbers to NULL.<br>";

    // 2. Resolve ALL Duplicate Names (ignoring is_deleted)
    $duplicateNames = $pdo->query("SELECT name, COUNT(*) as count FROM customers GROUP BY name HAVING count > 1")->fetchAll();
    foreach ($duplicateNames as $dup) {
        $name = $dup['name'];
        echo "Processing duplicate name: '$name' ({$dup['count']})<br>";

        $stmt = $pdo->prepare("SELECT id, is_deleted FROM customers WHERE name = ? ORDER BY is_deleted ASC, id ASC");
        $stmt->execute([$name]);
        $rows = $stmt->fetchAll();
        $ids = array_column($rows, 'id');

        $keepId = array_shift($ids);
        $removeIds = implode(',', $ids);

        // Merge sales
        $pdo->exec("UPDATE sales SET customer_id = $keepId WHERE customer_id IN ($removeIds)");
        echo "- Merged sales for IDs ($removeIds) into $keepId.<br>";

        // Delete extras
        $pdo->exec("DELETE FROM customers WHERE id IN ($removeIds)");
        echo "- Deleted redundant records ($removeIds).<br>";
    }

    // 3. Resolve ALL Duplicate Phones (ignoring is_deleted)
    $duplicatePhones = $pdo->query("SELECT phone, COUNT(*) as count FROM customers WHERE phone IS NOT NULL GROUP BY phone HAVING count > 1")->fetchAll();
    foreach ($duplicatePhones as $dup) {
        $phone = $dup['phone'];
        echo "Processing duplicate phone: '$phone' ({$dup['count']})<br>";

        $stmt = $pdo->prepare("SELECT id, is_deleted FROM customers WHERE phone = ? ORDER BY is_deleted ASC, id ASC");
        $stmt->execute([$phone]);
        $rows = $stmt->fetchAll();
        $ids = array_column($rows, 'id');

        $keepId = array_shift($ids);
        $removeIds = implode(',', $ids);

        // Merge sales
        $pdo->exec("UPDATE sales SET customer_id = $keepId WHERE customer_id IN ($removeIds)");
        echo "- Merged sales for IDs ($removeIds) into $keepId.<br>";

        // Delete extras
        $pdo->exec("DELETE FROM customers WHERE id IN ($removeIds)");
        echo "- Deleted redundant records ($removeIds).<br>";
    }

    // 4. Final attempt at constraints
    echo "<h4>Applying Constraints...</h4>";
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_NAME (name)");
        echo "<b style='color:green'>Success: UNIQUE_NAME index added.</b><br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color:orange'>UNIQUE_NAME already exists.</p>";
        } else {
            echo "Error adding UNIQUE_NAME: " . $e->getMessage() . "<br>";
        }
    }

    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_PHONE (phone)");
        echo "<b style='color:green'>Success: UNIQUE_PHONE index added.</b><br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color:orange'>UNIQUE_PHONE already exists.</p>";
        } else {
            echo "Error adding UNIQUE_PHONE: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br><p>Finished. <a href='index.php'>Go to Homepage</a></p>";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
