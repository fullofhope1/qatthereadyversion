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
$view = $_GET['view'] ?? 'Summary';

$user_id = $_SESSION['user_id'];

// --- DATA FETCHING ---
// 1. Sourcing
$stmtS = $pdo->prepare("
    SELECT p.purchase_date, prov.name as provider_name, t.name as type_name,
           p.source_weight_grams, p.agreed_price, p.discount_amount, p.is_received, p.unit_type, p.source_units
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
foreach ($sourcing as &$s) {
    if (($s['unit_type'] ?? 'weight') === 'weight') {
        $totalSourcingKg += $s['source_weight_grams'] / 1000;
    }
    
    // Net Cost calculation
    $net_cost = $s['agreed_price'] - ($s['discount_amount'] ?? 0);
    $s['final_cost'] = $net_cost;

    $totalSourcingCost += $net_cost;
}
unset($s);

// 2. Expenses (All for this admin)
$stmtE = $pdo->prepare("
    SELECT e.expense_date, e.description, e.amount, e.category, s.name as staff_name, e.staff_id
    FROM expenses e
    LEFT JOIN staff s ON e.staff_id = s.id
    WHERE e.expense_date BETWEEN ? AND ? AND e.created_by = ?
    ORDER BY e.expense_date DESC, e.id DESC
");
$stmtE->execute([$from, $to, $user_id]);
$expenses = $stmtE->fetchAll();

$totalExpenses = array_sum(array_column($expenses, 'amount'));

// 3. Staff Draws (Filtered from Expenses)
$staffDraws = array_filter($expenses, function($e) {
    return $e['category'] === 'Staff';
});
$totalStaffDraws = array_sum(array_column($staffDraws, 'amount'));

$netCost = $totalSourcingCost + $totalExpenses;
?>

<style>
    :root {
        --admin-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        --admin-accent: #3498db;
    }

    .report-header {
        background: var(--admin-primary);
        color: white;
        padding: 2.5rem;
        border-radius: 20px 20px 0 0;
        position: relative;
        overflow: hidden;
    }

    .nav-tabs-premium {
        border: none;
        gap: 10px;
    }

    .nav-tabs-premium .nav-link {
        border: none !important;
        border-radius: 12px !important;
        padding: 0.8rem 1.5rem;
        font-weight: 700;
        color: rgba(255,255,255,0.7);
        transition: all 0.3s;
        background: rgba(255,255,255,0.05);
    }

    .nav-tabs-premium .nav-link.active {
        background: white !important;
        color: var(--admin-accent) !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .stat-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s;
    }
    .stat-card:hover { transform: translateY(-5px); }

    @media print {
        .no-print { display: none !important; }
        .container-fluid { padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        .report-header { background: #f8f9fa !important; color: black !important; padding: 1rem !important; margin-bottom: 2rem !important; }
        .nav-tabs-premium { display: none !important; }
    }
</style>

<div class="container-fluid mb-5">
    <!-- Header & Tabs -->
    <div class="report-header shadow-lg mb-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-file-contract me-2"></i> تقرير المشرف</h2>
                <p class="text-white-50 mb-0">نظرة شاملة على عمليات التوريد والمصاريف الخاصة بك</p>
            </div>
            <button onclick="window.print()" class="btn btn-light rounded-pill px-4 fw-bold">
                <i class="fas fa-print me-2"></i> طباعة PDF
            </button>
        </div>

        <ul class="nav nav-tabs nav-tabs-premium">
            <?php
            $tabs = [
                'Summary' => ['label' => 'الخلاصة الكلية', 'icon' => 'fa-th-large'],
                'Sourcing' => ['label' => 'سجل التوريد', 'icon' => 'fa-truck-loading'],
                'Expenses' => ['label' => 'المصاريف', 'icon' => 'fa-wallet'],
                'StaffDraws' => ['label' => 'مسحوبات الموظفين', 'icon' => 'fa-user-tag']
            ];
            foreach ($tabs as $k => $v):
                $active = ($view === $k);
            ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active ? 'active' : '' ?>" href="?view=<?= $k ?>&from=<?= $from ?>&to=<?= $to ?>">
                        <i class="fas <?= $v['icon'] ?> me-2"></i> <?= $v['label'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Date Filter Card -->
    <div class="card border-0 shadow-sm mb-4 no-print" style="border-radius:15px; margin-top:-2rem; position:relative; z-index:10; background:rgba(255,255,255,0.9); backdrop-filter:blur(10px);">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end text-right" dir="rtl">
                <input type="hidden" name="view" value="<?= $view ?>">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">من تاريخ</label>
                    <input type="date" name="from" class="form-control rounded-pill border-0 bg-light" value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control rounded-pill border-0 bg-light" value="<?= htmlspecialchars($to) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">
                        <i class="fas fa-sync-alt me-2"></i> تحديث التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Print Only Header -->
    <div class="d-none d-print-block text-center mb-4">
        <h1 class="fw-bold">تقرير المشرف: <?= htmlspecialchars($_SESSION['username']) ?></h1>
        <p class="text-muted">الفترة من <?= $from ?> إلى <?= $to ?></p>
        <hr>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <?php if ($view === 'Summary'): ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white p-4">
                        <h6 class="text-white-50">إجمالي التوريد</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($totalSourcingCost) ?> ريال</h2>
                        <p class="small mb-0 opacity-75"><?= number_format($totalSourcingKg, 2) ?> كجم / <?= count($sourcing) ?> شحنة</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-info text-white p-4">
                        <h6 class="text-white-50">إجمالي المصاريف</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($totalExpenses) ?> ريال</h2>
                        <p class="small mb-0 opacity-75">تتضمن مسحوبات الموظفين</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-dark text-white p-4">
                        <h6 class="text-white-50">التكاليف التشغيلية الكلية</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($netCost) ?> ريال</h2>
                        <p class="small mb-0 opacity-75">توريد + مصاريف</p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-4" style="border-radius:15px;">
                        <h5 class="fw-bold mb-4">موجز مسحوبات الموظفين</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>العامل</th>
                                        <th class="text-center">عدد الدفعات</th>
                                        <th class="text-end">إجمالي المسحوبات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $staffSummary = [];
                                    foreach ($staffDraws as $d) {
                                        $name = $d['staff_name'] ?? 'غير محدد';
                                        if (!isset($staffSummary[$name])) $staffSummary[$name] = ['count' => 0, 'total' => 0];
                                        $staffSummary[$name]['count']++;
                                        $staffSummary[$name]['total'] += $d['amount'];
                                    }
                                    foreach ($staffSummary as $name => $data): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($name) ?></td>
                                            <td class="text-center"><?= $data['count'] ?></td>
                                            <td class="text-end fw-bold text-danger"><?= number_format($data['total']) ?> ريال</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($staffSummary)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-4">لا توجد مسحوبات موظفين</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($view === 'Sourcing'): ?>
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">سجل التوريد التفصيلي</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">التاريخ</th>
                                <th>الرعوي</th>
                                <th>النوع</th>
                                <th class="text-center">الكمية/الوزن</th>
                                <th class="text-end pe-4">التكلفة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sourcing as $s): 
                                $isWeight = ($s['unit_type'] ?? 'weight') === 'weight';
                                if ($isWeight) {
                                    $qtyDisplay = number_format($s['source_weight_grams'] / 1000, 2) . ' كجم';
                                } else {
                                    $qtyDisplay = number_format($s['source_units']) . ' ' . htmlspecialchars($s['unit_type'] ?: 'حبة');
                                }
                            ?>
                                <tr>
                                    <td class="ps-4"><?= $s['purchase_date'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($s['provider_name'] ?? '-') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['type_name'] ?? '-') ?></span></td>
                                    <td class="text-center"><?= $qtyDisplay ?></td>
                                    <td class="text-end pe-4">
                                        <?php if (($s['discount_amount'] ?? 0) > 0): ?>
                                            <div class="small text-muted text-decoration-line-through"><?= number_format($s['agreed_price']) ?></div>
                                            <div class="text-danger small">-<?= number_format($s['discount_amount']) ?></div>
                                            <div class="fw-bold text-success mb-0"><?= number_format($s['final_cost']) ?></div>
                                        <?php else: ?>
                                            <div class="fw-bold"><?= number_format($s['final_cost']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">الإجمالي:</td>
                                <td class="text-center">الكمية المسجلة</td>
                                <td class="text-end pe-4 text-primary"><?= number_format($totalSourcingCost) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        <?php elseif ($view === 'Expenses'): ?>
             <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">سجل المصاريف العامة</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">التاريخ</th>
                                <th>البيان</th>
                                <th>التصنيف</th>
                                <th class="text-end pe-4">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $e): ?>
                                <tr>
                                    <td class="ps-4"><?= $e['expense_date'] ?></td>
                                    <td><?= htmlspecialchars($e['description']) ?></td>
                                    <td><span class="badge bg-info-subtle text-info"><?= $e['category'] ?></span></td>
                                    <td class="text-end pe-4 fw-bold"><?= number_format($e['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end border-0">الإجمالي:</td>
                                <td class="text-end pe-4 text-primary border-0"><?= number_format($totalExpenses) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        <?php elseif ($view === 'StaffDraws'): ?>
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">كشف سحبيات ومسحوبات الموظفين</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">التاريخ</th>
                                <th>الموظف</th>
                                <th>البيان</th>
                                <th class="text-end pe-4">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffDraws as $d): ?>
                                <tr>
                                    <td class="ps-4"><?= $d['expense_date'] ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($d['staff_name'] ?? 'غير محدد') ?></td>
                                    <td><?= htmlspecialchars($d['description']) ?></td>
                                    <td class="text-end pe-4 fw-bold text-danger"><?= number_format($d['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($staffDraws)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">لا يوجد مسحوبات موظفين</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">إجمالي المسحوبات:</td>
                                <td class="text-end pe-4 text-danger"><?= number_format($totalStaffDraws) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>