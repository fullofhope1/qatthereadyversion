<?php
// reset_business_data.php
require 'config/db.php';

// Protection: Only run if explicitly confirmed via GET param (safety)
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    die("<h1>Safety Check</h1><p>This will DELETE ALL business data (Sales, Purchases, Customers, etc.).</p><p>To proceed, add <code>?confirm=yes</code> to the URL.</p>");
}

try {
    // Disable Foreign Key Checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [
        'sales',
        'sales_leftovers',
        'purchases',
        'payments',
        'expenses',
        'leftovers',
        'unknown_transfers',
        'customers',
        'providers'
        // 'staff' - User didn't explicitly say staff, but usually part of business reset. Keeping it based on "names of customers" hint.
        // 'users' - KEEP (Admins)
        // 'qat_types' - KEEP (Seed data)
    ];

    foreach ($tables as $table) {
        // Check if table exists before truncating
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetchColumn()) {
            $pdo->exec("TRUNCATE TABLE $table");
            echo "Truncated: $table<br>";
        } else {
            echo "Skipped (Not Found): $table<br>";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<h3 style='color:green'>Business Data Reset Successfully.</h3>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
