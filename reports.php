<?php
require 'config/db.php';
include 'includes/header.php';

// Helper for Arabic Day Names
function getArabicDay($date)
{
    if (!$date) return '';
    $days = [
        'Sunday' => 'الأحد',
        'Monday' => 'الاثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
        'Saturday' => 'السبت'
    ];
    $dayName = date('l', strtotime($date));
    return $days[$dayName] ?? $dayName;
}

// Filter Inputs
$reportType = $_GET['report_type'] ?? 'Daily';
$view = $_GET['view'] ?? 'Summary'; // New Default: Summary
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$year = $_GET['year'] ?? date('Y');

// Determine Date Range
$params = [];
if ($reportType === 'Monthly') {
    $whereSQL_Sales = "WHERE DATE_FORMAT(sale_date, '%Y-%m') = ?";
    $whereSQL_Purch = "WHERE DATE_FORMAT(purchase_date, '%Y-%m') = ?";
    $whereSQL_Exp = "WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?";
    $params = [$month];
} elseif ($reportType === 'Yearly') {
    $whereSQL_Sales = "WHERE YEAR(sale_date) = ?";
    $whereSQL_Purch = "WHERE YEAR(purchase_date) = ?";
    $whereSQL_Exp = "WHERE YEAR(expense_date) = ?";
    $params = [$year];
} else { // Daily
    $whereSQL_Sales = "WHERE sale_date = ?";
    $whereSQL_Purch = "WHERE purchase_date = ?";
    $whereSQL_Exp = "WHERE expense_date = ?";
    $params = [$date];
}

// 0. Provider filtering for detail views
$providerRepo = new ProviderRepository($pdo);
$provider_id = $_GET['provider_id'] ?? null;
$providersWithSales = $providerRepo->getWithSales();

// Additional common filters
if ($reportType === 'Monthly') {
    $whereSQL_Dep = "WHERE DATE_FORMAT(deposit_date, '%Y-%m') = ?";
    $whereSQL_Pay = "WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?";
} elseif ($reportType === 'Yearly') {
    $whereSQL_Dep = "WHERE YEAR(deposit_date) = ?";
    $whereSQL_Pay = "WHERE YEAR(payment_date) = ?";
} else {
    $whereSQL_Dep = "WHERE deposit_date = ?";
    $whereSQL_Pay = "WHERE payment_date = ?";
}

// --- CORE DATA FETCHING ---
// Initialization via Clean Architecture
$reportRepo = new ReportRepository($pdo);
$service = new ReportService($reportRepo);

// Current user ID for filtering user-specific data (expenses, deposits)
$current_user_id = $_SESSION['user_id'] ?? null;

// --- CORE DATA FETCHING ---
$overview = $service->getOverviewData($reportType, $date, $month, $year, $current_user_id);
$totalSales = $overview['total_sales'];
$totalPurchases = $overview['total_purchases'];
$totalExpenses = $overview['total_expenses'];
$totalDebt = $overview['total_debt'];
$overdueCount = $overview['overdue_count'];
$todayDue = $overview['today_due'];
$tomorrowDue = $overview['tomorrow_due'];
$listRefunds = $overview['refunds'];

// Tab-specific data
$listSales = ($view === 'Sales' || $view === 'Printable') ? $service->getDetailedViewData('Sales', $reportType, $date, $month, $year, $provider_id, $current_user_id) : [];
$listPurch = ($view === 'Receiving' || $view === 'Printable') ? $service->getDetailedViewData('Receiving', $reportType, $date, $month, $year, null, $current_user_id) : [];
$listExp = ($view === 'Expenses' || $view === 'Printable') ? $service->getDetailedViewData('Expenses', $reportType, $date, $month, $year, null, $current_user_id) : [];
$listWaste = ($view === 'Waste' || $view === 'Printable') ? $service->getDetailedViewData('Waste', $reportType, $date, $month, $year, null, $current_user_id) : [];

