<?php
require 'config/db.php';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in DB: " . implode(", ", $tables) . "\n\n";

$stmt = $pdo->query("DESCRIBE users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

// Check if 'user' role exists in enum
$user_col = array_filter($cols, function ($c) {
    return $c['Field'] == 'role';
});
$user_col = array_values($user_col)[0];
if (strpos($user_col['Type'], "'user'") !== false) {
    echo "\n'user' role EXISTS in enum.\n";
} else {
    echo "\n'user' role MISSING from enum. Fixing...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'super_admin', 'user') NOT NULL");
    echo "Role enum updated.\n";
}

// Check for display_name and phone
$fields = array_column($cols, 'Field');
if (!in_array('display_name', $fields)) {
    echo "Adding display_name...\n";
    $pdo->exec("ALTER TABLE users ADD COLUMN display_name VARCHAR(255) AFTER username");
}
if (!in_array('phone', $fields)) {
    echo "Adding phone...\n";
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER display_name");
}

echo "\nDiagnostic cleanup complete.\n";
