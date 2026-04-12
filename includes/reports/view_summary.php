<?php
// includes/reports/view_summary.php
?>
<div class="d-flex justify-content-end mb-4 no-print">
    <a href="?view=Printable&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-dark rounded-pill px-4 shadow-sm">
        <i class="fas fa-print me-2 text-warning"></i> طباعة التقرير الشامل
    </a>
</div>

<?php
// includes/reports/view_summary.php

$breakdowns = $service->getSummaryBreakdowns($reportType, $date, $month, $year);
$salesSummary = $breakdowns['sales'];
$leftoversSummary = $breakdowns['leftovers'];
$depositsRaw = $breakdowns['deposits'];

$depositsYER = $depositsRaw['YER'] ?? 0;
$depositsSAR = $depositsRaw['SAR'] ?? 0;
$depositsUSD = $depositsRaw['USD'] ?? 0;

// 4. REFUNDS DETAIL
$cashRefunds = 0;
$debtRefunds = 0;
foreach ($listRefunds as $r) {
    if ($r['refund_type'] === 'Cash') $cashRefunds += $r['amount'];
}
$totalUnknownTransfers = (float)($salesSummary['total_unknown_transfers'] ?? 0);
$totalInflow = $salesSummary['cash_sales'] + $collectedPayments + $totalUnknownTransfers;
$totalOutflow = $totalExpenses + $cashRefunds + $depositsYER;
$remainingCash = $totalInflow - $totalOutflow;

$totalDroppedKg  = ($leftoversSummary['manual_dropped_kg'] ?? 0) + ($leftoversSummary['auto_dropped_kg'] ?? 0);
$totalMomsiKg    = ($leftoversSummary['manual_momsi_kg'] ?? 0) + ($leftoversSummary['auto_momsi_kg'] ?? 0);
?>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse-soft {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.02);
        }

        100% {
            transform: scale(1);
        }
    }

    .summary-card {
        border-radius: 24px;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(15px);
        animation: fadeInUp 0.6s ease-out both;
    }

    .summary-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--royal-shadow) !important;
        background: #fff !important;
    }

    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
    }

    .text-value {
        font-size: 2.2rem;
        font-weight: 900;
        margin-bottom: 0.25rem;
        letter-spacing: -1px;
    }

    .progress-micro {
        height: 6px;
        border-radius: 10px;
        margin: 1.25rem 0;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .badge-premium {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
                    <?php if ($totalUnknownTransfers > 0): ?>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">تحويلات مجهولة مستلمة:</span>
                        <span class="fw-bold text-warning">+ <?= number_format($totalUnknownTransfers) ?></span>
                    </div>
                    <?php endif; ?>
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

<!-- Final Custody Result (The Performance Badge) -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg text-white p-5 overflow-hidden"
            style="border-radius: 30px; background: <?= $remainingCash >= 0 ? 'var(--primary-gradient)' : 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)' ?>; animation: fadeInUp 1s ease-out; position: relative;">

            <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>

            <div class="row align-items-center position-relative">
                <div class="col-md-7 mb-4 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white bg-opacity-20 p-3 rounded-4 me-3">
                            <i class="fas fa-vault fs-2"></i>
                        </div>
                        <h2 class="mb-0 fw-bold">العهدة النقدية النهائية</h2>
                    </div>
                    <p class="mb-0 opacity-75 fs-5">المستلم الفعلي المفترض تواجده حالياً مع المحاسب (بعد تصفية كافة العمليات)</p>
                </div>
                <div class="col-md-5 text-center text-md-end">
                    <div class="display-3 fw-black mb-0" style="text-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                        <?= number_format($remainingCash) ?>
                    </div>
                    <div class="h4 fw-light opacity-75 mb-0">ريال يمني</div>
                    <?php if ($remainingCash < 0): ?>
                        <div class="badge bg-white text-danger mt-3 badge-premium px-3 py-2 animation-pulse">عجز مالي</div>
                    <?php else: ?>
                        <div class="badge bg-white text-success mt-3 badge-premium px-3 py-2">رصيد إيجابي</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>