<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Not Set') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'Not Set') . "\n";
echo "Sub-Role: " . ($_SESSION['sub_role'] ?? 'Not Set') . "\n";