// --- SUMMARY & CASH CALCULATION ---
if (in_array($view, ['Summary', 'Printable', 'Dashboard'])) {
    $cashSummary = $service->getCashSummary($reportType, $date, $month, $year, $current_user_id);
    $remainingCash = $cashSummary['remaining_cash'];
    $cashSales = $cashSummary['cash_sales'];
    $collectedPayments = $cashSummary['collected_payments'];
    $depositsYER = $cashSummary['deposits_yer'];
    $cashRefunds = $cashSummary['cash_refunds'];

    if ($view === 'Dashboard') {
        $dashStats = $service->getDashboardStats();
        $totalReceivables = $dashStats['total_receivables'];
        $inventoryValue = $dashStats['inventory_value'];
        $electronicBalance = $dashStats['electronic_balance'];

        // Corrected Net Worth Formula (Includes Bank/Electronic Funds)
        $netWorth = $totalReceivables + $remainingCash + $inventoryValue + $electronicBalance;

        $netCash = $remainingCash;
        $netProfit = ($totalSales - $cashRefunds) - $totalPurchases - $totalExpenses;
    }
}
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        --accent-color: #ffc107;
        --royal-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .report-card-header {
        background: var(--primary-gradient);
        border-radius: 20px 20px 0 0 !important;
        padding: 2rem 2.5rem;
        position: relative;
        overflow: hidden;
    }

    .report-card-header::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
    }

    .report-nav-pills {
        position: relative;
        z-index: 20;
        /* Ensure tabs are clickable over the overlapping filter container */
    }

    .report-nav-pills .nav-link {
        border-radius: 12px;
        padding: 0.6rem 1.5rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        margin-bottom: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.8);
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .report-nav-pills .nav-link.active {
        background: var(--accent-color) !important;
        color: #000 !important;
        box-shadow: 0 8px 20px rgba(255, 193, 7, 0.4);
        border-color: var(--accent-color);
        transform: translateY(-2px);
    }

    .report-nav-pills .nav-link:not(.active):hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.3);
    }

    .filter-pill-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 18px;
        padding: 1.25rem;
        margin-top: -1.5rem;
        box-shadow: var(--royal-shadow);
        border: 1px solid rgba(255, 255, 255, 1);
        z-index: 10;
        position: relative;
    }

    .report-title-icon {
        width: 55px;
        height: 55px;
        background: rgba(255, 255, 255, 0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        margin-left: 20px;
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .btn-update-report {
        background: #ffc107;
        color: #000;
        font-weight: 700;
        border: none;
        padding: 0.6rem 2rem;
        border-radius: 50px;
        transition: all 0.3s;
    }

    .btn-update-report:hover {
        background: #e0a800;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
</style>

<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
            <!-- Header section with title and global nav -->
            <div class="report-card-header text-white">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <div class="report-title-icon">
                            <i class="fas fa-chart-line fs-4 text-warning"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">التقارير المالية</h3>
                            <p class="mb-0 text-white-50 small">متابعة الأداء، المبيعات، والنشاط المالي</p>
                        </div>
                    </div>

                    <ul class="nav nav-pills report-nav-pills d-none d-md-flex">
                        <?php
                        $tabs = [
                            'Summary' => ['label' => 'الخلاصة الكلية', 'icon' => 'fa-file-invoice'],
                            'Sales' => ['label' => 'المبيعات', 'icon' => 'fa-shopping-cart'],
                            'Receiving' => ['label' => 'المشتريات', 'icon' => 'fa-truck'],
                            'Waste' => ['label' => 'التوالف (البقايا)', 'icon' => 'fa-trash-alt'],
                            'Expenses' => ['label' => 'المصاريف', 'icon' => 'fa-wallet'],
                            'Refunds' => ['label' => 'المرتجعات', 'icon' => 'fa-undo'],
                            'Debts' => ['label' => 'الديون', 'icon' => 'fa-file-invoice-dollar'],
                            'unknown_transfers' => ['label' => 'حوالات مجهولة', 'icon' => 'fa-question-circle'],
                            'Printable' => ['label' => 'التقرير المطبوع', 'icon' => 'fa-print'],
                            'Dashboard' => ['label' => 'تحليل الأداء', 'icon' => 'fa-tachometer-alt'],
                        ];
                        foreach ($tabs as $k => $v):
                            $active = ($view === $k);
                        ?>
                            <li class="nav-item me-2">
                                <a class="nav-link <?= $active ? 'active' : 'text-white' ?>" href="?view=<?= $k ?>&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>">
                                    <i class="fas <?= $v['icon'] ?> me-2"></i> <?= $v['label'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Filters Section (Overlapping) -->
                <div class="filter-pill-container no-print">
                    <form class="row align-items-end g-3" method="GET">
                        <input type="hidden" name="view" value="<?= $view ?>">

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1 px-2">📅 نوع التقرير</label>
                            <select name="report_type" class="form-select border-0 bg-light fw-bold py-2 shadow-sm" id="repType" onchange="toggleInputs()" style="border-radius: 12px;">
                                <option value="Daily" <?= $reportType == 'Daily' ? 'selected' : '' ?>>تقرير يومي</option>
                                <option value="Monthly" <?= $reportType == 'Monthly' ? 'selected' : '' ?>>تقرير شهري</option>
                                <option value="Yearly" <?= $reportType == 'Yearly' ? 'selected' : '' ?>>تقرير سنوي</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1 px-2">⏳ الفترة الزمنية</label>
                            <div id="div_daily" class="<?= $reportType != 'Daily' ? 'd-none' : '' ?>">
                                <input type="date" name="date" class="form-control border-0 bg-light py-2 shadow-sm" value="<?= $date ?>" style="border-radius: 12px;">
                            </div>
                            <div id="div_monthly" class="<?= $reportType != 'Monthly' ? 'd-none' : '' ?>">
                                <input type="month" name="month" class="form-control border-0 bg-light py-2 shadow-sm" value="<?= $month ?>" style="border-radius: 12px;">
                            </div>
                            <div id="div_yearly" class="<?= $reportType != 'Yearly' ? 'd-none' : '' ?>">
                                <select name="year" class="form-select border-0 bg-light py-2 shadow-sm" style="border-radius: 12px;">
                                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <?php if ($view === 'Sales'): ?>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1 px-2">👨‍🌾 المورد</label>
                                <select name="provider_id" class="form-select border-0 bg-light py-2 shadow-sm" style="border-radius: 12px;">
                                    <option value="">كل الموردين</option>
                                    <?php foreach ($providersWithSales as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $provider_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-2">
                            <button class="btn btn-update-report w-100 py-2 shadow-sm" style="border-radius: 12px;">
                                <i class="fas fa-sync-alt me-1"></i> تصفية
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Mobile Navigation (shown only on small screens) -->
            <div class="d-md-none bg-light p-2 border-top">
                <select class="form-select border-0" onchange="window.location.href=this.value">
                    <?php foreach ($tabs as $k => $v): ?>
                        <option value="?view=<?= $k ?>&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>" <?= $view === $k ? 'selected' : '' ?>>
                            <?= $v['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-0">
    <?php
    $viewPath = "includes/reports/view_" . strtolower($view) . ".php";
    if ($view === 'Refunds') {
    ?>
        <div class="card report-table-card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-undo me-2 text-warning"></i>
                    سجل المرتجعات التفصيلي
                </h5>
                <span class="badge bg-light text-muted fw-normal"><?= count($listRefunds) ?> عملية</span>
            </div>
            <div class="card-body p-0">
                <!-- Search box (#27) -->
                <div class="p-3 pb-0">
                    <input type="text" id="refundReportSearch" class="form-control" placeholder="بحث باسم العميل أو السبب..." oninput="filterRefundReport()">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle report-table">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>نوع المرتجع</th>
                                <th>السبب (البيان)</th>
                                <th class="text-end">المبلغ (ريال)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listRefunds as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($r['cust_name'] ?? 'عميل سفري') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                            <?= htmlspecialchars($r['refund_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars($r['reason']) ?>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        <?= number_format($r['amount']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($listRefunds)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-history fs-1 d-block mb-3 opacity-25"></i>
                                        لا توجد مرتجعات مسجلة في هذه الفترة.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php
    } elseif (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo "<div class='alert alert-info'>يرجى اختيار قسم لعرض بياناته</div>";
    }
    ?>
</div>

<script>
    function toggleInputs() {
        const type = document.getElementById('repType').value;
        const divs = ['div_daily', 'div_monthly', 'div_yearly'];
        divs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('d-none');
        });

        if (type === 'Daily') {
            const d = document.getElementById('div_daily');
            if (d) d.classList.remove('d-none');
        } else if (type === 'Monthly') {
            const m = document.getElementById('div_monthly');
            if (m) m.classList.remove('d-none');
        } else if (type === 'Yearly') {
            const y = document.getElementById('div_yearly');
            if (y) y.classList.remove('d-none');
        }
    }

    // Safety Print function for the button in the sub-view
    function triggerPrint() {
        console.log("Print triggered...");
        try {
            window.print();
        } catch (e) {
            console.error("Print failed:", e);
            alert("حدث خطأ أثناء محاولة الطباعة. يرجى استخدام Ctrl + P يدوياً.");
        }
    }

    function filterRefundReport() {
        const term = document.getElementById('refundReportSearch').value.toLowerCase();
        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            if (row.cells.length < 4) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>

<?php include 'includes/footer.php'; ?>