<?php
require 'config/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM qat_types LIKE 'is_deleted'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE qat_types ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        echo "✅ Column 'is_deleted' added to 'qat_types' table.\n";
    } else {
        echo "ℹ️ Column 'is_deleted' already exists in 'qat_types'.\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
