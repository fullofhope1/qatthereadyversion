<?php
require 'config/db.php';
include 'includes/header.php';

// Only admin can see this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');

// --- Sourcing Report ---
$user_id = $_SESSION['user_id'];
$stmtS = $pdo->prepare("
    SELECT p.purchase_date, prov.name as provider_name, t.name as type_name,
           p.source_weight_grams, p.agreed_price, p.is_received
    FROM purchases p
    LEFT JOIN providers prov ON p.provider_id = prov.id
    LEFT JOIN qat_types t   ON p.qat_type_id  = t.id
    WHERE p.purchase_date BETWEEN ? AND ? AND p.created_by = ?
    ORDER BY p.purchase_date DESC, p.id DESC
");
$stmtS->execute([$from, $to, $user_id]);
$sourcing = $stmtS->fetchAll();

$totalSourcingKg   = 0;
$totalSourcingCost = 0;
foreach ($sourcing as $s) {
    $totalSourcingKg   += $s['source_weight_grams'] / 1000;
    $totalSourcingCost += $s['agreed_price'];
}

// --- Expenses Report ---
$stmtE = $pdo->prepare("
    SELECT e.expense_date, e.description, e.amount, e.category, s.name as staff_name
    FROM expenses e
    LEFT JOIN staff s ON e.staff_id = s.id
    WHERE e.expense_date BETWEEN ? AND ? AND e.created_by = ?
    ORDER BY e.expense_date DESC, e.id DESC
");
$stmtE->execute([$from, $to, $user_id]);
$expenses = $stmtE->fetchAll();

$totalExpenses = array_sum(array_column($expenses, 'amount'));

$netCost = $totalSourcingCost + $totalExpenses;
?>

<style>
    .summary-card {
        border-radius: 14px;
        padding: 22px 24px;
        color: white;
        margin-bottom: 20px;
    }

    .summary-card .label {
        font-size: 0.85rem;
        opacity: 0.85;
    }

    .summary-card .value {
        font-size: 2rem;
        font-weight: 800;
    }

    .bg-sourcing {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    }

    .bg-expenses {
        background: linear-gradient(135deg, #fd7e14, #ffc107);
        color: #1a1a1a !important;
    }

    .bg-net {
        background: linear-gradient(135deg, #dc3545, #6f42c1);
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        border-right: 4px solid;
        padding-right: 10px;
        margin-bottom: 15px;
    }

    .section-title.sourcing-title {
        border-color: #0d6efd;
        color: #0d6efd;
    }

    .section-title.expenses-title {
        border-color: #fd7e14;
        color: #fd7e14;
    }
</style>

<div class="container-fluid" dir="rtl">
    <h2 class="text-center fw-bold mb-4 mt-2">
        <i class="fas fa-chart-bar text-primary me-2"></i> تقرير التوريد والمصاريف
    </h2>

    <!-- Date Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> عرض السجل
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="summary-card bg-sourcing">
                <div class="label"><i class="fas fa-truck-loading me-1"></i> إجمالي التوريد</div>
                <div class="value"><?= number_format($totalSourcingCost) ?> <small style="font-size:1rem;">ريال</small></div>
                <div class="small mt-1"><?= number_format($totalSourcingKg, 3) ?> كجم — <?= count($sourcing) ?> شحنة</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card bg-expenses">
                <div class="label"><i class="fas fa-wallet me-1"></i> إجمالي المصاريف</div>
                <div class="value"><?= number_format($totalExpenses) ?> <small style="font-size:1rem;">ريال</small></div>
                <div class="small mt-1"><?= count($expenses) ?> بند</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card bg-net">
                <div class="label"><i class="fas fa-calculator me-1"></i> إجمالي التكاليف</div>
                <div class="value"><?= number_format($netCost) ?> <small style="font-size:1rem;">ريال</small></div>
                <div class="small mt-1">توريد + مصاريف</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sourcing Table -->
        <div class="col-lg-6 mb-4">
            <div class="section-title sourcing-title">
                <i class="fas fa-truck-loading me-1"></i> تفاصيل التوريد
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="px-3 py-2 border-bottom">
                        <input type="text" id="adminSourcingSearch" class="form-control form-control-sm" placeholder="بحث باسم المورد أو النوع..." oninput="filterAdminSourcing()">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الرعوي</th>
                                    <th>النوع</th>
                                    <th>الوزن</th>
                                    <th>التكلفة</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sourcing)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">لا توجد شحنات في هذه الفترة</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($sourcing as $s): ?>
                                    <tr>
                                        <td><?= $s['purchase_date'] ?></td>
                                        <td><?= htmlspecialchars($s['provider_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($s['type_name'] ?? '-') ?></td>
                                        <td><?= number_format($s['source_weight_grams'] / 1000, 3) ?> كجم</td>
                                        <td class="fw-bold"><?= number_format($s['agreed_price']) ?></td>
                                        <td>
                                            <?php if ($s['is_received']): ?>
                                                <span class="badge bg-success">تم الاستلام</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">معلق</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if (!empty($sourcing)): ?>
                                <tfoot class="table-dark fw-bold">
                                    <tr>
                                        <td colspan="3">الإجمالي</td>
                                        <td><?= number_format($totalSourcingKg, 3) ?> كجم</td>
                                        <td><?= number_format($totalSourcingCost) ?> ريال</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="col-lg-6 mb-4">
            <div class="section-title expenses-title">
                <i class="fas fa-wallet me-1"></i> تفاصيل المصاريف
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="px-3 py-2 border-bottom">
                        <input type="text" id="adminExpensesSearch" class="form-control form-control-sm" placeholder="بحث بالبيان أو التصنيف أو العامل..." oninput="filterAdminExpenses()">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>البيان</th>
                                    <th>التصنيف</th>
                                    <th>العامل</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">لا توجد مصاريف في هذه الفترة</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($expenses as $e): ?>
                                    <tr>
                                        <td><?= $e['expense_date'] ?></td>
                                        <td><?= htmlspecialchars($e['description']) ?></td>
                                        <td>
                                            <?php
                                            $catMap = ['Shop' => 'خرج محل', 'Staff' => 'سحبية عامل', 'Other' => 'أخرى'];
                                            echo $catMap[$e['category']] ?? $e['category'];
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($e['staff_name'] ?? '-') ?></td>
                                        <td class="fw-bold"><?= number_format($e['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if (!empty($expenses)): ?>
                                <tfoot class="table-dark fw-bold">
                                    <tr>
                                        <td colspan="4">الإجمالي</td>
                                        <td><?= number_format($totalExpenses) ?> ريال</td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function filterAdminSourcing() {
        const term = document.getElementById('adminSourcingSearch').value.toLowerCase();
        const table = document.querySelector('.sourcing-title').nextElementSibling.querySelector('table');
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.cells.length < 6) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }

    function filterAdminExpenses() {
        const term = document.getElementById('adminExpensesSearch').value.toLowerCase();
        const table = document.querySelector('.expenses-title').nextElementSibling.querySelector('table');
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.cells.length < 5) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>

<script>
    function filterAdminSourcing() {
        const term = document.getElementById('adminSourcingSearch').value.toLowerCase();
        const table = document.querySelector('.sourcing-title').nextElementSibling.querySelector('table');
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.cells.length < 6) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }

    function filterAdminExpenses() {
        const term = document.getElementById('adminExpensesSearch').value.toLowerCase();
        const table = document.querySelector('.expenses-title').nextElementSibling.querySelector('table');
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.cells.length < 5) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>

<?php include 'includes/footer.php'; ?>