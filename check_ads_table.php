<?php
require 'config/db.php';
$stmt = $pdo->query("DESCRIBE advertisements");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
