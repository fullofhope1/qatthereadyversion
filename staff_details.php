<?php
require 'config/db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: staff.php");
    exit;
}

// Initialization via Clean Architecture
$repo = new StaffRepository($pdo);
$service = new StaffService($repo);

// Fetch Staff Info
$staff = $service->getById($id);
if (!$staff) {
    echo "<div class='container mt-5 text-center'><div class='alert alert-danger'>الموظف غير موجود.</div></div>";
    exit;
}

// Fetch Monthly Filter
$month = $_GET['month'] ?? date('Y-m');
$withdrawals = $service->getStaffWithdrawals($id, $month);

// Calculations
$monthTotal = 0;
foreach ($withdrawals as $w) {
    $monthTotal += $w['amount'];
}

// Estimate Accrued Salary (Days in selected month)
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)explode('-', $month)[1], (int)explode('-', $month)[0]);
$estimatedSalary = $daysInMonth * ($staff['daily_salary'] ?: 0);
$netBalance = $estimatedSalary - $monthTotal;
?>

<style>
    @media print {
        @page { size: A4; margin: 1cm; }
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background: #f8f9fa !important; color: #000 !important; font-weight: bold; border-bottom: 2px solid #000 !important; }
        body { background: #fff !important; color: #000 !important; }
        table { border: 1px solid #000 !important; }
        th, td { border: 1px solid #000 !important; padding: 8px !important; }
        .text-danger { color: #000 !important; }
    }

    .statement-header {
        background: linear-gradient(135deg, #2d3436 0%, #000000 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
    }

    .stat-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-5px); }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="staff.php" class="btn btn-outline-secondary rounded-pill">
            <i class="fas fa-arrow-right me-2"></i> عودة لقائمة الموظفين
        </a>
        <button onclick="window.print()" class="btn btn-dark rounded-pill px-4">
            <i class="fas fa-print me-2 text-warning"></i> طباعة كشف الحساب
        </button>
    </div>

    <div class="statement-header shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-1">كشف حساب الموظف</h1>
                <p class="mb-0 opacity-75">سجل السحبيات والرواتب للشهر (<?= date('m - Y', strtotime($month)) ?>)</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="h4 mb-0"><?= htmlspecialchars($staff['name']) ?></div>
                <div class="small opacity-75"><?= htmlspecialchars($staff['role']) ?></div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card h-100 bg-white">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold mb-3">الراتب اليومي</h6>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($staff['daily_salary']) ?> <small class="fs-6 fw-normal">YER</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100 bg-white border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold mb-3">مستحق الشهر (<?= $daysInMonth ?> يوم)</h6>
                    <h4 class="fw-bold mb-0 text-primary"><?= number_format($estimatedSalary) ?> <small class="fs-6 fw-normal text-muted">YER</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100 bg-white border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold mb-3">إجمالي المسحوبات</h6>
                    <h4 class="fw-bold mb-0 text-danger"><?= number_format($monthTotal) ?> <small class="fs-6 fw-normal text-muted">YER</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100 <?= $netBalance >= 0 ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                <div class="card-body">
                    <h6 class="small fw-bold mb-3 opacity-75">المتبقي الصافي</h6>
                    <h4 class="fw-bold mb-0"><?= number_format($netBalance) ?> <small class="fs-6 fw-normal">YER</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Ledger -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2 text-primary"></i> تفاصيل العمليات المالية</h5>
                    <form class="d-flex no-print" method="GET">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="month" name="month" class="form-select form-select-sm" value="<?= $month ?>" onchange="this.form.submit()">
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">التاريخ</th>
                                    <th>البيان / التفاصيل</th>
                                    <th class="text-end pe-4">المبلغ المسحوب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $w): ?>
                                    <tr>
                                        <td class="ps-4"><?= date('Y/m/d', strtotime($w['expense_date'])) ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($w['description'] ?: 'سحب نقدي') ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($w['category'] ?? 'Staff') ?></div>
                                        </td>
                                        <td class="text-end pe-4 fw-bold text-danger"><?= number_format($w['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($withdrawals)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="fas fa-info-circle mb-2 fs-3"></i><br>
                                            لا توجد مسحوبات مسجلة لهذا الشهر
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="ps-4 text-end">إجمالي مسحوبات الشهر:</td>
                                        <td class="text-end pe-4 text-danger"><?= number_format($monthTotal) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Profile Info -->
        <div class="col-md-4 no-print">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-4 border-bottom pb-2">بيانات الموظف</h6>
                    <div class="mb-3 d-flex justify-content-between font-small">
                        <span class="text-muted">تاريخ الإضافة:</span>
                        <span class="fw-bold"><?= date('Y/m/d', strtotime($staff['created_at'] ?? '2024-01-01')) ?></span>
                    </div>
                    <div class="mb-3 d-flex justify-content-between font-small">
                        <span class="text-muted">الحالة:</span>
                        <span class="badge bg-success-subtle text-success rounded-pill"><?= $staff['is_active'] ? 'نشط' : 'ملغى' ?></span>
                    </div>
                    <div class="mb-0 d-flex justify-content-between font-small">
                        <span class="text-muted">رقم الهاتف:</span>
                        <span class="fw-bold text-end" dir="ltr"><?= htmlspecialchars($staff['phone'] ?? 'لا يوجد') ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 bg-light-warning" style="border-radius: 15px; border-right: 5px solid #ffc107 !important;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-exclamation-circle me-1"></i> تنبيهات محاسبية</h6>
                    <?php if ($staff['withdrawal_limit'] > 0): ?>
                        <p class="small mb-2">سقف السحب المسموح: <strong><?= number_format($staff['withdrawal_limit']) ?></strong></p>
                        <?php if ($monthTotal >= $staff['withdrawal_limit']): ?>
                            <div class="alert alert-danger py-2 mb-0 small">تجاوز الموظف سقف السحب المسموح به!</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="small mb-0 text-muted">لا يوجد سقف سحب محدد لهذا الموظف.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>