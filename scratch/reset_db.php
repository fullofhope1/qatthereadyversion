<?php
// scratch/reset_db.php
require '../config/db.php';

$tablesToTruncate = [
    'sales',
    'purchases',
    'leftovers',
    'expenses',
    'customers',
    'providers',
    'staff_withdrawals',
    'salary_payments',
    'refunds',
    'provider_payments',
    'debts' // if there is a specific debts table, though usually they are in sales
];

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Get all tables to be sure
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $keep = ['qat_types', 'users'];
    
    foreach ($allTables as $table) {
        if (!in_array($table, $keep)) {
            echo "Truncating $table...\n";
            $pdo->exec("TRUNCATE TABLE `$table` ");
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\nDatabase Reset Successfully! Kept: qat_types, users.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
