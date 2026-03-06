<?php
require 'config/db.php';

try {
    // Fix user ID 1 (super admin)
    $stmt = $pdo->prepare("UPDATE users SET role = 'super_admin' WHERE id = 1");
    $stmt->execute();

    // Also ensure any other users that should be super admins are set
    $stmt = $pdo->prepare("UPDATE users SET role = 'super_admin' WHERE username = 'super admin'");
    $stmt->execute();

    echo "Successfully updated super admin role.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
