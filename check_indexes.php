<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW INDEX FROM customers");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
