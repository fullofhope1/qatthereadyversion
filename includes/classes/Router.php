<?php
/**
 * includes/classes/Router.php
 * 
 * Manages obfuscated route mapping for security.
 */

class Router {
    private static $mapping = [
        'adm-close' => 'closing.php',
        'src-01'    => 'sourcing.php',
        'usr-mgr'   => 'customers.php',
        'rep-gen'   => 'reports.php',
        'exp-tr'    => 'expenses.php',
        'db-stat'   => 'debts.php',
        // Add more mappings here. You can use random strings if you prefer.
        // 'x9f2'   => 'closing.php', 
    ];

    /**
     * Resolves an obfuscated token to a real file path
     */
    public static function resolve($token) {
        return self::$mapping[$token] ?? null;
    }

    /**
     * Generates a secure URL for a given page
     */
    public static function getUrl($page) {
        $token = array_search($page, self::$mapping);
        if ($token) {
            return $token;
        }
        // Fallback to clean name if no mapping exists
        return str_replace('.php', '', $page);
    }
}
