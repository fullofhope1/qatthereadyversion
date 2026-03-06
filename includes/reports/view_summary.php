<?php
// includes/reports/view_summary.php
?>
<div class="d-flex justify-content-end mb-4 no-print">
    <a href="?view=Printable&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-dark rounded-pill px-4 shadow-sm">
        <i class="fas fa-print me-2 text-warning"></i> طباعة التقرير الشامل
    </a>
</div>

<?php
// 1. SALES BREAKDOWN
$stmt = $pdo->prepare("SELECT
SUM(CASE WHEN payment_method = 'Cash' THEN price ELSE 0 END) as cash_sales,
SUM(CASE WHEN payment_method = 'Debt' THEN price ELSE 0 END) as debt_sales,
SUM(CASE WHEN payment_method NOT IN ('Cash', 'Debt') THEN price ELSE 0 END) as transfer_sales,
SUM(CASE WHEN qat_status = 'Momsi' THEN price ELSE 0 END) as momsi_sales,
COUNT(*) as total_invoices
FROM sales $whereSQL_Sales");
$stmt->execute($params);
$salesSummary = $stmt->fetch();

// 2. PAYMENTS COLLECTED (Inflow from previous debts)
// Note: We need a where clause for payments using the same report template logic
if ($reportType === 'Monthly') $whereSQL_Pay = "WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?";
elseif ($reportType === 'Yearly') $whereSQL_Pay = "WHERE YEAR(payment_date) = ?";
else $whereSQL_Pay = "WHERE payment_date = ?";

$stmt = $pdo->prepare("SELECT SUM(amount) FROM payments $whereSQL_Pay");
$stmt->execute($params);
$collectedPayments = $stmt->fetchColumn() ?: 0;

// 3. DEPOSITS (Outflow to currency exchanges/owners)
if ($reportType === 'Monthly') $whereSQL_Dep = "WHERE DATE_FORMAT(deposit_date, '%Y-%m') = ?";
elseif ($reportType === 'Yearly') $whereSQL_Dep = "WHERE YEAR(deposit_date) = ?";
else $whereSQL_Dep = "WHERE deposit_date = ?";

$stmt = $pdo->prepare("SELECT currency, SUM(amount) as total FROM qat_deposits $whereSQL_Dep GROUP BY currency");
$stmt->execute($params);
$depositsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$depositsYER = $depositsRaw['YER'] ?? 0;
$depositsSAR = $depositsRaw['SAR'] ?? 0;
$depositsUSD = $depositsRaw['USD'] ?? 0;

// 4. REFUNDS DETAIL
$cashRefunds = 0;
$debtRefunds = 0;
foreach ($listRefunds as $r) {
    if ($r['refund_type'] === 'Cash') $cashRefunds += $r['amount'];
    else $debtRefunds += $r['amount'];
}

// 5. LEFTOVERS BREAKDOWN (for the period)
if ($reportType === 'Monthly') $whereSQL_Lef = "WHERE DATE_FORMAT(source_date, '%Y-%m') = ?";
elseif ($reportType === 'Yearly') $whereSQL_Lef = "WHERE YEAR(source_date) = ?";
else $whereSQL_Lef = "WHERE source_date = ?";

$stmt = $pdo->prepare("SELECT
    SUM(CASE WHEN status IN ('Dropped') THEN weight_kg ELSE 0 END) as manual_dropped_kg,
    SUM(CASE WHEN status = 'Auto_Dropped' THEN weight_kg ELSE 0 END) as auto_dropped_kg,
    SUM(CASE WHEN status = 'Transferred_Next_Day' THEN weight_kg ELSE 0 END) as manual_momsi_kg,
    SUM(CASE WHEN status = 'Auto_Momsi' THEN weight_kg ELSE 0 END) as auto_momsi_kg
    FROM leftovers $whereSQL_Lef");
$stmt->execute($params);
$leftoversSummary = $stmt->fetch();
$totalDroppedKg  = ($leftoversSummary['manual_dropped_kg'] ?? 0) + ($leftoversSummary['auto_dropped_kg'] ?? 0);
$totalMomsiKg    = ($leftoversSummary['manual_momsi_kg'] ?? 0) + ($leftoversSummary['auto_momsi_kg'] ?? 0);

// 6. FINAL TOTALS
$totalInflow = $salesSummary['cash_sales'] + $collectedPayments;
$totalOutflow = $totalExpenses + $cashRefunds + $depositsYER;
$remainingCash = $totalInflow - $totalOutflow;

?>

<style>
    .summary-card {
        border-radius: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none !important;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    }

    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .text-value {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .progress-micro {
        height: 4px;
        border-radius: 2px;
        margin: 1rem 0;
    }
</style>

<div class="row g-4 mb-4">
    <!-- Sales Overview -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm summary-card border-top border-warning border-5">
            <div class="card-body p-4">
                <div class="icon-box bg-warning-subtle text-warning">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h6 class="text-muted fw-bold text-uppercase small mb-3">ملخص المبيعات الكلي</h6>
                <div class="text-value text-dark"><?= number_format($totalSales) ?> <small class="fs-6 fw-normal">ريال</small></div>

                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">مبيعات نقدي:</span>
                        <span class="fw-bold"><?= number_format($salesSummary['cash_sales']) ?></span>
                    </div>
                    <div class="progress progress-micro">
                        <div class="progress-bar bg-warning" style="width: <?= $totalSales > 0 ? ($salesSummary['cash_sales'] / $totalSales * 100) : 0 ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">مبيعات آجل:</span>
                        <span class="fw-bold"><?= number_format($salesSummary['debt_sales']) ?></span>
                    </div>
                    <div class="progress progress-micro">
                        <div class="progress-bar bg-danger" style="width: <?= $totalSales > 0 ? ($salesSummary['debt_sales'] / $totalSales * 100) : 0 ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">حوالات:</span>
                        <span class="fw-bold"><?= number_format($salesSummary['transfer_sales']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inflow Overview -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm summary-card border-top border-success border-5">
            <div class="card-body p-4">
                <div class="icon-box bg-success-subtle text-success">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <h6 class="text-muted fw-bold text-uppercase small mb-3">النقد الداخل (المستلم)</h6>
                <div class="text-value text-success"><?= number_format($totalInflow) ?> <small class="fs-6 fw-normal">ريال</small></div>

                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">المبيعات النقدية:</span>
                        <span class="fw-bold"><?= number_format($salesSummary['cash_sales']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">سداد ديون قديمة:</span>
                        <span class="fw-bold text-primary">+ <?= number_format($collectedPayments) ?></span>
                    </div>
                    <div class="bg-light p-2 rounded text-center small text-muted">
                        <i class="fas fa-info-circle me-1"></i> هذا المبلغ يمثل إجمالي الكاش الذي دخل الصندوق
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outflow Overview -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm summary-card border-top border-danger border-5">
            <div class="card-body p-4">
                <div class="icon-box bg-danger-subtle text-danger">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <h6 class="text-muted fw-bold text-uppercase small mb-3">النقد الخارج (المصروفات)</h6>
                <div class="text-value text-danger"><?= number_format($totalOutflow) ?> <small class="fs-6 fw-normal">ريال</small></div>

                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">مصاريف التشغيل:</span>
                        <span class="fw-bold"><?= number_format($totalExpenses) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">مرتجعات العملاء:</span>
                        <span class="fw-bold"><?= number_format($cashRefunds) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-primary">
                        <span>إيداعات البنك/المندوب:</span>
                        <span class="fw-bold"><?= number_format($depositsYER) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Secondary Stats -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-light" style="border-radius: 15px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-box-open me-2 text-primary"></i> تحليل الشراء والمبيعات النوعية</h6>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <div class="text-muted small mb-1">تكلفة التوريد</div>
                        <div class="h4 fw-bold mb-0"><?= number_format($totalPurchases) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small mb-1">مبيعات البقايا</div>
                        <div class="h4 fw-bold mb-0 text-info"><?= number_format($salesSummary['momsi_sales']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-dark text-white" style="border-radius: 15px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4 text-warning"><i class="fas fa-coins me-2"></i> تسليم العملات (صافي خارج العهدة)</h6>
                <div class="row text-center">
                    <div class="col-4 border-end border-secondary">
                        <div class="text-white-50 small mb-1">يمني</div>
                        <div class="h5 fw-bold mb-0 text-white"><?= number_format($depositsYER) ?></div>
                    </div>
                    <div class="col-4 border-end border-secondary">
                        <div class="text-white-50 small mb-1">سعودي</div>
                        <div class="h5 fw-bold mb-0 text-info"><?= number_format($depositsSAR, 2) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-white-50 small mb-1">دولار</div>
                        <div class="h5 fw-bold mb-0 text-primary"><?= number_format($depositsUSD, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leftovers Breakdown Card -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 15px; border-right: 5px solid #dc3545 !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-box-open me-2 text-danger"></i> تقرير البقايا — التالف والممسي</h6>
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                            <div class="text-danger small fw-bold mb-1"><i class="fas fa-trash-alt me-1"></i> إتلاف يدوي</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($leftoversSummary['manual_dropped_kg'] ?? 0, 2) ?></div>
                            <div class="text-muted small">كجم</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-danger bg-opacity-10 rounded-3 p-3" style="opacity: 0.75;">
                            <div class="text-danger small fw-bold mb-1"><i class="fas fa-robot me-1"></i> إتلاف تلقائي</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($leftoversSummary['auto_dropped_kg'] ?? 0, 2) ?></div>
                            <div class="text-muted small">كجم</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <div class="text-primary small fw-bold mb-1"><i class="fas fa-arrow-right me-1"></i> ممسي يدوي</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($leftoversSummary['manual_momsi_kg'] ?? 0, 2) ?></div>
                            <div class="text-muted small">كجم</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <div class="text-info small fw-bold mb-1"><i class="fas fa-robot me-1"></i> ممسي تلقائي</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($leftoversSummary['auto_momsi_kg'] ?? 0, 2) ?></div>
                            <div class="text-muted small">كجم</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top d-flex justify-content-around text-center">
                    <div>
                        <div class="text-muted small">إجمالي التالف</div>
                        <div class="h5 fw-bold text-danger mb-0"><?= number_format($totalDroppedKg, 2) ?> كجم</div>
                    </div>
                    <div class="vr"></div>
                    <div>
                        <div class="text-muted small">إجمالي الممسي (المرحّل)</div>
                        <div class="h5 fw-bold text-primary mb-0"><?= number_format($totalMomsiKg, 2) ?> كجم</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Final Custody Result -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg <?= $remainingCash >= 0 ? 'bg-gradient-success' : 'bg-gradient-danger' ?> text-white p-4"
            style="border-radius: 20px; background: <?= $remainingCash >= 0 ? 'linear-gradient(135deg, #1D976C 0%, #93F9B9 100%)' : 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)' ?>;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-3 mb-md-0">
                    <h3 class="mb-1 fw-bold"><i class="fas fa-vault me-2"></i> الرصيد المتبقي في العهدة (الكاش)</h3>
                    <p class="mb-0 opacity-75">المبلغ المفترض تواجده حالياً مع المحاسب (بعد خصم المصاريف والإيداعات)</p>
                </div>
                <div class="text-center text-md-end">
                    <div class="display-4 fw-bold mb-0"><?= number_format($remainingCash) ?></div>
                    <div class="h5 fw-normal opacity-75 mb-0">ريال يمني</div>
                </div>
            </div>
        </div>
    </div>
</div>