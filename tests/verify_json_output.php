<?php
// tests/verify_json_output.php
ob_start();
include __DIR__ . '/../requests/add_provider.php';
$output = ob_get_clean();

if (strpos($output, '<br />') !== false || strpos($output, '<b>') !== false) {
    echo "FAIL: Output contains HTML error tags.\n";
    echo "Output: " . $output . "\n";
} else {
    echo "PASS: Output is clean.\n";
    echo "Response: " . $output . "\n";
}
