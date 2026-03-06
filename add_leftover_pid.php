<?php
require 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM leftovers LIKE 'purchase_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE leftovers ADD COLUMN purchase_id INT AFTER source_date");
        // Optional: Add FK if purchases table is strict, but usually safe to just add column
        echo "Column 'purchase_id' added to leftovers table.<br>";
    } else {
        echo "Column 'purchase_id' already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
