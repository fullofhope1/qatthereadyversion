<?php
// add_media_to_purchases.php
require 'config/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM purchases LIKE 'media_path'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN media_path VARCHAR(255) DEFAULT NULL AFTER status");
        echo "✅ Added 'media_path' to 'purchases'.\n";
    } else {
        echo "ℹ️ Column 'media_path' already exists in 'purchases'.\n";
    }
} catch (Exception $e) {
    die("❌ Error during migration: " . $e->getMessage());
}
