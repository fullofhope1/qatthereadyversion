<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, category FROM expenses");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
foreach ($rows as $row) {
    if (empty(trim($row['category']))) {
        $upd = $pdo->prepare("UPDATE expenses SET category = 'أخرى' WHERE id = ?");
        $upd->execute([$row['id']]);
        $updated++;
    }
}
echo "Updated $updated rows manually.";
