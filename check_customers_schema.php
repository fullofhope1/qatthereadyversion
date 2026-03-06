<?php
require 'config/db.php';
$stmt = $pdo->query("DESC customers");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
