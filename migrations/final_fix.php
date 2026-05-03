<?php
require_once dirname(__DIR__) . '/config/db.php';
try {
    // 1. Add column to leftovers if missing
    $pdo->exec("ALTER TABLE leftovers ADD COLUMN IF NOT EXISTS created_by INT NULL");
    
    // 2. Set default creator for old records
    $firstSuper = $pdo->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1")->fetchColumn();
    if ($firstSuper) {
        $pdo->exec("UPDATE leftovers SET created_by = $firstSuper WHERE created_by IS NULL");
        $pdo->exec("UPDATE sales SET created_by = $firstSuper WHERE created_by IS NULL");
    }
    
    echo "Migration completed successfully. Columns in leftovers: ";
    $cols = $pdo->query("DESCRIBE leftovers")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
