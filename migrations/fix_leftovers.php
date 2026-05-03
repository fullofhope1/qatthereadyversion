<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE leftovers ADD COLUMN created_by INT NULL");
    echo "Column added successfully to leftovers table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
