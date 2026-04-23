<?php
require 'config/db.php';
include 'includes/header.php';

// Calculate Stats for Preview
// Find the oldest date that has unclosed activity
$stmtOldest = $pdo->query("SELECT MIN(d) FROM (
    SELECT MIN(purchase_date) as d FROM purchases WHERE status IN ('Fresh', 'Momsi')
    UNION
    SELECT MIN(sale_date) as d FROM sales WHERE payment_method = 'Debt' AND debt_type = 'Daily' AND is_paid = 0
    UNION
    SELECT MIN(sale_date) as d FROM leftovers WHERE status = 'Transferred_Next_Day'
) as unclosed_dates");
$oldestUnclosed = $stmtOldest->fetchColumn() ?: date('Y-m-d', strtotime('-1 day'));

$today = $_GET['date'] ?? date('Y-m-d');
$realToday = date('Y-m-d');
$isClosingToday = ($today === $realToday);

$types = $pdo->query("SELECT * FROM qat_types")->fetchAll();

$preview = [];
foreach ($types as $t) {
    // 1. Total Purchased up to selection date (Fresh logic)
    $stmtBuy = $pdo->prepare("SELECT SUM(quantity_kg) as bought_kg, SUM(received_units) as bought_units FROM purchases WHERE qat_type_id = ? AND purchase_date <= ? AND status != 'Closed'");
    $stmtBuy->execute([$t['id'], $today]);
    $buyData = $stmtBuy->fetch(PDO::FETCH_ASSOC);
    $boughtKg = $buyData['bought_kg'] ?: 0;
    $boughtUnits = $buyData['bought_units'] ?: 0;

    // 2. All PIDs for active purchases up to this date
    $stmtPIDs = $pdo->prepare("SELECT id FROM purchases WHERE qat_type_id = ? AND purchase_date <= ? AND status != 'Closed'");
    $stmtPIDs->execute([$t['id'], $today]);
    $pids = $stmtPIDs->fetchAll(PDO::FETCH_COLUMN);

    $soldKg = 0;
    $soldUnits = 0;
    $managedKg = 0;
    $managedUnits = 0;
    if (!empty($pids)) {
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        // 2. Total Sold
        $stmtSell = $pdo->prepare("SELECT SUM(weight_kg) as sold_kg, SUM(quantity_units) as sold_units FROM sales WHERE purchase_id IN ($placeholders)");
        $stmtSell->execute($pids);
        $sellData = $stmtSell->fetch(PDO::FETCH_ASSOC);
        $soldKg = $sellData['sold_kg'] ?: 0;
        $soldUnits = $sellData['sold_units'] ?: 0;

        // 3. Managed weight (manual leftovers)
        $stmtManaged = $pdo->prepare("SELECT SUM(weight_kg) as managed_kg, SUM(quantity_units) as managed_units FROM leftovers WHERE purchase_id IN ($placeholders) AND status IN ('Dropped', 'Transferred_Next_Day')");
        $stmtManaged->execute($pids);
        $managedData = $stmtManaged->fetch(PDO::FETCH_ASSOC);
        $managedKg = $managedData['managed_kg'] ?: 0;
        $managedUnits = $managedData['managed_units'] ?: 0;
    }

    $remKg = $boughtKg - $soldKg - $managedKg;
    if ($remKg < 0.001) $remKg = 0;

    $remUnits = $boughtUnits - $soldUnits - $managedUnits;
    if ($remUnits < 0) $remUnits = 0;

    $preview[] = [
        'name' => $t['name'],
        'bought_kg' => $boughtKg,
        'bought_units' => $boughtUnits,
        'sold_kg' => $soldKg,
        'sold_units' => $soldUnits,
        'surplus_kg' => $remKg,
        'surplus_units' => $remUnits
    ];
}

