<?php
require_once 'Autoloader.php';
// Ensure session is started (auth.php does this, but safely)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// HTTP Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Basic page name
$current_page = basename($_SERVER['PHP_SELF']);

// Pages allowed without login
$public_pages = ['index.php'];

// Include auth helpers
require_once __DIR__ . '/auth.php';

// Redirect if not logged in AND requesting a private page
if (!in_array($current_page, $public_pages)) {
    requireLogin();
}

// Redirect if logged in as 'user' but trying to access Admin pages
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user' && !in_array($current_page, $public_pages)) {
    // Normal users ONLY allowed in public pages AND settings.php
    $allowed_for_user = array_merge($public_pages, ['settings.php']);
    if (!in_array($current_page, $allowed_for_user)) {
        header("Location: index.php");
        exit;
    }
}

// For Admins -> Restrict access to some pages if not super_admin
$admin_allowed_pages = ['sourcing.php', 'providers.php', 'expenses.php', 'admin_report.php', 'settings.php', 'logout.php', 'dashboard.php', 'refunds.php', 'manage_ads.php', 'manage_products.php', 'staff.php', 'staff_details.php'];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && !in_array($current_page, $admin_allowed_pages) && !in_array($current_page, $public_pages)) {
    header("Location: access_denied.php");
    exit;
}

// For Super Admins -> Restrict access based on sub_role
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' && !in_array($current_page, $public_pages)) {
    requirePermission();
}

// Ensure 24-hour cycle logic is applied automatically
require_once __DIR__ . '/auto_close.php';
trigger_auto_closing($pdo);

// Define dynamic home link based on role
$home_link = 'index.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] !== 'user') {
        $home_link = 'dashboard.php';

        // If super_admin and doesn't have dashboard permission, fall back
        if ($_SESSION['role'] === 'super_admin') {
            $sub_role = $_SESSION['sub_role'] ?? 'full';
            $no_dash = ['reports', 'sales_debts', 'seller', 'accountant', 'partner'];
            if (in_array($sub_role, $no_dash)) {
                $home_link = 'settings.php'; // Or just index.php
            }
        }
    }
}

