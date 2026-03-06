<?php
require 'config/db.php';
$files = glob("*.php");
$missing = [];
foreach ($files as $file) {
    if ($file == 'diagnostic.php') continue;
    $content = file_get_contents($file);
    // Check for login.php or signup.php references
    if (preg_match('/(login|signup)\.php/', $content, $matches)) {
        $missing[] = "File: $file contains reference to " . $matches[0];
    }
}

echo "Diagnostic Results:\n";
if (empty($missing)) echo "No legacy references found.\n";
else print_r($missing);

// Check if tables exist
try {
    $pdo->query("SELECT 1 FROM advertisements LIMIT 1");
    echo "Table 'advertisements' EXISTS.\n";
} catch (Exception $e) {
    echo "Table 'advertisements' MISSING: " . $e->getMessage() . "\n";
}

try {
    $pdo->query("SELECT 1 FROM qat_types LIMIT 1");
    echo "Table 'qat_types' EXISTS.\n";
} catch (Exception $e) {
    echo "Table 'qat_types' MISSING: " . $e->getMessage() . "\n";
}
