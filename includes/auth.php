<?php
if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 1. Auth Check Helper
function requireLogin()
{
    if (PHP_SAPI === 'cli') return;
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?auth=1");
        exit;
    }
}

// 2. Role Check Helper
function requireRole($role)
{
    if (PHP_SAPI === 'cli') return; // Bypass in CLI

    if ($_SESSION['role'] !== $role) {
        // If an admin tries to access a super_admin page
        if ($_SESSION['role'] === 'admin') {
            header("Location: dashboard.php");
        } else {
            // Should not happen, but fallback
            header("Location: index.php?auth=1");
        }
        exit;
    }
}

// 3. Sub-Role Check Helper (Granular Permissions)
function checkPermission()
{
    if (PHP_SAPI === 'cli') return true;
    
    // Support for Secure Routing Proxy (TARGET_PAGE)
    $current_page = $GLOBALS['TARGET_PAGE'] ?? basename($_SERVER['PHP_SELF']);
    
    $global_allowed = ['settings.php', 'logout.php', 'access_denied.php', 'index.php'];
    if (in_array($current_page, $global_allowed)) return true;

    if (isset($_SESSION['role'])) {
        // Normal Admin: Access to Admin-level pages
        if ($_SESSION['role'] === 'admin') return true;

        // Super Admin: Granular check
        if ($_SESSION['role'] === 'super_admin') {
            $sub_role = $_SESSION['sub_role'] ?? 'full';
            if ($sub_role === 'full') return true;

            // Forbidden map
            $forbidden = [
                'receiving' => ['dashboard.php', 'reports.php', 'debts.php', 'refunds.php', 'closing.php'],
                'verifier'  => ['dashboard.php', 'reports.php', 'debts.php', 'refunds.php', 'closing.php'],
                'seller'    => ['closing.php', 'reports.php'],
                'accountant' => ['dashboard.php', 'closing.php'],
                'partner'   => ['dashboard.php', 'closing.php', 'sales.php', 'customers.php']
            ];

            if (isset($forbidden[$sub_role]) && in_array($current_page, $forbidden[$sub_role])) {
                return false;
            }
            return true;
        }
    }

    return false; // Default
}

function requirePermission()
{
    if (!checkPermission()) {
        header("Location: access_denied.php");
        exit;
    }
}

function getHomeLink()
{
    if (!isset($_SESSION['user_id'])) return 'index.php';
    if ($_SESSION['role'] === 'user') return 'index.php';
    if ($_SESSION['role'] === 'admin') return 'sourcing.php'; 

    $sub_role = $_SESSION['sub_role'] ?? 'full';
    $permissions = [
        'full' => 'dashboard.php',
        'receiving' => 'purchases.php',
        'verifier' => 'purchases.php',
        'reports' => 'reports.php',
        'sales_debts' => 'sales.php',
        'seller' => 'sales.php',
        'accountant' => 'whatsapp_statements.php',
        'partner' => 'reports.php'
    ];

    return $permissions[$sub_role] ?? 'settings.php';
}
