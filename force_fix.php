<?php
// force_fix.php
require 'config/db.php';
echo "<h1>Force Fix Schema</h1>";
try {
    // 1. Check current columns
    $stmt = $pdo->query("DESCRIBE qat_types");
    $allCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cols = array_map(function ($c) {
        return $c['Field'];
    }, $allCols);

    echo "Current columns: " . implode(", ", $cols) . "<br>";

    if (!in_array('is_deleted', $cols)) {
        echo "Adding 'is_deleted' column... ";
        $pdo->exec("ALTER TABLE qat_types ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        echo "DONE!<br>";
    } else {
        echo "'is_deleted' already exists.<br>";
    }

    // 2. Double check by running the problematic query
    echo "Testing query: SELECT * FROM qat_types WHERE is_deleted = 0... ";
    $stmt = $pdo->query("SELECT * FROM qat_types WHERE is_deleted = 0");
    $count = $stmt->rowCount();
    echo "SUCCESS! Found $count rows.<br>";
} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";

    // Try to force add it if it's missing but DESCRIBE failed
    if (strpos($e->getMessage(), "Unknown column 'is_deleted'") !== false) {
        echo "Attempting emergency ALTER... ";
        try {
            $pdo->exec("ALTER TABLE qat_types ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
            echo "DONE!<br>";
        } catch (Exception $e2) {
            echo "FAILED: " . $e2->getMessage() . "<br>";
        }
    }
}
