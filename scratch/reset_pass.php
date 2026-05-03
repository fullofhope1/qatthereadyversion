<?php
require 'config/db.php';
$password = password_hash('123456', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->execute([$password, 'superadmin']);
echo "Password for 'superadmin' reset to '123456'\n";

$stmt->execute([$password, 'admin']);
echo "Password for 'admin' reset to '123456'\n";
