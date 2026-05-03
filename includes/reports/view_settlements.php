<?php
// includes/reports/view_settlements.php
$listPayments = $reportRepo->getDetailedPayments($reportType, $date, $month, $year);
?>

<div class="card border-0 shadow-sm rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-primary text-white p-4 rounded-top-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2"></i> تقرير تحصيلات الديون</h4>
                <small class="opacity-75">سجل المبالغ المحصلة وسدادات حسابات العملاء</small>
            </div>
            <div class="text-end d-flex gap-2 align-items-center">
                <div class="position-relative me-2 no-print">
                    <input type="text" id="settlementSearch" class="form-control form-control-sm rounded-pill ps-4 border-0 shadow-sm" placeholder="ابحث عن عميل أو حوالة..." style="width: 250px;">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size: 0.8rem;"></i>
                </div>
                <span class="badge bg-white text-primary fs-5 rounded-pill px-4 shadow-sm">
                    إجمالي التحصيل: <?= number_format(array_sum(array_column($listPayments, 'amount'))) ?> YER
                </span>
                <button onclick="window.print()" class="btn btn-light rounded-pill no-print shadow-sm">
                    <i class="fas fa-print me-1"></i> طباعة التقرير
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-dark">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">التاريخ والوقت</th>
                        <th>اسم العميل</th>
                        <th>المبلغ المسدد</th>
                        <th>طريقة السداد</th>
                        <th>تفاصيل الحوالة (المرسل / الرقم / الشركة)</th>
                        <th class="pe-4">ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listPayments)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="opacity-25 mb-3">
                                    <i class="fas fa-file-invoice-dollar fa-4x"></i>
                                </div>
                                <h5 class="text-muted">لا توجد عمليات تحصيل مسجلة لهذه الفترة</h5>
                                <p class="text-muted small">تأكد من اختيار الفترة الزمنية الصحيحة</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listPayments as $pay): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= date('Y-m-d', strtotime($pay['payment_date'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($pay['payment_date'])) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fas fa-user small"></i>
                                        </div>
                                        <span class="fw-bold"><?= htmlspecialchars($pay['customer_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fs-5 fw-bold text-success">
                                        <?= number_format($pay['amount']) ?>
                                        <small class="fs-6 fw-normal text-muted">ريال</small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($pay['payment_method'] === 'Transfer'): ?>
                                        <span class="badge bg-info-subtle text-info rounded-pill px-3">
                                            <i class="fas fa-exchange-alt me-1"></i> حوالة
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">
                                            <i class="fas fa-money-bill-alt me-1"></i> نقدي
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pay['payment_method'] === 'Transfer'): ?>
                                        <div class="p-2 bg-light rounded-3 border border-info border-opacity-10 small" style="min-width: 200px;">
                                            <div class="mb-1"><span class="text-muted">المرسل:</span> <span class="fw-bold text-dark"><?= htmlspecialchars($pay['transfer_sender'] ?: '-') ?></span></div>
                                            <div class="mb-1"><span class="text-muted">المستلم:</span> <span class="fw-bold text-dark"><?= htmlspecialchars($pay['transfer_receiver'] ?: '-') ?></span></div>
                                            <div class="mb-1"><span class="text-muted">الرقم:</span> <span class="fw-bold text-dark"><?= htmlspecialchars($pay['transfer_number'] ?: '-') ?></span></div>
                                            <div><span class="text-muted">الشركة:</span> <span class="fw-bold text-dark"><?= htmlspecialchars($pay['transfer_company'] ?: '-') ?></span></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50">---</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4">
                                    <div class="text-muted small" style="max-width: 200px;">
                                        <?= htmlspecialchars($pay['note'] ?: 'لا توجد ملاحظات') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('settlementSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
