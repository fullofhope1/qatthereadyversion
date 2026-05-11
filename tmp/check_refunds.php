<?php
require 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE refunds");
    var_export($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
