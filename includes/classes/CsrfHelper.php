<?php
/**
 * includes/classes/CsrfHelper.php
 * 
 * Simple Cross-Site Request Forgery (CSRF) protection.
 */

class CsrfHelper {
    /**
     * Generates a CSRF token and stores it in the session
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validates the provided CSRF token against the session
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Helper to generate a hidden input field for forms
     */
    public static function insertTokenField() {
        echo '<input type="hidden" name="csrf_token" value="' . self::getToken() . '">';
    }
}
