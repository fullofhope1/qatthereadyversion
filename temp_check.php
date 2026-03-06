<?php
require 'config/db.php';
$stmt = $pdo->query("DESC providers");
print_r($stmt->fetchAll());
