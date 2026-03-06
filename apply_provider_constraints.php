<?php
// apply_provider_constraints.php
require 'config/db.php';

try {
    echo "Checking for duplicates in 'providers' table...\n";

    // 1. Check for duplicate names
    $stmt = $pdo->query("SELECT name, COUNT(*) as count FROM providers GROUP BY name HAVING count > 1");
    $duplicateNames = $stmt->fetchAll();

    if ($duplicateNames) {
        echo "Found duplicate names:\n";
        foreach ($duplicateNames as $row) {
            echo "- " . $row['name'] . " (" . $row['count'] . " times)\n";
        }
        echo "Please clean up duplicate names before applying unique constraint.\n";
        exit;
    }

    // 2. Check for duplicate phones
    $stmt = $pdo->query("SELECT phone, COUNT(*) as count FROM providers GROUP BY phone HAVING count > 1");
    $duplicatePhones = $stmt->fetchAll();

    if ($duplicatePhones) {
        echo "Found duplicate phone numbers:\n";
        foreach ($duplicatePhones as $row) {
            echo "- " . $row['phone'] . " (" . $row['count'] . " times)\n";
        }
        echo "Please clean up duplicate phone numbers before applying unique constraint.\n";
        exit;
    }

    echo "No duplicates found. Applying unique constraints...\n";

    // 3. Add unique constraints
    // We add them one by one to avoid issues if one already exists
    try {
        $pdo->exec("ALTER TABLE providers ADD CONSTRAINT unique_provider_name UNIQUE (name)");
        echo "Unique constraint added to 'name' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Unique constraint already exists for 'name'.\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE providers ADD CONSTRAINT unique_provider_phone UNIQUE (phone)");
        echo "Unique constraint added to 'phone' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Unique constraint already exists for 'phone'.\n";
        } else {
            throw $e;
        }
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
