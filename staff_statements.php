<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$reportRepo = new ReportRepository($pdo);
$staffRepo = new StaffRepository($pdo);

$view = $_GET['view'] ?? 'summary';
$staffId = $_GET['staff_id'] ?? null;

if ($view === 'summary') {
    $summary = $reportRepo->getStaffBalanceSummary();
} else {
    $staff = $staffRepo->getById($staffId);
    if (!$staff) die("الموظف غير موجود");
    $statement = $reportRepo->getStaffDetailedStatement($staffId);
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">
            <i class="fas fa-id-badge me-2 text-primary"></i> 
            <?= $view === 'summary' ? 'كشوفات حسابات الموظفين' : 'كشف حساب: ' . htmlspecialchars($staff['name']) ?>
        </h3>
        <?php if ($view === 'details'): ?>
            <a href="staff_statements.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> عودة للملخص
            </a>
        <?php endif; ?>
    </div>

    <?php if ($view === 'summary'): ?>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">الموظف</th>
                            <th class="py-3">الراتب اليومي</th>
                            <th class="py-3">إجمالي المستحق</th>
                            <th class="py-3 text-danger">إجمالي المسحوبات</th>
                            <th class="py-3">الرصيد المتبقي</th>
                            <th class="py-3 text-center">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $s): 
                            $balanceClass = $s['balance'] >= 0 ? 'text-success' : 'text-danger';
                        ?>
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($s['role']) ?></div>
                                </td>
                                <td><?= number_format($s['daily_salary']) ?></td>
                                <td class="fw-bold"><?= number_format($s['total_earned']) ?></td>
                                <td class="text-danger"><?= number_format($s['total_withdrawn']) ?></td>
                                <td class="fw-bold <?= $balanceClass ?>"><?= number_format($s['balance']) ?></td>
                                <td class="text-center">
                                    <a href="staff_statements.php?view=details&staff_id=<?= $s['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                        <i class="fas fa-list-ul me-1"></i> كشف تفصيلي
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 small text-muted">
            * يتم احتساب إجمالي المستحق بناءً على تاريخ الإضافة (أو تاريخ الانضمام) والراتب اليومي المحدد.
        </div>

    <?php else: ?>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary-emphasis"></i>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($staff['name']) ?></h4>
                    <span class="badge bg-primary-subtle text-primary rounded-pill mb-4"><?= htmlspecialchars($staff['role']) ?></span>
                    
                    <div class="text-start border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">الراتب اليومي:</span>
                            <span class="fw-bold"><?= number_format($staff['daily_salary']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">سقف المسحوبات:</span>
                            <span class="fw-bold"><?= number_format($staff['withdrawal_limit']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i> سجل المسحوبات</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4">التاريخ</th>
                                    <th>البيان</th>
                                    <th>طريقة الدفع</th>
                                    <th class="text-end px-4">المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statement as $st): ?>
                                    <tr>
                                        <td class="px-4"><?= $st['op_date'] ?></td>
                                        <td><?= htmlspecialchars($st['description'] ?: 'سحب نقدي') ?></td>
                                        <td>
                                            <?php if ($st['payment_method'] === 'Transfer'): ?>
                                                <span class="badge bg-info-subtle text-info"><i class="fas fa-exchange-alt me-1"></i> تحويل</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><i class="fas fa-money-bill-wave me-1"></i> كاش</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4 fw-bold text-danger"><?= number_format($st['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($statement)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">لا توجد مسحوبات مسجلة لهذا الموظف</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
