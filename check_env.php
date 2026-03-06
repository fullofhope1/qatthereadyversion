<?php
require 'config/db.php';
echo "<h1>Diagnostic Info</h1>";
echo "Database: " . $dbname . "<br>";
try {
    $stmt = $pdo->query("SELECT DATABASE()");
    echo "Connected to Database: " . $stmt->fetchColumn() . "<br>";

    $stmt = $pdo->query("DESCRIBE qat_types");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Columns in qat_types:</h3><ul>";
    foreach ($columns as $col) {
        echo "<li>{$col['Field']} ({$col['Type']})</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
