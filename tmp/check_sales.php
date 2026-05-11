<?php
require 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE sales");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
