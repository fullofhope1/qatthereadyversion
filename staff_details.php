<?php
require 'config/db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: staff.php");
    exit;
}

// Fetch Staff Info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    echo "الموظف غير موجود.";
    exit;
}

// Fetch Monthly Filter
$month = $_GET['month'] ?? date('Y-m');

// Fetch Withdrawals (Expenses with category='Staff')
$stmt = $pdo->prepare("
    SELECT * FROM expenses 
    WHERE staff_id = ? 
    AND category = 'Staff'
    AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ORDER BY expense_date DESC
");
$stmt->execute([$id, $month]);
$withdrawals = $stmt->fetchAll();

// Calculate Total for this month
$monthTotal = 0;
foreach ($withdrawals as $w) {
    $monthTotal += $w['amount'];
}
?>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h2><?= htmlspecialchars($staff['name']) ?> - السجل المالي</h2>
        <a href="staff.php" class="btn btn-secondary">عودة للقائمة</a>
    </div>
</div>

<div class="row">
    <!-- Summary Card -->
    <div class="col-md-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">ملخص</div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span>الوظيفة</span>
                    <strong><?= htmlspecialchars($staff['role']) ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>الراتب اليومي</span>
                    <strong><?= number_format($staff['daily_salary']) ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>سقف السحب</span>
                    <strong><?= $staff['withdrawal_limit'] !== null ? number_format($staff['withdrawal_limit']) : '<span class="text-muted">بدون سقف</span>' ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between bg-light">
                    <span>المسحوب هذا الشهر</span>
                    <strong class="text-danger"><?= number_format($monthTotal) ?></strong>
                </div>
            </div>
        </div>

        <!-- Quick Edit Form -->
        <div class="card shadow border-warning">
            <div class="card-header bg-warning text-dark fw-bold">تعديل سريع</div>
            <div class="card-body">
                <form action="requests/update_staff.php" method="POST">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="mb-2">
                        <label class="small fw-bold">الراتب اليومي</label>
                        <input type="number" name="daily_salary" class="form-control form-control-sm" value="<?= $staff['daily_salary'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">سقف السحب</label>
                        <input type="number" name="withdrawal_limit" class="form-control form-control-sm" value="<?= $staff['withdrawal_limit'] ?>" placeholder="فارغ = بدون سقف">
                        <div class="form-text x-small">اتركه فارغاً للسحب المفتوح</div>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">حفظ</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Ledger -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                <span class="mb-0">سجل المسحوبات</span>
                <form class="d-flex" method="GET">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= $month ?>" onchange="this.form.submit()">
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td><?= $w['expense_date'] ?></td>
                                <td><?= htmlspecialchars($w['description']) ?></td>
                                <td class="text-danger fw-bold">-<?= number_format($w['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($withdrawals) === 0): ?>
                            <tr>
                                <td colspan="3" class="text-center p-3 text-muted">لا توجد مسحوبات لهذا الشهر.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>