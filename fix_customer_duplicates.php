<?php
require 'config/db.php';

try {
    echo "<h3>Applying Unique Constraints to Customers Table...</h3>";

    // 1. Add unique index for Name
    // Note: We use IGNORE or check for duplicates first? 
    // Usually, we should check if there are already duplicates.

    $duplicates = $pdo->query("SELECT name, COUNT(*) as count FROM customers WHERE is_deleted = 0 GROUP BY name HAVING count > 1")->fetchAll();
    if (!empty($duplicates)) {
        echo "<p style='color:red'>Warning: Duplicate names found. You must clean them up before applying a unique index.</p>";
        foreach ($duplicates as $d) {
            echo "- {$d['name']} ({$d['count']} times)<br>";
        }
    } else {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_NAME (name)");
        echo "<p style='color:green'>Unique index added for 'name'.</p>";
    }

    // 2. Add unique index for Phone (allowing NULLs if phone is nullable)
    // In MySQL, multiple NULL values are allowed in a UNIQUE index.
    $duplicatesPhone = $pdo->query("SELECT phone, COUNT(*) as count FROM customers WHERE is_deleted = 0 AND phone IS NOT NULL AND phone != '' GROUP BY phone HAVING count > 1")->fetchAll();
    if (!empty($duplicatesPhone)) {
        echo "<p style='color:red'>Warning: Duplicate phone numbers found. You must clean them up before applying a unique index.</p>";
        foreach ($duplicatesPhone as $d) {
            echo "- {$d['phone']} ({$d['count']} times)<br>";
        }
    } else {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_PHONE (phone)");
        echo "<p style='color:green'>Unique index added for 'phone'.</p>";
    }

    echo "<br><p>Finished. <a href='index.php'>Go to Homepage</a></p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "<p style='color:orange'>Constraints already exists.</p>";
    } else {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}
