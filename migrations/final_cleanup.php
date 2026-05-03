<?php
require 'config/db.php';
try {
    // 1. Add column to leftovers if missing
    $pdo->exec("ALTER TABLE leftovers ADD COLUMN IF NOT EXISTS created_by INT NULL");
    
    // 2. Set default creator for old records (Assuming first super_admin as owner)
    $firstSuper = $pdo->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1")->fetchColumn();
    if ($firstSuper) {
        $pdo->exec("UPDATE leftovers SET created_by = $firstSuper WHERE created_by IS NULL");
        $pdo->exec("UPDATE sales SET created_by = $firstSuper WHERE created_by IS NULL");
        $pdo->exec("UPDATE expenses SET created_by = $firstSuper WHERE created_by IS NULL");
        $pdo->exec("UPDATE staff SET created_by = $firstSuper WHERE created_by IS NULL");
        $pdo->exec("UPDATE purchases SET created_by = $firstSuper WHERE created_by IS NULL");
    }
    
    echo "Migration and data cleanup completed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
