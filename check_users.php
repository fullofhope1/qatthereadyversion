<?php
require 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE users");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
