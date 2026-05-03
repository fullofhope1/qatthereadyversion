<?php
/**
 * gateway.php
 * 
 * Acts as a secure front-controller for obfuscated routes.
 */

require_once 'includes/Autoloader.php';
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['route'] ?? '';
$targetFile = Router::resolve($token);

if ($targetFile && file_exists($targetFile)) {
    // Set a flag so header.php knows the real page being accessed
    $GLOBALS['TARGET_PAGE'] = $targetFile;
    include $targetFile;
} else {
    // If no route matches, check if it's a direct file access (for local dev) or redirect to index
    if ($token && file_exists($token . ".php")) {
        $GLOBALS['TARGET_PAGE'] = $token . ".php";
        include $token . ".php";
    } else {
        header("Location: index.php");
        exit;
    }
}
