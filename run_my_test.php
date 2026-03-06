<?php
try {
    require 'tests/test_leftovers_lifecycle.php';
} catch (Throwable $e) {
    echo "\nTHE_ERROR_DETAILS: " . $e->getMessage() . "\n";
}
