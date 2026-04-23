<?php
// includes/reports/view_printable.php

// 1. DATA PREP
$breakdowns = $service->getSummaryBreakdowns($reportType, $date, $month, $year);
$ps = $breakdowns['sales'];
$depositsRaw = $breakdowns['deposits'];
$waste = $breakdowns['waste_stats'];

$depYER = $depositsRaw['YER'] ?? 0;
$depSAR = $depositsRaw['SAR'] ?? 0;
$depUSD = $depositsRaw['USD'] ?? 0;

$cashSummary = $service->getCashSummary($reportType, $date, $month, $year);
$totalReceivables = $reportRepo->getTotalReceivables();

$waselList = $reportRepo->getDetailedPayments($reportType, $date, $month, $year);

// MAPPING LABELS FROM SAMPLE
$waselCash = $cashSummary['wasel_cash'] ?? 0;
$waselTransfer = $cashSummary['wasel_transfer'] ?? 0;
$cashSalesTotal = $ps['cash_sales'] ?? 0;
$transferSalesTotal = $ps['transfer_sales'] ?? 0;
$debtSalesToday = $ps['debt_sales'] ?? 0;
$momsiSales = $ps['momsi_sales'] ?? 0;
$cashRefunds = $cashSummary['cash_refunds'] ?? 0;
$debtRefunds = $cashSummary['debt_refunds'] ?? 0;
$totalRefunds = $cashRefunds + $debtRefunds;

// Surplus Calculation (as Revenue - Cost - Refunds)
$surplusMonetary = $ps['cash_sales'] + $ps['debt_sales'] + $ps['transfer_sales'] - ($totalPurchases ?? 0) - $totalRefunds;
$profitNet = $surplusMonetary - ($totalExpenses ?? 0);

