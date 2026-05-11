<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE staff ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "SUCCESS";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "EXISTS";
    } else {
        echo "ERROR: " . $e->getMessage();
    }
}
