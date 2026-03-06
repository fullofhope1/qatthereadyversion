<?php
require 'config/db.php';
$stmt = $pdo->query("DESCRIBE qat_types");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
