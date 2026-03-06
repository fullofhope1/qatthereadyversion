<?php
require 'config/db.php';

try {
    // Step 1: Restore all soft-deleted types
    $restored = $pdo->exec("UPDATE qat_types SET is_deleted = 0 WHERE is_deleted = 1");

    // Step 2: Check current types
    $all_before = $pdo->query("SELECT name FROM qat_types ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

    // Step 3: Ensure all 7 types exist - insert if missing
    $required_types = [
        'جمام سمين',
        'جمام قصار',
        'جمام كالف',
        'جمام نقوة',
        'صدور عادي',
        'قطل',
        'صدور نقوة',
    ];

    $inserted = [];
    foreach ($required_types as $name) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM qat_types WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO qat_types (name, description, is_deleted) VALUES (?, '', 0)")->execute([$name]);
            $inserted[] = $name;
        }
    }

    // Step 4: Show current state
    $all_after = $pdo->query("SELECT id, name, is_deleted FROM qat_types ORDER BY name")->fetchAll();

    echo "<h2>✅ Fix Complete</h2>";
    echo "<p>Restored " . $restored . " soft-deleted rows.</p>";
    if ($inserted) {
        echo "<p><strong>Inserted new types:</strong> " . implode(', ', $inserted) . "</p>";
    }

    echo "<h3>All Types in Database (" . count($all_after) . "):</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>is_deleted</th></tr>";
    foreach ($all_after as $t) {
        $style = $t['is_deleted'] ? 'color:red' : 'color:green';
        echo "<tr><td>{$t['id']}</td><td style='$style'>{$t['name']}</td><td>{$t['is_deleted']}</td></tr>";
    }
    echo "</table>";
    echo "<br><a href='sales.php'>→ Go to Sales</a> | <a href='sourcing.php'>→ Go to Sourcing</a>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
