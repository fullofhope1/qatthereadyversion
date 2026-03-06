<?php
// add_media_columns.php
require 'config/db.php';

try {
    // 1. Add media_path to qat_types
    // First check if it exists
    $stmt = $pdo->query("SHOW COLUMNS FROM qat_types LIKE 'media_path'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE qat_types ADD COLUMN media_path VARCHAR(255) DEFAULT NULL");
        echo "✅ Added 'media_path' to 'qat_types'.\n";
    } else {
        echo "ℹ️ Column 'media_path' already exists in 'qat_types'.\n";
    }

    // 2. Add media_path to advertisements
    $stmt = $pdo->query("SHOW COLUMNS FROM advertisements LIKE 'media_path'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE advertisements ADD COLUMN media_path VARCHAR(255) DEFAULT NULL");
        echo "✅ Added 'media_path' to 'advertisements'.\n";
    } else {
        echo "ℹ️ Column 'media_path' already exists in 'advertisements'.\n";
    }
} catch (Exception $e) {
    die("❌ Error during migration: " . $e->getMessage());
}
