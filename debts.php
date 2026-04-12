<?php
require 'config/db.php';
include 'includes/header.php';

// Initialization via Clean Architecture
$debtRepo = new DebtRepository($pdo);
$service = new DebtService($debtRepo);

// Filter
$type = $_GET['type'] ?? 'All';

$data = $service->getDebtsData($type);
$debtors = $data['debtors'];
$viewTotal = $data['total'];

$type_ar = [
    'All'      => 'الكل',
    'Daily'    => 'يومي',
    'Upcoming' => 'مؤجل/قادم',
    'Monthly'  => 'شهري',
    'Yearly'   => 'سنوي'
];

$yesterday = date('Y-m-d', strtotime('-1 day'));
?>

<style>
    .overdue-row {
        background: #fff3cd !important;
    }

    .overdue-24h {
        background: #f8d7da !important;
    }

    /* Debt type row colors */
    .debt-row-daily   { background: rgba(220, 53,  69,  0.08) !important; border-right: 4px solid #dc3545; }
    .debt-row-monthly { background: rgba(13,  110, 253, 0.08) !important; border-right: 4px solid #0d6efd; }
    .debt-row-yearly  { background: rgba(25,  135, 84,  0.08) !important; border-right: 4px solid #198754; }
    .debt-row-daily.overdue-24h   { background: rgba(220, 53, 69, 0.2) !important; }
    .debt-row-monthly.overdue-24h { background: rgba(13, 110, 253, 0.15) !important; }
    .debt-row-yearly.overdue-24h  { background: rgba(25, 135, 84, 0.15) !important; }
</style>

<div class="row mb-4">
    <div class="col-md-12 text-center">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0 text-dark fw-bold"><i class="fas fa-file-invoice-dollar text-danger me-2"></i> إدارة الديون</h1>
            <a href="requests/reconcile_debts.php" class="btn btn-outline-warning rounded-pill px-4 fw-bold shadow-sm" onclick="return confirm('ستقوم عملية المطابقة بإعادة حساب كل عملية بيع وسداد لكل عميل. هل تريد الاستمرار؟')">
                <i class="fas fa-sync-alt me-1"></i> مزامنة الديون
            </a>
        </div>

        <?php if (isset($_GET['reconciled'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 text-start mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>اكتمل التدقيق!</strong> تم إعادة حساب ومطابقة جميع ديون العملاء مع سجل المعاملات.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm p-3 mb-4 bg-white rounded-4 border-0">
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="?type=All" class="btn btn-lg shadow-sm <?= $type == 'All' ? 'btn-dark' : 'btn-outline-dark' ?>"><i class="fas fa-list me-2"></i>الكل</a>
                <a href="?type=Daily" class="btn btn-lg shadow-sm <?= $type == 'Daily' ? 'btn-danger' : 'btn-outline-danger' ?>"><i class="fas fa-calendar-day me-2"></i>يومي</a>
                <a href="?type=Upcoming" class="btn btn-lg shadow-sm <?= $type == 'Upcoming' ? 'btn-info text-white' : 'btn-outline-info' ?>"><i class="fas fa-history me-2"></i>مؤجل/قادم</a>
                <a href="?type=Monthly" class="btn btn-lg shadow-sm <?= $type == 'Monthly' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-calendar-alt me-2"></i>شهري</a>
                <a href="?type=Yearly" class="btn btn-lg shadow-sm <?= $type == 'Yearly' ? 'btn-success' : 'btn-outline-success' ?>"><i class="fas fa-calendar me-2"></i>سنوي</a>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'rolled_over'): ?>
    <div class="row mb-3">
        <div class="col-md-8 mx-auto">
            <div class="alert alert-success alert-dismissible fade show shadow-sm text-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>نجاح!</strong> تم ترحيل الدين.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-8 mx-auto text-center">
        <div class="card shadow-lg border-0 bg-dark text-white" style="background: linear-gradient(135deg, #2c3e50, #000000) !important;">
            <div class="card-body py-4 text-center">
                <h6 class="text-uppercase opacity-75 fw-bold mb-2">إجمالي الديون القائمة (<?= $type_ar[$type] ?>)</h6>
                <h2 class="display-4 fw-bold mb-0 text-white">
                    <i class="fas fa-coins text-warning me-2 animate__animated animate__pulse animate__infinite"></i>
                    <?= number_format($viewTotal) ?> <small class="fs-4 text-light">ريال</small>
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Search box (#32) -->
                <div class="mb-3">
                    <input type="text" id="debtSearch" class="form-control" placeholder="بحث باسم العميل أو رقم الهاتف..." onkeyup="filterDebts()">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="fas fa-user me-1"></i> الاسم</th>
                                <th><i class="fas fa-phone me-1"></i> الرقم</th>
                                <th><i class="fas fa-money-bill me-1"></i> <?= ($type == 'All') ? 'إجمالي الدين' : 'ديون ' . $type_ar[$type] ?></th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="debtTableBody">
                            <?php foreach ($debtors as $d):
                                // Determine 24h overdue status
                                $isOverdue24h = false;
                                if (!empty($d['earliest_due']) && $d['earliest_due'] <= $yesterday) {
                                    $isOverdue24h = true;
                                }
                                // Row color by debt type
                                $debtTypeClass = '';
                                if (!empty($d['debt_type'])) {
                                    if ($d['debt_type'] === 'Daily')   $debtTypeClass = 'debt-row-daily';
                                    if ($d['debt_type'] === 'Monthly') $debtTypeClass = 'debt-row-monthly';
                                    if ($d['debt_type'] === 'Yearly')  $debtTypeClass = 'debt-row-yearly';
                                } elseif ($type !== 'All') {
                                    if ($type === 'Daily')   $debtTypeClass = 'debt-row-daily';
                                    if ($type === 'Monthly') $debtTypeClass = 'debt-row-monthly';
                                    if ($type === 'Yearly')  $debtTypeClass = 'debt-row-yearly';
                                }
                                $rowClass = $debtTypeClass . ($isOverdue24h ? ' overdue-24h' : '');
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        <?= htmlspecialchars($d['name']) ?>
                                        <?php if ($isOverdue24h): ?>
                                            <span class="badge bg-danger ms-1" title="تجاوز 24 ساعة"><i class="fas fa-exclamation-circle"></i> متأخر</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($d['phone']) ?></td>
                                    <td>
                                        <span class="fw-bold fs-5 text-dark">
                                            <?= number_format($d['due_amount']) ?>
                                        </span>

                                        <?php if ($d['due_amount'] > 0 && in_array($type, ['Daily', 'Monthly', 'Yearly'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-secondary text-light mb-1">
                                                    <i class="fas fa-calendar-alt me-1"></i> مستحق: اليوم/سابق
                                                </span>
                                                <form action="requests/process_debt_rollover.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="customer_id" value="<?= $d['id'] ?>">
                                                    <input type="hidden" name="debt_type" value="<?= $type ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-dark shadow-sm py-0" title="ترحيل">
                                                        <i class="fas fa-redo me-1"></i> ترحيل
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="customer_details.php?id=<?= $d['id'] ?>&back=debts" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                            <i class="fas fa-eye me-1"></i> التفاصيل والسداد
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($debtors) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">لا توجد ديون مستحقة.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Report Link -->
<div class="text-center mt-4 mb-5 no-print">
    <a href="reports.php?report_type=Daily" class="btn btn-outline-secondary">
        <i class="fas fa-file-invoice me-2"></i> تقرير اليوم المفصل
    </a>
</div>

<script>
    function filterDebts() {
        const filter = document.getElementById('debtSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#debtTableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }
</script>

<?php include 'includes/footer.php'; ?>