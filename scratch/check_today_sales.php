<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, created_by, sale_date FROM sales WHERE sale_date = '2026-05-06'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
