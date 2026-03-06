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
$provider_id = $_GET['provider_id'] ?? null;
$providersWithSales = [];
try {
    $providersWithSales = $pdo->query("SELECT DISTINCT prov.id, prov.name FROM providers prov JOIN purchases p ON prov.id = p.provider_id JOIN sales s ON p.id = s.purchase_id ORDER BY prov.name")->fetchAll();
} catch (Exception $e) {
    // Fallback if schema differs
}

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
// 1. Overview Totals
$stmt = $pdo->prepare("SELECT SUM(price) FROM sales $whereSQL_Sales");
$stmt->execute($params);
$totalSales = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(net_cost) FROM purchases $whereSQL_Purch");
$stmt->execute($params);
$totalPurchases = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses $whereSQL_Exp");
$stmt->execute($params);
$totalExpenses = $stmt->fetchColumn() ?: 0;

// 4. Debt Statistics (Global - not dependent on report period as they are current status)
$totalDebt = $pdo->query("SELECT SUM(price - paid_amount) FROM sales WHERE is_paid = 0")->fetchColumn() ?: 0;
$overdueCount = $pdo->query("SELECT COUNT(*) FROM sales WHERE is_paid = 0 AND due_date < CURDATE()")->fetchColumn() ?: 0;
$todayDue = $pdo->query("SELECT SUM(price - paid_amount) FROM sales WHERE is_paid = 0 AND due_date = CURDATE()")->fetchColumn() ?: 0;
$tomorrowDue = $pdo->query("SELECT SUM(price - paid_amount) FROM sales WHERE is_paid = 0 AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)")->fetchColumn() ?: 0;

// Refunds
if ($reportType === 'Monthly') $whereSQL_Ref = "WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ?";
elseif ($reportType === 'Yearly') $whereSQL_Ref = "WHERE YEAR(r.created_at) = ?";
else $whereSQL_Ref = "WHERE DATE(r.created_at) = ?";

$stmt = $pdo->prepare("SELECT r.*, c.name as cust_name FROM refunds r LEFT JOIN customers c ON r.customer_id = c.id $whereSQL_Ref ORDER BY r.id DESC");
$stmt->execute($params);
$listRefunds = $stmt->fetchAll();

// --- TAB SPECIFIC DATA FETCHING ---
$listSales = [];
$listPurch = [];
$listExp = [];
$listWaste = [];