if ($reportType === 'Monthly') {
    $periodDisplay = "شهر: " . date('Y / m', strtotime($month . "-01"));
} elseif ($reportType === 'Yearly') {
    $periodDisplay = "سنة: " . $year;
} else {
    $periodDisplay = date('Y / m / d', strtotime($date));
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Tajawal:wght@400;700&display=swap');

    :root {
        --border-color: #000;
    }

    body {
        background: #fff !important;
        margin: 0;
        padding: 0;
        direction: rtl;
        font-family: 'Tajawal', sans-serif;
    }

    .paper-report {
        width: 210mm;
        min-height: 297mm;
        padding: 10mm;
        margin: auto;
        background: white;
        box-shadow: none;
    }

    @media print {
        @page {
            size: A4;
            margin: 5mm;
        }

        .no-print {
            display: none !important;
        }

        .paper-report {
            width: 100%;
            border: none;
            padding: 0;
        }
    }

    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .header-right {
        text-align: right;
    }

    .header-center {
        text-align: center;
    }

    .header-left {
        text-align: left;
    }

    .main-title {
        font-family: 'Amiri', serif;
        font-size: 24pt;
        font-weight: bold;
        text-decoration: underline;
        margin: 0;
    }

    .grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .grid-table th,
    .grid-table td {
        border: 1px solid var(--border-color);
        padding: 5px 10px;
        text-align: center;
        font-size: 11pt;
    }

    .grid-table th {
        background-color: #f2f2f2;
    }

    .section-title {
        background-color: #eee;
        font-weight: bold;
        text-align: right !important;
    }

    .notes-box {
        height: 150px;
        border: 1px solid var(--border-color);
        padding: 10px;
        position: relative;
    }

    .notes-box::before {
        content: "الملاحظات";
        position: absolute;
        top: -10px;
        right: 10px;
        background: #fff;
        padding: 0 5px;
        font-weight: bold;
        font-size: 10pt;
    }

    .signature-section {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
    }

    .sig-line {
        border-top: 1px solid var(--border-color);
        width: 200px;
        margin-top: 30px;
    }

    .amount-cell {
        font-weight: bold;
        background: #fafafa;
    }

    .label-cell {
        text-align: right !important;
        width: 60%;
    }
</style>

<div class="no-print d-flex justify-content-center py-3">
    <button onclick="window.print()" class="btn btn-dark btn-lg shadow">طباعة التقرير الجديد (A4)</button>
</div>

<div class="paper-report" id="printableArea">
    <!-- Header Area -->
    <div class="report-header">
        <div class="header-right">
            <div class="fw-bold fs-5">إدارة/ القادري وماجد لأجود أنواع القات</div>
            <div>ت: 775065459 - 774456261</div>
        </div>
        <div class="header-center">
            <h1 class="main-title">خلاصة الحركة اليومية</h1>
            <div class="mt-2">
                اليوم: <span class="border-bottom border-dark px-3"><?= date('l') ?></span> &nbsp;&nbsp;
                التاريخ: <span class="border-bottom border-dark px-3"><?= $periodDisplay ?></span>
            </div>
        </div>
        <div class="header-left">
            <img src="logo.jpg" style="width: 80px; filter: grayscale(1);">
        </div>
    </div>

    <!-- Summary Section (Top Right logic) -->
    <div class="row g-0">
        <div class="col-8">
            <table class="grid-table">
                <thead>
                    <tr class="section-title">
                        <th class="label-cell">البيان</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="label-cell">إجمالي المبيعات نقداً مع الواصل</td>
                        <td class="amount-cell"><?= number_format($cashSalesTotal + $waselCash) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">إجمالي البيع نقداً</td>
                        <td class="amount-cell"><?= number_format($cashSalesTotal) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">إجمالي البيع حوالات</td>
                        <td class="amount-cell"><?= number_format($transferSalesTotal) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">إجمالي الواصل نقداً (سداد دين)</td>
                        <td class="amount-cell"><?= number_format($waselCash) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">إجمالي الديون (المبيعات الآجلة)</td>
                        <td class="amount-cell"><?= number_format($debtSalesToday) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">إجمالي الواصل حوالات (سداد دين)</td>
                        <td class="amount-cell"><?= number_format($waselTransfer) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell text-primary fw-bold">إجمالي المديونية (رصيد العملاء)</td>
                        <td class="amount-cell text-primary"><?= number_format($totalReceivables) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">إجمالي بيع القات م م (البقايا)</td>
                        <td class="amount-cell"><?= number_format($momsiSales) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">وزن التالف اليومي (كجم)</td>
                        <td class="amount-cell"><?= number_format($waste['total_weight'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell text-danger fw-bold">إجمالي المرتجعات والتعويضات</td>
                        <td class="amount-cell text-danger"><?= number_format($totalRefunds) ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell fw-bold">إجمالي الفائض (الربح الإجمالي)</td>
                        <td class="amount-cell"><?= number_format($surplusMonetary) ?></td>
                    </tr>
                    <tr style="background: #e0e0e0;">
                        <td class="label-cell fw-bold">صافي الربح أو الخسارة</td>
                        <td class="amount-cell"><?= number_format($profitNet) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-4 ps-2">
            <div class="notes-box"></div>
        </div>
    </div>

    <!-- Details Section (Wasel & Expenses) -->
    <div class="row mt-4 g-0">
        <div class="col-6 pe-1">
            <table class="grid-table">
                <thead>
                    <tr class="section-title">
                        <th colspan="2" class="text-center">الواصل / سداد ديون</th>
                    </tr>
                    <tr>
                        <th>البيان</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totW = 0;
                    foreach ($waselList as $w):
                        $totW += $w['amount'];
                    ?>
                        <tr>
                            <td class="text-end small"><?= htmlspecialchars($w['customer_name']) ?></td>
                            <td><?= number_format($w['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php for ($i = count($waselList); $i < 8; $i++) echo "<tr><td class='py-3'></td><td></td></tr>"; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>إجمالي الواصل</td>
                        <td><?= number_format($totW) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="col-6 ps-1">
            <table class="grid-table">
                <thead>
                    <tr class="section-title">
                        <th colspan="2" class="text-center">المنصرف / النثريات</th>
                    </tr>
                    <tr>
                        <th>البيان</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totE = 0;
                    foreach ($listExp as $e):
                        $totE += $e['amount'];
                    ?>
                        <tr>
                            <td class="text-end small"><?= htmlspecialchars($e['description']) ?></td>
                            <td><?= number_format($e['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php for ($i = count($listExp); $i < 6; $i++) echo "<tr><td class='py-3'></td><td></td></tr>"; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>الإجمالي</td>
                        <td><?= number_format($totE) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Bottom Section (Banking/Deposits) -->
    <div class="mt-4 border border-dark p-3">
        <div class="fw-bold text-center mb-2">إجمالي المبلغ المودع للصراف أو المسلم للقادري</div>
        <div class="row">
            <div class="col-8">
                <table class="grid-table">
                    <thead>
                        <tr>
                            <th>يمني</th>
                            <th>سعودي</th>
                            <th>دولار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold"><?= number_format($depYER) ?></td>
                            <td class="fw-bold"><?= number_format($depSAR, 2) ?></td>
                            <td class="fw-bold"><?= number_format($depUSD, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-4 d-flex flex-column justify-content-center border border-dark border-start-0 text-center">
                <div class="small fw-bold">الإجمالي العام (مع الرصيد)</div>
                <div class="h3 fw-black mb-0"><?= number_format($cashSummary['remaining_cash']) ?></div>
            </div>
        </div>
    </div>

    <!-- Final Footer -->
    <div class="signature-section">
        <div class="text-center">
            <div>المحاسب المسؤول</div>
            <div class="sig-line"></div>
        </div>
        <div class="text-center">
            <div>المندوب / المورد</div>
            <div class="sig-line"></div>
        </div>
    </div>
</div>