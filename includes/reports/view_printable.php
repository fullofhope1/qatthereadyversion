<?php
// includes/reports/view_printable.php

// 1. SALES BREAKDOWN (Paper specific)
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN payment_method = 'Cash' THEN price ELSE 0 END) as cash_sales,
    SUM(CASE WHEN payment_method = 'Debt' THEN price ELSE 0 END) as debt_sales,
    SUM(CASE WHEN payment_method NOT IN ('Cash', 'Debt') THEN price ELSE 0 END) as transfer_sales
    FROM sales $whereSQL_Sales");
$stmt->execute($params);
$ps = $stmt->fetch();

// 2. PAYMENTS COLLECTED
if ($reportType === 'Monthly') {
    $whereSQL_Pay = "WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?";
} elseif ($reportType === 'Yearly') {
    $whereSQL_Pay = "WHERE YEAR(payment_date) = ?";
} else {
    $whereSQL_Pay = "WHERE payment_date = ?";
}

$stmt = $pdo->prepare("SELECT SUM(amount) FROM payments $whereSQL_Pay");
$stmt->execute($params);
$collectedPayments = $stmt->fetchColumn() ?: 0;

// 3. DEPOSITS Breakdown
$stmt = $pdo->prepare("SELECT currency, SUM(amount) as total FROM qat_deposits $whereSQL_Dep GROUP BY currency");
$stmt->execute($params);
$depositsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$depYER = $depositsRaw['YER'] ?? 0;
$depSAR = $depositsRaw['SAR'] ?? 0;
$depUSD = $depositsRaw['USD'] ?? 0;

// 4. REFUNDS
$stmt = $pdo->prepare("SELECT SUM(amount) FROM refunds r $whereSQL_Ref AND refund_type = 'Cash'");
$stmt->execute($params);
$cashRefunds = $stmt->fetchColumn() ?: 0;

// 5. CALCULATIONS for Paper
$totalSalesWithTransfers = $ps['cash_sales'] + $ps['transfer_sales'];
$totalCashInflow = $ps['cash_sales'] + $collectedPayments;
$netResult = $totalCashInflow - $totalExpenses - $cashRefunds - $depYER;

// Fetch detailed lists
$stmt = $pdo->prepare("SELECT s.*, c.name as cust_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id $whereSQL_Sales AND payment_method = 'Debt' ORDER BY s.id DESC");
$stmt->execute($params);
$listDebtSales = $stmt->fetchAll();

