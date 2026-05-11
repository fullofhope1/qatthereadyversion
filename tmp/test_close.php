<?php
require 'config/db.php';
require_once 'includes/Autoloader.php';
require_once 'includes/auto_close.php';

$today = '2026-04-14';
echo "Manually triggering close for $today...\n";

try {
    trigger_auto_closing($pdo, $today, true);
    echo "Processing complete.\n";
} catch (Exception $e) {
    echo "CRASH: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

$stmt = $pdo->query("SELECT status FROM purchases WHERE id = 38");
echo "\nStatus for id 38 is now: " . $stmt->fetchColumn() . "\n";
