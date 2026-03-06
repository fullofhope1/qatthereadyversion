<?php
require 'config/db.php';
include 'includes/header.php';

// Calculate Stats for Preview
$today = $_GET['date'] ?? date('Y-m-d');
$types = $pdo->query("SELECT * FROM qat_types")->fetchAll();

$preview = [];
$preview = [];
foreach ($types as $t) {
    // 1. Total Purchased on selection date (Fresh logic)
    $stmtBuy = $pdo->prepare("SELECT SUM(quantity_kg) FROM purchases WHERE qat_type_id = ? AND purchase_date = ? AND status != 'Closed'");
    $stmtBuy->execute([$t['id'], $today]);
    $bought = $stmtBuy->fetchColumn() ?: 0;

    // 2. Total Sold from these specific purchases
    // We get the purchase IDs for that day and type
    $stmtPIDs = $pdo->prepare("SELECT id FROM purchases WHERE qat_type_id = ? AND purchase_date = ?");
    $stmtPIDs->execute([$t['id'], $today]);
    $pids = $stmtPIDs->fetchAll(PDO::FETCH_COLUMN);

    $sold = 0;
    if (!empty($pids)) {
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $stmtSell = $pdo->prepare("SELECT SUM(weight_kg) FROM sales WHERE purchase_id IN ($placeholders)");
        $stmtSell->execute($pids);
        $sold = $stmtSell->fetchColumn() ?: 0;
    }

    $rem = $bought - $sold;
    if ($rem < 0) {
        $rem = 0;
    }

    $preview[] = [
        'name' => $t['name'],
        'bought' => $bought,
        'sold' => $sold,
        'surplus' => $rem
    ];
}

// 3. Count Unpaid Daily Debts for the selected date
$stmtDebt = $pdo->prepare("SELECT COUNT(*) as count, SUM(price) as total FROM sales WHERE sale_date = ? AND payment_method = 'Debt' AND debt_type = 'Daily' AND is_paid = 0");
$stmtDebt->execute([$today]);
$debtStats = $stmtDebt->fetch();
$totalLeftoversCount = 0;
foreach ($preview as $p) {
    if ($p['surplus'] > 0.001) {
        $totalLeftoversCount++;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h3 class="mb-0">إغلاق وتصفية اليومية</h3>
            </div>
            <div class="card-body">
                <div class="mb-4 text-center">
                    <label class="form-label fw-bold">اختر اليوم المراد إغلاقه:</label>
                    <input type="date" class="form-control w-50 mx-auto" id="close_date_select" value="<?= $today ?>" onchange="location.href='?date=' + this.value">
                </div>

                <!-- Pre-Closing Summary Alert -->
                <div class="alert alert-info border-primary shadow-sm" dir="rtl">
                    <h5 class="alert-heading"><i class="fas fa-file-invoice me-2"></i> ملخص الإغلاق</h5>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h3 class="text-danger fw-bold"><?= $totalLeftoversCount ?></h3>
                            <small>أنواع ستنتقل للبقايا</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-primary fw-bold"><?= $debtStats['count'] ?></h3>
                            <small>ديون يومية ستُرحل لمسودة الغد</small>
                        </div>
                    </div>
                    <?php if ($debtStats['total'] > 0): ?>
                        <div class="mt-3 text-center">
                            <span class="badge bg-primary fs-6">إجمالي الديون المرحلة: <?= number_format($debtStats['total'], 0) ?> ريال</span>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="lead mt-4 text-center fw-bold">ماذا سيحدث عند الضغط؟</p>
                <ul class="text-end" dir="rtl">
                    <li>حساب الكميات المتبقية وتصديرها كـ <b>"بقايا"</b> لليوم التالي.</li>
                    <li>إغلاق مخزون اليوم ووسمه بـ <b>"مغلق"</b>.</li>
                    <li><b>ترحيل الديون اليومية غير المدفوعة تلقائياً إلى كشف الغد.</b></li>
                </ul>
                <p class="text-warning text-center"><i class="fas fa-exclamation-triangle"></i> يرجى التأكد من مراجعة الجدول أدناه قبل الإغلاق.</p>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>المشترى (كجم)</th>
                            <th>المباع (كجم)</th>
                            <th>سينتقل للبقايا (كجم)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= number_format($p['bought'], 3) ?></td>
                                <td><?= number_format($p['sold'], 3) ?></td>
                                <td class="fw-bold text-danger"><?= number_format($p['surplus'], 3) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form action="requests/close_day.php" method="POST" onsubmit="return confirm('هل أنت متأكد من إغلاق اليوم؟ سيؤدي ذلك لإنشاء مدخلات ممسي للغد.');">
                    <input type="hidden" name="date" value="<?= $today ?>">
                    <button type="submit" class="btn btn-danger w-100 btn-lg">إغلاق اليوم وترحيل الممسي</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>