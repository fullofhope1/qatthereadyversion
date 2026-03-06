<?php
if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Check if logged in
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
    if ($_SESSION['role'] === 'admin') return false; // Basic admins have their own logic in header.php

    $sub_role = $_SESSION['sub_role'] ?? 'full';
    if ($sub_role === 'full') return true;

    $permissions = [
        'reports' => ['reports.php', 'admin_report.php', 'dashboard.php', 'unknown_transfers.php'],
        'sales_debts' => ['sales.php', 'customers.php', 'debts.php', 'sales_leftovers.php', 'dashboard.php', 'customer_details.php', 'customer_statement.php', 'expenses.php', 'leftovers.php'],
        'receiving' => ['purchases.php', 'sourcing.php', 'inventory.php', 'dashboard.php', 'expenses.php'],
        // New specific roles
        'seller' => ['sales.php', 'customers.php', 'debts.php', 'staff.php', 'expenses.php', 'sales_leftovers.php', 'refunds.php', 'dashboard.php', 'customer_details.php', 'customer_statement.php'],
        'accountant' => ['whatsapp_statements.php', 'reports.php', 'admin_report.php', 'dashboard.php', 'unknown_transfers.php'],
        'partner' => ['reports.php', 'admin_report.php', 'dashboard.php'],
        'verifier' => ['purchases.php', 'sourcing.php', 'inventory.php', 'providers.php', 'dashboard.php']
    ];

    if (!isset($permissions[$sub_role])) return false;

    $current_page = basename($_SERVER['PHP_SELF']);
    return in_array($current_page, $permissions[$sub_role]);
}

function requirePermission()
{
    if (!checkPermission()) {
        header("Location: access_denied.php");
        exit;
    }
}
