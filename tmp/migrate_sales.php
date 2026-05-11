<?php
// /tmp/migrate.php
$is_localhost = true; // Force local for CLI
require 'c:/xampp/htdocs/qat/config/db.php';
try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN is_returned TINYINT(1) DEFAULT 0");
    echo "Column added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
