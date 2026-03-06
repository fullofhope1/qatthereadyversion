<?php
require 'config/db.php';
$pdo->exec("UPDATE users SET role = 'admin' WHERE username = 'admin'");
echo "Admin role restored for user 'admin'.\n";
