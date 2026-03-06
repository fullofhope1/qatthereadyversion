<?php
require 'config/db.php';
try {
    // 1. Alter table to VARCHAR
    $pdo->exec("ALTER TABLE expenses MODIFY category VARCHAR(100) NOT NULL");
    echo "Table altered successfully.\n";

    // 2. Clean up empty categories safely
    $stmt = $pdo->prepare("UPDATE expenses SET category = 'أخرى' WHERE category = '' OR category IS NULL");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " rows.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
