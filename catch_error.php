<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/db.php';
require 'includes/auto_close.php';

$target = '2026-02-16';
echo "ATTEMPTING CLOSE FOR $target...\n";
try {
    trigger_auto_closing($pdo, $target);
    echo "SUCCESS!\n";
} catch (Exception $e) {
    echo "CRITICAL FAILURE: " . $e->getMessage() . "\n";
    echo "SQL: " . $e->getTraceAsString() . "\n";
}
