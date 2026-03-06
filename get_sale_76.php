<?php
require 'config/db.php';
$res = $pdo->query("SELECT * FROM sales WHERE id=76")->fetch(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
