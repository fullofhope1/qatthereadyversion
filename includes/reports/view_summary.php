<?php
// 1. DATA AGGREGATION
// $overview already contains totals (real_profit, total_sales, etc.) from reports.php
$totals = $overview; // Passed from reports.php via $service->getOverviewData()
$breakdowns = $service->getSummaryBreakdowns($reportType, $date, $month, $year);
$salesSummary = $breakdowns['sales']; // Contains cash_sales, transfer_sales, momsi1_sales, momsi2_sales
$cashSummary = $service->getCashSummary($reportType, $date, $month, $year, $report_user_id); 

// Calculation for General Total (Drawer Cash Result)
// This is now calculated centrally in ReportService::getCashSummary
$cashInHandResult = $cashSummary['remaining_cash'] ?? 0;
?>

<div class="container py-3">
    <!-- Final Card Summary -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #f8f9fa;">
        <div class="card-header bg-dark text-white p-4 border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1 fw-bold text-warning"><i class="fas fa-file-invoice-dollar me-2"></i> المحصلة المالية النهائية</h3>
                    <p class="mb-0 opacity-75 small">تقرير <?= ($reportType === 'Daily' ? 'يومي' : ($reportType === 'Monthly' ? 'شهري' : 'سنوي')) ?> | التاريخ: <?= $date ?: 'محدد' ?></p>
                </div>
                <div class="text-end no-print">
                     <button onclick="window.print()" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-print me-2"></i> طباعة التقرير
                     </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0 bg-white">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="direction: rtl;">
                    <thead class="bg-light">
                        <tr class="text-muted small uppercase">
                            <th class="ps-4 py-3 fw-bold" style="width: 65%">البيـــــــــــــــــــــــــــــــــان المالي</th>
                            <th class="pe-4 py-3 text-end fw-bold">القيمة (ريال)</th>
                        </tr>
                    </thead>
                    <tbody class="fs-6">
                        <!-- 1. CASH SALES -->
                        <tr class="clickable-row" onclick="showDetails('Sales', 'إجمالي البيع نقداً')" style="cursor: pointer;">
                            <td class="ps-4 py-3"><i class="fas fa-money-bill-alt text-success me-3"></i> 1. إجمالي البيع نقداً</td>
                            <td class="pe-4 py-3 text-end fw-bold text-dark fs-5"><?= number_format($cashSummary['cash_sales']) ?></td>
                        </tr>
                        
                        <!-- 2. CASH COLLECTIONS -->
                        <tr class="bg-light bg-opacity-50 clickable-row" onclick="showDetails('Payments', 'إجمالي الواصل نقداً')" style="cursor: pointer;">
                            <td class="ps-4 py-3"><i class="fas fa-hand-holding-usd text-primary me-3"></i> 2. إجمالي الواصل (دين مسدد) نقداً</td>
                            <td class="pe-4 py-3 text-end fw-bold text-primary fs-5"><?= number_format($cashSummary['wasel_cash']) ?></td>
                        </tr>
                        
                        <!-- 3. TRANSFER SALES -->
                        <tr class="clickable-row" onclick="showDetails('Sales', 'إجمالي البيع حوالات')" style="cursor: pointer;">
                            <td class="ps-4 py-3"><i class="fas fa-university text-info me-3"></i> 3. إجمالي البيع حوالات</td>
                            <td class="pe-4 py-3 text-end fw-bold text-info fs-5"><?= number_format($cashSummary['transfer_sales']) ?></td>
                        </tr>
                        
                        <!-- 4. TRANSFER COLLECTIONS -->
                        <tr class="bg-light bg-opacity-50 clickable-row" onclick="showDetails('Payments', 'إجمالي الواصل حوالات')" style="cursor: pointer;">
                            <td class="ps-4 py-3"><i class="fas fa-exchange-alt text-info me-3"></i> 4. إجمالي الواصل (دين مسدد) حوالات</td>
                            <td class="pe-4 py-3 text-end fw-bold text-info fs-5"><?= number_format($cashSummary['wasel_transfer']) ?></td>
                        </tr>

                        <!-- 5. NEW DEBT TODAY -->
                        <tr class="bg-danger bg-opacity-10 clickable-row" onclick="showDetails('DebtSales', 'الديون الجديدة اليوم')" style="cursor: pointer;">
                            <td class="ps-4 py-3 fw-bold text-danger"><i class="fas fa-user-plus me-3"></i> 5. إجمالي الديون الجديدة (اليوم)</td>
                            <td class="pe-4 py-3 text-end fw-bold text-danger fs-5"><?= number_format($cashSummary['today_debt_sales']) ?></td>
                        </tr>

                        <!-- GLOBAL DEBT - REAL TIME -->
                        <tr class="border-top border-4 border-white">
                            <td class="ps-4 py-3 bg-secondary bg-opacity-10 fw-bold text-dark"><i class="fas fa-users-cog me-3"></i> إجمالي مديونية العملاء (كافة الأوقات)</td>
                            <td class="pe-4 py-3 text-end fw-bold text-dark fs-5 bg-secondary bg-opacity-10"><?= number_format($cashSummary['total_global_debt']) ?></td>
                        </tr>

                        <!-- 6. MOMSI SALES -->
                        <tr>
                            <td class="ps-4 py-3"><i class="fas fa-leaf text-success me-3"></i> 6. إجمالي بيع القات الممسي أول</td>
                            <td class="pe-4 py-3 text-end fw-bold text-dark"><?= number_format($salesSummary['momsi1_sales'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="ps-4 py-3"><i class="fas fa-leaf text-warning me-3"></i> 7. إجمالي بيع القات الممسي الثاني</td>
                            <td class="pe-4 py-3 text-end fw-bold text-dark"><?= number_format($salesSummary['momsi2_sales'] ?? 0) ?></td>
                        </tr>

                        <!-- 7. REFUNDS & COMPENSATIONS -->
                        <tr class="border-top border-2">
                            <td class="ps-4 py-3"><i class="fas fa-undo text-danger me-3"></i> 8. إجمالي المرتجعات</td>
                            <td class="pe-4 py-3 text-end fw-bold text-danger"><?= number_format($cashSummary['total_refunds']) ?></td>
                        </tr>
                        <tr>
                            <td class="ps-4 py-3"><i class="fas fa-gift text-dark me-3"></i> 9. إجمالي التعويضات</td>
                            <td class="pe-4 py-3 text-end fw-bold text-dark"><?= number_format($cashSummary['total_compensations']) ?></td>
                        </tr>

                        <!-- 8. EXPENSES -->
                        <tr class="bg-light clickable-row" onclick="showDetails('Expenses', 'إجمالي المصاريف')" style="cursor: pointer;">
                            <td class="ps-4 py-3 fw-bold text-danger"><i class="fas fa-minus-circle me-3"></i> 10. إجمالي الخرج (المصاريف)</td>
                            <td class="pe-4 py-3 text-end fw-bold text-danger"><?= number_format($cashSummary['total_expenses']) ?></td>
                        </tr>

                        <!-- 9. PROVIDER PAYMENTS (Hidden as per user request - handled in statements) -->
                        <?php /*
                        <tr class="bg-light border-top">
                            <td class="ps-4 py-3 fw-bold text-primary"><i class="fas fa-truck-loading me-3"></i> 11. إجمالي المبالغ المسلمة للموردين (تسديد)</td>
                            <td class="pe-4 py-3 text-end fw-bold text-primary"><?= number_format($totals['total_provider_payments'] ?? 0) ?></td>
                        </tr>
                        */ ?>
                        <!-- 11. INVENTORY VALUE -->
                        <tr class="bg-warning bg-opacity-10 border-top border-4 border-white">
                            <td class="ps-4 py-3 fw-bold text-dark"><i class="fas fa-boxes me-3 text-warning"></i> 11. إجمالي قيمة المخزون الحالي (بضاعة لم تُبع)</td>
                            <td class="pe-4 py-3 text-end fw-bold text-dark fs-5"><?= number_format($totals['inventory_value'] ?? 0) ?></td>
                        </tr>

                        <!-- 11. PURCHASE DISCOUNTS -->
                        <tr class="bg-success bg-opacity-10 border-top border-4 border-white">
                            <td class="ps-4 py-3 fw-bold text-success"><i class="fas fa-tag me-3"></i> 11. مكرر: إجمالي الخصومات المكتسبة (من الموردين)</td>
                            <td class="pe-4 py-3 text-end fw-bold text-success fs-5"><?= number_format($totals['total_purchase_discounts'] ?? 0) ?></td>
                        </tr>

                        <!-- 10. PROFIT -->
                        <tr class="bg-success text-white border-top border-4 border-white shadow-sm">
                            <td class="ps-4 py-4 fw-bold fs-5"><i class="fas fa-chart-line me-3"></i> 12. صافي الربح أو الخسارة الحقيقي</td>
                            <td class="pe-4 py-4 text-end fw-bold fs-3"><?= number_format($totals['real_profit']) ?></td>
                        </tr>

                        <!-- 11. DEPOSITS -->
                        <tr>
                            <td class="ps-4 py-3"><i class="fas fa-vault text-secondary me-3"></i> 13. إجمالي المبلغ المسلم (الإيداعات):</td>
                            <td class="pe-4 py-3 text-end">
                                <span class="fw-bold fs-5 text-dark"><?= number_format($cashSummary['deposits_yer']) ?></span>
                                <small class="text-muted ms-1">ريال</small>
                            </td>
                        </tr>

                        <!-- 12. DRAWER RESULT -->
                        <tr class="bg-dark text-white border-top border-4 border-white">
                            <td class="ps-4 py-4 fw-bold fs-4 text-warning"><i class="fas fa-cash-register me-3"></i> 14. الرصيد المتبقي في الدرج (الإجمالي العام)</td>
                            <td class="pe-4 py-4 text-end fw-bold fs-2 text-warning"><?= number_format($cashInHandResult) ?> <small class="fs-6 opacity-75">ريال</small></td>
                        </tr>

                        <!-- 13. ELECTRONIC BALANCE RESULT -->
                        <tr class="bg-info text-white border-top border-2 border-white">
                            <td class="ps-4 py-4 fw-bold fs-4"><i class="fas fa-wallet me-3"></i> 15. إجمالي الرصيد الإلكتروني (الحوالات)</td>
                            <td class="pe-4 py-4 text-end fw-bold fs-2"><?= number_format($cashSummary['remaining_transfer'] ?? 0) ?> <small class="fs-6 opacity-75">ريال</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer bg-light p-4 text-center">
            <p class="text-muted small mb-0 fw-bold">
                <i class="fas fa-info-circle text-primary me-2"></i> 
                تم احتساب المديونية بناءً على الأرصدة الحالية، والربح بناءً على المبيعات والتكلفة والتالف.
            </p>
        </div>
    </div>
</div>