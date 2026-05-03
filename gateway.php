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
    // Set a flag so header.php knows the real page being accessed
    $GLOBALS['TARGET_PAGE'] = $targetFile;
    include $targetFile;
} else {
    // If no route matches, fall back to index.php or 404
    include 'index.php';
}
