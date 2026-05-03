<?php
/**
 * gateway.php
 * 
 * Acts as a secure front-controller for obfuscated routes.
 */

require_once 'includes/Autoloader.php';
require_once 'config/db.php';

$token = $_GET['route'] ?? '';
$targetFile = Router::resolve($token);

if ($targetFile && file_exists($targetFile)) {
    // Before including, we might want to set some flags or handle specific logic
    include $targetFile;
} else {
    // If no route matches, fall back to index.php or 404
    include 'index.php';
}