// 3. Count Unpaid Daily Debts that will be rolled over (due_date <= target date)
$stmtDebt = $pdo->prepare("SELECT COUNT(*) as count, SUM(price - paid_amount - COALESCE(refund_amount,0)) as total FROM sales WHERE (due_date <= ? OR due_date IS NULL) AND payment_method = 'Debt' AND (debt_type = 'Daily' OR debt_type IS NULL OR debt_type = '') AND is_paid = 0");
$stmtDebt->execute([$today]);
$debtStats = $stmtDebt->fetch();
$totalLeftoversCount = 0;
foreach ($preview as $p) {
    if ((isset($p['surplus_kg']) && $p['surplus_kg'] > 0.001) || (isset($p['surplus_units']) && $p['surplus_units'] > 0)) {
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

                <?php if ($isClosingToday): ?>
                    <div class="alert alert-warning border-warning shadow-sm mb-4 text-end" dir="rtl">
                        <i class="fas fa-exclamation-triangle ms-2"></i>
                        <strong>تنبيه هام:</strong> أنت تحاول إغلاق "اليوم" (<?= $realToday ?>).
                        هذا سيؤدي إلى نقل جميع المخزون المتبقي إلى الغد وإخفائه من قائمة البيع الحالية.
                        يُفضل إغلاق اليوم فقط في نهاية الدوام.
                    </div>
                <?php endif; ?>

                <p class="lead mt-4 text-center fw-bold">ماذا سيحدث عند الضغط؟</p>
                <ul class="text-end" dir="rtl">
                    <li>حساب الكميات المتبقية وتصديرها كـ <b>"بقايا"</b> لليوم التالي.</li>
                    <li>إغلاق مخزون اليوم ووسمه بـ <b>"مغلق"</b>.</li>
                    <li><b>ترحيل الديون اليومية غير المدفوعة تلقائياً إلى كشف الغد.</b></li>
                </ul>
                <p class="text-warning text-center"><i class="fas fa-exclamation-triangle"></i> يرجى التأكد من مراجعة الجدول أدناه قبل الإغلاق.</p>

                <table class="table table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>النوع</th>
                            <th>المشترى</th>
                            <th>المباع</th>
                            <th>سينتقل للبقايا</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $p): ?>
                            <?php if ($p['surplus_kg'] > 0.001 || $p['surplus_units'] > 0): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td>
                                        <?php if ($p['bought_kg'] > 0) echo number_format($p['bought_kg'], 3) . ' كجم<br>'; ?>
                                        <?php if ($p['bought_units'] > 0) echo $p['bought_units'] . ' وحدة'; ?>
                                        <?php if ($p['bought_kg'] == 0 && $p['bought_units'] == 0) echo '-'; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['sold_kg'] > 0) echo number_format($p['sold_kg'], 3) . ' كجم<br>'; ?>
                                        <?php if ($p['sold_units'] > 0) echo $p['sold_units'] . ' وحدة'; ?>
                                        <?php if ($p['sold_kg'] == 0 && $p['sold_units'] == 0) echo '-'; ?>
                                    </td>
                                    <td class="fw-bold text-danger">
                                        <?php if ($p['surplus_kg'] > 0.001) echo number_format($p['surplus_kg'], 3) . ' كجم<br>'; ?>
                                        <?php if ($p['surplus_units'] > 0) echo $p['surplus_units'] . ' وحدة'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($totalLeftoversCount === 0): ?>
                            <tr>
                                <td colspan="4" class="text-muted py-3">لا توجد أصناف باقية لترحيلها</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <form action="requests/close_day.php" method="POST">
                    <input type="hidden" name="date" value="<?= $today ?>">
                    <button type="submit" class="btn btn-danger w-100 btn-lg mb-3" onclick="return confirm('هل أنت متأكد من إغلاق اليوم (الوردية)؟ سيتم ترحيل جميع البضاعة الجاهزة للمراحل التالية.');">إغلاق الوردية وترحيل البضاعة</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>