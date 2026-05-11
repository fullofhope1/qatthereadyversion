<?php
$pdo = new PDO('mysql:host=localhost;dbname=qat_erp', 'root', '');
$stmt = $pdo->query('SELECT id, unit_type, quantity_units FROM sales WHERE id = 88');
var_dump($stmt->fetch(PDO::FETCH_ASSOC));
