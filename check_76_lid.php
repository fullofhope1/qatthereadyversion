<?php
require 'config/db.php';
$lid = $pdo->query("SELECT leftover_id FROM sales WHERE id=76")->fetchColumn();
echo "Leftover ID for sale 76: " . ($lid ?: 'NULL') . "\n";
