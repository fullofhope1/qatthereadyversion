<?php
// add_missing_cols.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/db.php';

echo "Connected. Checking columns...\n";

try {
    $columns = $pdo->query("DESCRIBE purchases")->fetchAll(PDO::FETCH_COLUMN);

    // Helper function
    function addCol($pdo, $col, $def)
    {
        global $columns;
        if (!in_array($col, $columns)) {
            $pdo->exec("ALTER TABLE purchases ADD COLUMN $col $def");
            echo "ADDED: $col\n";
        } else {
            echo "EXISTS: $col\n";
        }
    }

    addCol($pdo, 'provider_id', "INT AFTER qat_type_id");
    addCol($pdo, 'source_weight_grams', "DECIMAL(10, 2) DEFAULT 0.00 AFTER qat_type_id");
    addCol($pdo, 'received_weight_grams', "DECIMAL(10, 2) DEFAULT 0.00 AFTER source_weight_grams");
    addCol($pdo, 'price_per_kilo', "DECIMAL(10, 2) DEFAULT 0.00 AFTER agreed_price");
    addCol($pdo, 'is_received', "BOOLEAN DEFAULT 1 AFTER status");
    addCol($pdo, 'received_at', "DATETIME NULL AFTER is_received");

    echo "--- DONE ---\n";

    // Final Verify
    $finalCols = $pdo->query("DESCRIBE purchases")->fetchAll(PDO::FETCH_COLUMN);
    echo "Final Columns: " . implode(", ", $finalCols);
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
