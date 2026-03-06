<?php
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['sub_role'] = 'full';
$_GET['action'] = 'list';
ob_start();
chdir('requests');
require 'manage_users.php';
$output = ob_get_clean();
echo "Output: " . $output . "\n";
$data = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "JSON Success!\n";
    print_r($data);
}
