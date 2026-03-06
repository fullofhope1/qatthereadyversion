<?php
require 'config/db.php';
include 'includes/header.php';

// 1. Fetch Staff with their Total Withdrawals (Expenses where category='Staff')
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT s.*, 
    (SELECT SUM(amount) FROM expenses WHERE staff_id = s.id AND category = 'Staff') as total_withdrawn 
    FROM staff s 
    WHERE s.created_by = ?
    ORDER BY s.name ASC
");
$stmt->execute([$user_id]);
$staffMembers = $stmt->fetchAll();

// 2. Total Withdrawals for All
$stmtTotal = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE category = 'Staff' AND created_by = ?");
$stmtTotal->execute([$user_id]);
$allWithdrawals = $stmtTotal->fetchColumn() ?: 0;
?>

<div class="row mb-4">
    <div class="col-md-12 text-center">
        <div class="card bg-info text-dark shadow">
            <div class="card-body">
                <h3>إجمالي مسحوبات الموظفين</h3>
                <h2 class="fw-bold"><?= number_format($allWithdrawals) ?> YER</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Add New Staff -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">إضافة موظف جديد</h5>
            </div>
            <div class="card-body">
                <form action="requests/add_staff.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">الاسم</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوظيفة</label>
                        <input type="text" name="role" class="form-control" placeholder="مثال: مبيعات، عامل">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الراتب اليومي</label>
                        <input type="number" step="1" name="daily_salary" class="form-control" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">سقف السحب</label>
                        <input type="number" step="1" name="withdrawal_limit" class="form-control" placeholder="اختياري">
                        <div class="form-text">اتركه فارغاً إذا لا يوجد سقف للسحب</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">إضافة موظف</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Staff List -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">قائمة الموظفين</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>قائمة الموظفين</th>
                                <th>الراتب اليومي</th>
                                <th>السقف</th>
                                <th>المسحوب</th>
                                <th>المتبقي</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffMembers as $s):
                                $hasLimit = ($s['withdrawal_limit'] !== null);
                                $rem = $hasLimit ? (($s['withdrawal_limit'] ?: 0) - ($s['total_withdrawn'] ?: 0)) : null;
                                $rowClass = ($hasLimit && $rem <= 0) ? 'table-danger' : '';
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td><?= number_format($s['daily_salary'] ?: 0) ?></td>
                                    <td><?= $hasLimit ? number_format($s['withdrawal_limit']) : '<span class="text-muted">بدون سقف</span>' ?></td>
                                    <td class="fw-bold text-danger"><?= number_format($s['total_withdrawn'] ?: 0) ?></td>
                                    <td class="fw-bold">
                                        <?php if ($hasLimit): ?>
                                            <span class="<?= $rem > 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($rem) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="staff_details.php?id=<?= $s['id'] ?>" class="btn btn-info btn-sm text-white" title="Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
        <i class="fas fa-file-invoice me-2"></i> عرض تقرير اليوم المفصل
    </a>
</div>

<?php include 'includes/footer.php'; ?>