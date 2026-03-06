<?php
require 'config/db.php';
$output = "";
$stmt = $pdo->query('SELECT id, username, role, HEX(role) as r_hex, sub_role, HEX(sub_role) as s_hex FROM users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= "ID: {$row['id']} | User: [{$row['username']}] | Role: [{$row['role']}] | RoleHex: [{$row['r_hex']}] | SubRole: [{$row['sub_role']}] | SubHex: [{$row['s_hex']}]\n";
}
$output .= "\n--- DB Variable Check ---\n";
$stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
$output .= var_export($stmt->fetch(), true) . "\n";
file_put_contents('audit_log_v2.txt', $output);
echo "Audit complete. Check audit_log_v2.txt\n";