// Admin badges: count today's sourcing entries and today's expenses
$admin_sourcing_badge = 0;
$admin_expenses_badge = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $today_date = date('Y-m-d');
    $bs = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE purchase_date = ? AND is_received = 0");
    $bs->execute([$today_date]);
    $admin_sourcing_badge = (int)$bs->fetchColumn();

    $be = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE expense_date = ?");
    $be->execute([$today_date]);
    $admin_expenses_badge = (int)$be->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>القادري و ماجد - لأجود أنواع القات</title>
    <!-- Google Fonts: Cairo & Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap 5 JS (loaded in head to ensure it's ready before any page scripts) -->
    <script src="public/js/bootstrap.bundle.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Driver.js for Interactive Tour -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@0.9.8/dist/driver.min.css">

    <link rel="stylesheet" href="public/css/style.css">
    <style>
        :root {
            --brand-gradient: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF8C00 100%);
            --brand-dark: #1a1a1a;
        }

        body {
            font-family: 'Cairo', 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar-brand {
            font-family: 'Cairo', sans-serif;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .brand-text {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
        }

        /* Mobile Fixes for Profile Dropdown */
        @media (max-width: 768px) {
            .navbar-nav {
                margin-top: 15px;
            }
            .dropdown-menu-end {
                position: static !important; /* Let it flow in the navbar on mobile instead of floating out of bounds */
                width: 100% !important;
                border: none !important;
                background: rgba(255, 255, 255, 0.05) !important;
                box-shadow: none !important;
                color: white !important;
            }
            .dropdown-item {
                color: rgba(255, 255, 255, 0.8) !important;
            }
            .dropdown-item:hover {
                background: rgba(255, 255, 255, 0.1) !important;
            }
            .dropdown-divider {
                border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
            }
        }

        /* Print logic */
        @media print {
            body { background: white !important; color: black !important; }
            .no-print, .btn, .navbar, .nav, .breadcrumb, .alert, .no-print * { 
                display: none !important; 
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .card { border: none !important; box-shadow: none !important; background: transparent !important; }
            .container-fluid, .container { width: 100% !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
            
            .print-header { display: block !important; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .print-header h1 { font-size: 24pt; font-weight: 900; margin-bottom: 5px; }
            .print-header p { font-size: 10pt; color: #555; }
            
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
            th, td { border: 1px solid #ddd !important; padding: 8px !important; }
            th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
            .text-danger { color: #dc3545 !important; }
            .text-success { color: #198754 !important; }
            .badge { border: 1px solid #ccc !important; color: black !important; background: transparent !important; }
            
            @page { size: A4; margin: 1.5cm; }
        }
        
        .print-header { display: none; }

        .premium-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .premium-card:hover {
            transform: translateY(-5px);
        }

        /* Floating Help Button */
        #help_trigger {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 55px;
            height: 55px;
            background: var(--brand-gradient);
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            cursor: pointer;
            z-index: 9999;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 2px solid #fff;
        }

        #help_trigger:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        .driver-popover {
            font-family: 'Cairo', sans-serif !important;
            direction: rtl !important;
            text-align: right !important;
            z-index: 1000000000 !important;
        }
        .driver-popover-title {
            color: var(--brand-dark) !important;
            font-weight: 900 !important;
        }
        .driver-stage-no-animation {
            z-index: 999999998 !important;
        }
        #driver-page-overlay {
            z-index: 999999997 !important;
        }
        .driver-highlighted-element {
            z-index: 999999999 !important;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= $home_link ?>">
                <img src="logo.jpg" alt="Logo" class="me-2 rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid var(--brand-gradient);">
                <span class="brand-text">القادري و ماجد</span>
                <span class="ms-2 small d-none d-sm-inline text-light opacity-75">لأجود أنواع القات</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                        <!-- Admin: Sourcing + Expenses + Staff -->
                        <?php
                        // Helper for admin active nav
                        function adminNavClass($page, $current, $color = 'warning')
                        {
                            $base = "nav-link text-{$color} fw-bold position-relative";
                            if ($page === $current) $base .= " border-bottom border-{$color} border-3 bg-white bg-opacity-10 rounded px-2";
                            return $base;
                        }
                        ?>
                        <li class="nav-item">
                            <a class="<?= adminNavClass('sourcing.php', $current_page, 'warning') ?>" href="sourcing.php">
                                <i class="fas fa-truck-loading me-1"></i> التوريد
                                <?php if ($admin_sourcing_badge > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;"><?= $admin_sourcing_badge ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="<?= adminNavClass('providers.php', $current_page, 'white') ?>" href="providers.php">
                                <i class="fas fa-users-cog me-1"></i> الرعية
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="<?= adminNavClass('expenses.php', $current_page, 'info') ?>" href="expenses.php">
                                <i class="fas fa-wallet me-1"></i> المصاريف
                                <?php if ($admin_expenses_badge > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;"><?= $admin_expenses_badge ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="<?= adminNavClass('staff.php', $current_page, 'light') ?>" href="staff.php">
                                <i class="fas fa-user-hard-hat me-1"></i> الموظفين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="<?= adminNavClass('admin_report.php', $current_page, 'success') ?>" href="admin_report.php">
                                <i class="fas fa-chart-bar me-1"></i> التقارير
                            </a>
                        </li>
                    <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'super_admin'): ?>
                        <!-- Super Admin Menu -->
                        <?php
                        if (!function_exists('navActive')) {
                            function navActive($page, $current)
                            {
                                $base = 'nav-link';
                                if ($page === $current) $base .= ' active fw-bold border-bottom border-warning border-2 bg-white bg-opacity-10 rounded px-2';
                                return $base;
                            }
                        }
                        $sub_role = $_SESSION['sub_role'] ?? 'full';
                        $is_full = ($sub_role === 'full');
                        ?>
                        <?php if ($is_full || $sub_role === 'receiving' || $sub_role === 'verifier'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive($home_link, $current_page) ?>" href="<?= $home_link ?>"><i class="fas fa-home me-1"></i> الرئيسية</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'sales_debts' || $sub_role === 'seller'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('sales.php', $current_page) ?>" href="sales.php"><i class="fas fa-shopping-cart me-1"></i> المبيعات</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'receiving' || $sub_role === 'verifier'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('purchases.php', $current_page) ?>" href="purchases.php"><i class="fas fa-truck me-1"></i> استلام المشتريات</a></li>
                        <?php endif; ?>


                        <?php if ($is_full || $sub_role === 'sales_debts' || $sub_role === 'seller'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('customers.php', $current_page) ?>" href="customers.php"><i class="fas fa-users me-1"></i> العملاء</a></li>
                            <li class="nav-item"><a class="nav-link <?= navActive('debts.php', $current_page) ?>" href="debts.php"><i class="fas fa-file-invoice-dollar me-1"></i> الديون</a></li>
                        <?php endif; ?>

                        <?php if ($is_full): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('refunds.php', $current_page) ?>" href="refunds.php"><i class="fas fa-hand-holding-usd me-1"></i> التعويضات</a></li>
                            <li class="nav-item"><a class="nav-link <?= navActive('returns.php', $current_page) ?>" href="returns.php"><i class="fas fa-undo me-1"></i> المرتجعات</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'seller'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('staff.php', $current_page) ?>" href="staff.php"><i class="fas fa-user-tie me-1"></i> الموظفين</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'receiving' || $sub_role === 'sales_debts' || $sub_role === 'seller'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('expenses.php', $current_page) ?>" href="expenses.php"><i class="fas fa-wallet me-1"></i> المصاريف</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'reports'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('unknown_transfers.php', $current_page) ?>" href="unknown_transfers.php"><i class="fas fa-question-circle me-1"></i> تحويلات مجهولة</a></li>
                        <?php endif; ?>

                          <!-- Removed manual leftovers management -->

                        <?php if ($is_full || $sub_role === 'sales_debts' || $sub_role === 'seller'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('sales_leftovers_1.php', $current_page) ?>" href="sales_leftovers_1.php"><i class="fas fa-recycle me-1"></i> بيع أول</a></li>
                            <li class="nav-item"><a class="nav-link <?= navActive('sales_leftovers_2.php', $current_page) ?>" href="sales_leftovers_2.php"><i class="fas fa-history me-1"></i> بيع ثاني</a></li>
                        <?php endif; ?>

                        <?php if ($is_full): ?>
                            <li class="nav-item"><a class="nav-link text-danger fw-bold <?= navActive('closing.php', $current_page) ?>" href="closing.php"><i class="fas fa-calendar-check me-1"></i> إغلاق اليومية</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'reports' || $sub_role === 'accountant' || $sub_role === 'partner'): ?>
                            <li class="nav-item"><a class="nav-link <?= navActive('reports.php', $current_page) ?>" href="reports.php"><i class="fas fa-chart-line me-1"></i> التقارير</a></li>
                        <?php endif; ?>

                        <?php if ($is_full || $sub_role === 'accountant'): ?>
                            <li class="nav-item"><a class="nav-link text-success fw-bold <?= navActive('whatsapp_statements.php', $current_page) ?>" href="whatsapp_statements.php"><i class="fab fa-whatsapp me-1"></i> واتساب</a></li>
                        <?php endif; ?>

                        <?php if ($_SESSION['role'] === 'super_admin' && $is_full): ?>
                            <li class="nav-item border-start ms-2 ps-2"><a class="nav-link text-info fw-bold <?= navActive('manage_ads.php', $current_page) ?>" href="manage_ads.php"><i class="fas fa-ad me-1"></i> الإعلانات</a></li>
                            <li class="nav-item"><a class="nav-link text-info fw-bold <?= navActive('manage_products.php', $current_page) ?>" href="manage_products.php"><i class="fas fa-leaf me-1"></i> المنتجات</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center rounded-pill px-3" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-display="static">
                                <i class="fas fa-user-circle me-2 fs-5"></i>
                                <span class="d-md-inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                                <li class="px-3 py-2 border-bottom mb-2">
                                    <span class="text-muted small">مرحباً، </span><br>
                                    <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="settings.php">
                                        <i class="fas fa-cog me-2 text-secondary"></i> الإعدادات
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="index.php?auth=1" class="btn btn-sm btn-warning fw-bold rounded-pill px-3 shadow-sm">
                            <i class="fas fa-sign-in-alt me-1"></i> دخول
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">