if ($view === 'Sales' || $view === 'Printable') {
    $where_sales_detail = $whereSQL_Sales;
    $params_sales = $params;
    if ($provider_id) {
        $where_sales_detail .= " AND p.provider_id = ?";
        $params_sales[] = $provider_id;
    }
    $stmt = $pdo->prepare("SELECT s.*, c.name as cust_name, t.name as type_name, prov.name as prov_name 
                           FROM sales s 
                           LEFT JOIN customers c ON s.customer_id = c.id 
                           LEFT JOIN qat_types t ON s.qat_type_id = t.id
                           LEFT JOIN purchases p ON s.purchase_id = p.id
                           LEFT JOIN providers prov ON p.provider_id = prov.id
                           $where_sales_detail ORDER BY s.id DESC");
    $stmt->execute($params_sales);
    $listSales = $stmt->fetchAll();
}
if ($view === 'Receiving' || $view === 'Printable') {
    $stmt = $pdo->prepare("SELECT p.*, t.name as type_name, prov.name as prov_name 
                           FROM purchases p 
                           LEFT JOIN qat_types t ON p.qat_type_id = t.id 
                           LEFT JOIN providers prov ON p.provider_id = prov.id
                           $whereSQL_Purch ORDER BY p.id DESC");
    $stmt->execute($params);
    $listPurch = $stmt->fetchAll();
}
if ($view === 'Expenses' || $view === 'Printable') {
    $stmt = $pdo->prepare("SELECT e.*, s.name as staff_name FROM expenses e LEFT JOIN staff s ON e.staff_id = s.id $whereSQL_Exp ORDER BY e.id DESC");
    $stmt->execute($params);
    $listExp = $stmt->fetchAll();
}
if ($view === 'Waste' || $view === 'Printable') {
    $where_waste = "";
    if ($reportType === 'Monthly') $where_waste = "WHERE DATE_FORMAT(l.sale_date, '%Y-%m') = ?";
    elseif ($reportType === 'Yearly') $where_waste = "WHERE YEAR(l.sale_date) = ?";
    else $where_waste = "WHERE l.sale_date = ?";

    $stmt = $pdo->prepare("SELECT l.*, t.name as type_name, prov.name as prov_name 
                           FROM leftovers l 
                           LEFT JOIN qat_types t ON l.qat_type_id = t.id
                           LEFT JOIN purchases p ON l.purchase_id = p.id
                           LEFT JOIN providers prov ON p.provider_id = prov.id
                           $where_waste AND l.status IN ('Dropped', 'Auto_Dropped') ORDER BY l.id DESC");
    $stmt->execute($params);
    $listWaste = $stmt->fetchAll();
}

// Final totals for Summary/Printable/Dashboard
if (in_array($view, ['Summary', 'Printable', 'Dashboard'])) {
    // 1. Fetch elements needed for cash calculation
    $stmt = $pdo->prepare("SELECT SUM(CASE WHEN payment_method = 'Cash' THEN price ELSE 0 END) as cash_sales FROM sales s $whereSQL_Sales");
    $stmt->execute($params);
    $cashSales = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments $whereSQL_Pay");
    $stmt->execute($params);
    $collectedPayments = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT SUM(amount) FROM qat_deposits $whereSQL_Dep AND currency = 'YER'");
    $stmt->execute($params);
    $depositsYER = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT SUM(amount) FROM refunds r $whereSQL_Ref AND refund_type = 'Cash'");
    $stmt->execute($params);
    $cashRefunds = $stmt->fetchColumn() ?: 0;

    $remainingCash = ($cashSales + $collectedPayments) - ($totalExpenses + $cashRefunds + $depositsYER);

    // 2. DASHBOARD SPECIFIC
    if ($view === 'Dashboard') {
        $totalReceivables = $pdo->query("SELECT SUM(total_debt) FROM customers")->fetchColumn() ?: 0;
        $inventoryValue = $pdo->query("SELECT SUM(agreed_price) FROM purchases WHERE status = 'Fresh'")->fetchColumn() ?: 0; // Simplified estimation
        $netWorth = $totalReceivables + $remainingCash + $inventoryValue;
        $netCash = $remainingCash;
        $netProfit = ($totalSales - $cashRefunds) - $totalPurchases - $totalExpenses;
    }
}

// PREMIUM STYLES FOR REPORTS
?>
<style>
    .report-card-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        border-radius: 15px 15px 0 0 !important;
        padding: 1.5rem 2rem;
    }

    .report-nav-pills .nav-link {
        border-radius: 50px;
        padding: 0.5rem 1.25rem;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-bottom: 5px;
        border: 1px solid transparent;
    }

    .report-nav-pills .nav-link.active {
        background: #ffc107 !important;
        color: #000 !important;
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
    }

    .report-nav-pills .nav-link:not(.active):hover {
        background: rgba(255, 255, 255, 0.1);
        color: #ffc107 !important;
    }

    .filter-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .report-title-icon {
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        margin-left: 15px;
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

                <!-- Filters Section -->
                <div class="bg-white text-dark p-3 rounded-4 shadow-sm">
                    <form class="row align-items-end g-3" method="GET">
                        <input type="hidden" name="view" value="<?= $view ?>">

                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">نوع التقرير</label>
                            <select name="report_type" class="form-select border-0 bg-light fw-bold py-2 shadow-none" id="repType" onchange="toggleInputs()" style="border-radius: 10px;">
                                <option value="Daily" <?= $reportType == 'Daily' ? 'selected' : '' ?>>📅 تقرير يومي</option>
                                <option value="Monthly" <?= $reportType == 'Monthly' ? 'selected' : '' ?>>🗓️ تقرير شهري</option>
                                <option value="Yearly" <?= $reportType == 'Yearly' ? 'selected' : '' ?>>📊 تقرير سنوي</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">الفترة الزمنية</label>
                            <div id="div_daily" class="<?= $reportType != 'Daily' ? 'd-none' : '' ?>">
                                <input type="date" name="date" class="form-control border-0 bg-light py-2 shadow-none" value="<?= $date ?>" style="border-radius: 10px;">
                            </div>
                            <div id="div_monthly" class="<?= $reportType != 'Monthly' ? 'd-none' : '' ?>">
                                <input type="month" name="month" class="form-control border-0 bg-light py-2 shadow-none" value="<?= $month ?>" style="border-radius: 10px;">
                            </div>
                            <div id="div_yearly" class="<?= $reportType != 'Yearly' ? 'd-none' : '' ?>">
                                <select name="year" class="form-select border-0 bg-light py-2 shadow-none" style="border-radius: 10px;">
                                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <?php if ($view === 'Sales'): ?>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">حسب المورد (الرعوي)</label>
                                <select name="provider_id" class="form-select border-0 bg-light py-2 shadow-none" style="border-radius: 10px;">
                                    <option value="">الكل</option>
                                    <?php foreach ($providersWithSales as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $provider_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-2">
                            <button class="btn btn-update-report w-100 py-2">
                                <i class="fas fa-sync-alt me-2"></i> تحديث
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