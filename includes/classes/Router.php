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
        'prov-mgr'  => 'providers.php',
        'prov-stat' => 'provider_statements.php',
        'stf-mgr'   => 'staff.php',
        'stf-det'   => 'staff_details.php',
        'stf-stat'  => 'staff_statements.php',
        'att-mgr'   => 'attendance.php',
        'dash'      => 'dashboard.php',
        'set-cfg'   => 'settings.php',
        'ad-mgr'    => 'manage_ads.php',
        'prod-mgr'  => 'manage_products.php',
        'ref-mgr'   => 'refunds.php',
        'adm-rep'   => 'admin_report.php',
        'unk-tr'    => 'unknown_transfers.php',
        'ret-mgr'   => 'returns.php'
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
