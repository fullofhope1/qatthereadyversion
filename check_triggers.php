<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW TRIGGERS LIKE 'users'");
$triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($triggers)) {
    echo "No triggers found on users table.\n";
} else {
    print_r($triggers);
}
