<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, username, role, LENGTH(role) as r_len, sub_role, LENGTH(sub_role) as s_len FROM users WHERE id = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($row);
