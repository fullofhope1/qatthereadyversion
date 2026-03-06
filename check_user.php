<?php
require 'config/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'alqadri'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);