// Formatting Period Display
if ($reportType === 'Monthly') {
    $periodDisplay = "شهر: " . date('Y / m', strtotime($month . "-01"));
} elseif ($reportType === 'Yearly') {
    $periodDisplay = "سنة: " . $year;
} else {
    $periodDisplay = "تاريخ: " . date('Y / m / d', strtotime($date));
}
?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin-bottom: 0 !important;
        }

        body {
            background: white !important;
            font-size: 14px;
        }

        .container-fluid {
            padding: 0 !important;
        }
    }

    .print-header {
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    .report-table th,
    .report-table td {
        border: 1px solid #444;
        padding: 8px;
        text-align: right;
    }

    .report-table th {
        background-color: #f8f9fa;
    }

    .summary-box {
        border: 2px solid #000;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 3px;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .grand-total-box {
        background: #f8f9fa;
        color: #000;
        padding: 20px;
        border: 2px solid #000;
        border-radius: 10px;
        text-align: center;
    }

    @media print {
        .grand-total-box {
            background: #fff !important;
            color: #000 !important;
        }
    }
</style>

<style>
    .btn-print-report {
        background: #212529;
        color: #fff;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 700;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .btn-print-report:hover {
        background: #000;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        color: #fff;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h4 class="mb-0 text-muted"><i class="fas fa-print me-2"></i> معاينة قبل الطباعة</h4>
    <button onclick="triggerPrint()" class="btn btn-print-report">
        <i class="fas fa-print me-2 text-warning"></i> طباعة التقرير الورقي (Ctrl+P)
    </button>
</div>

<div id="printableArea" class="card shadow-sm border-0 p-4 printable-area">
    <!-- Header -->
    <div class="print-header text-center">
        <div class="mb-3">
            <img src="logo.jpg" alt="Logo" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #000;">
        </div>
        <h2 class="fw-bold mb-1">القادري و ماجد - لأجود أنواع القات</h2>
        <div class="text-muted mb-2">ت: 775065459 - 774456261</div>
        <div class="row mt-3">
            <div class="col-6 text-start">🗓️ <?= $periodDisplay ?></div>
            <div class="col-6 text-end fw-bold">خلاصة الحركة المالية</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="row g-4">
        <!-- Section 1: Financial Summary -->
        <div class="col-md-4">
            <div class="summary-box">
                <h6 class="fw-bold mb-3 text-center border-bottom pb-2">ملخص المبيعات (خلاصة)</h6>
                <div class="summary-item">
                    <span>إجمالي المبيعات (نقداً + حوالات):</span>
                    <span class="fw-bold"><?= number_format($totalSalesWithTransfers) ?></span>
                </div>
                <div class="summary-item">
                    <span>إجمالي البيع نقداً:</span>
                    <span class="fw-bold"><?= number_format($ps['cash_sales']) ?></span>
                </div>
                <div class="summary-item">
                    <span>إجمالي حوالات:</span>
                    <span class="fw-bold"><?= number_format($ps['transfer_sales']) ?></span>
                </div>
                <div class="summary-item">
                    <span>إجمالي النقد الواصل (سداد):</span>
                    <span class="fw-bold"><?= number_format($collectedPayments) ?></span>
                </div>
                <div class="summary-item">
                    <span>إجمالي المدين (آجل):</span>
                    <span class="fw-bold"><?= number_format($ps['debt_sales']) ?></span>
                </div>
                <hr>
                <div class="summary-item text-primary h5 fw-bold">
                    <span>إجمالي بيع الكاش:</span>
                    <span><?= number_format($totalCashInflow) ?></span>
                </div>
                <div class="summary-item text-danger">
                    <span>إجمالي المصروف (والمرتجع):</span>
                    <span><?= number_format($totalExpenses + $cashRefunds) ?></span>
                </div>
                <div class="summary-item <?= $netResult >= 0 ? 'text-success' : 'text-danger' ?> h4 fw-bold mt-2 pt-2 border-top">
                    <span>صافي الربح / الخسارة:</span>
                    <span><?= number_format($netResult) ?></span>
                </div>
            </div>
        </div>

        <!-- Section 2: Incoming (Sourcing) -->
        <div class="col-md-4">
            <h6 class="fw-bold mb-2"><i class="fas fa-truck me-2"></i> التوريد (المشتريات)</h6>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>البيان (الرعوي)</th>
                        <th class="text-end">المبلغ</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php
                    $totalIn = 0;
                    foreach ($listPurch as $p):
                        $totalIn += $p['net_cost'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($p['prov_name']) ?> (<?= $p['type_name'] ?>)</td>
                            <td class="text-end"><?= number_format($p['net_cost']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($listPurch)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-2">لا يوجد توريد</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="fw-bold">
                    <tr class="table-light">
                        <td>الإجمالي</td>
                        <td class="text-end"><?= number_format($totalIn) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Section 3: Expenses -->
        <div class="col-md-4">
            <h6 class="fw-bold mb-2"><i class="fas fa-wallet me-2"></i> المصاريف (الخارليات)</h6>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>البيان (التفاصيل)</th>
                        <th class="text-end">المبلغ</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php foreach ($listExp as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td class="text-end"><?= number_format($e['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($listExp)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-2">لا توجد مصاريف</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="fw-bold">
                    <tr class="table-light">
                        <td>الإجمالي</td>
                        <td class="text-end"><?= number_format($totalExpenses) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Currency Handed Over section -->
    <div class="row mt-4">
        <div class="col-12">
            <h6 class="fw-bold mb-2">إجمالي المبالغ المودعة للصراف أو المسلمة للمندوب:</h6>
            <table class="report-table text-center">
                <thead>
                    <tr>
                        <th>يمني (YER)</th>
                        <th>سعودي (SAR)</th>
                        <th>دولار (USD)</th>
                        <th class="bg-dark text-white">الإجمالي العام</th>
                    </tr>
                </thead>
                <tbody class="fw-bold fs-5">
                    <tr>
                        <td><?= number_format($depYER) ?></td>
                        <td><?= number_format($depSAR, 2) ?></td>
                        <td><?= number_format($depUSD, 2) ?></td>
                        <td class="bg-light"><?= number_format($depYER) ?> <span class="small">(YER)</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mt-4 mb-2">
        <div class="col-12">
            <div class="grand-total-box">
                <div class="h5 mb-1">المتبقي في الصندوق (العهدة النهائية):</div>
                <div class="h1 fw-bold mb-0"><?= number_format($remainingCash) ?> ريال يمني</div>
            </div>
        </div>
    </div>

    <div class="row mt-5 pt-3">
        <div class="col-4 text-center">توقيع المحاسب: .....................</div>
        <div class="col-4 text-center">توقيع المندوب: .....................</div>
        <div class="col-4 text-center">ملاحظات: ............................</div>
    </div>
</div>