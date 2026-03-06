<style>
    .debt-stat-card {
        border-radius: 15px;
        transition: transform 0.3s;
        border: none !important;
    }

    .debt-stat-card:hover {
        transform: translateY(-5px);
    }

    .report-table-card {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
    }

    .report-table thead {
        background: #f1f4f8;
    }

    .report-table th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1.25rem 1rem;
        border: none;
        color: #495057;
    }

    .report-table td {
        padding: 1rem;
        border-color: #f1f4f8;
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card debt-stat-card bg-danger bg-gradient text-white shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-file-invoice-dollar fs-2 mb-3 opacity-50"></i>
                <h6 class="opacity-75 small fw-bold">إجمالي الديون القائمة</h6>
                <h2 class="fw-bold"><?= number_format($totalDebt) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card debt-stat-card bg-warning bg-gradient text-dark shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-clock fs-2 mb-3 opacity-50"></i>
                <h6 class="opacity-75 small fw-bold">الفواتير المتأخرة</h6>
                <h2 class="fw-bold"><?= number_format($overdueCount) ?></h2>
                <div class="small fw-bold opacity-50">فاتورة</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card debt-stat-card bg-info bg-gradient text-white shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-calendar-day fs-2 mb-3 opacity-50"></i>
                <h6 class="opacity-75 small fw-bold">مستحق التحصيل اليوم</h6>
                <h2 class="fw-bold"><?= number_format($todayDue) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card debt-stat-card bg-primary bg-gradient text-white shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-calendar-check fs-2 mb-3 opacity-50"></i>
                <h6 class="opacity-75 small fw-bold">مستحق التحصيل غداً</h6>
                <h2 class="fw-bold"><?= number_format($tomorrowDue) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
            أكبر 20 ديناً (حسب المبلغ)
        </h5>
        <span class="badge bg-danger-subtle text-danger fw-normal">تنبيه ديون</span>
    </div>
    <div class="card-body p-0">
        <!-- Search box (#28) -->
        <div class="p-3 pb-0">
            <input type="text" id="debtReportSearch" class="form-control" placeholder="بحث باسم العميل..." oninput="filterDebtReport()">
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th>تاريخ البيع</th>
                        <th>العميل والمشرف</th>
                        <th>نوع الدين</th>
                        <th class="text-end">مبلغ الدين (ريال)</th>
                        <th>تاريخ الاستحقاق</th>
                        <th class="text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $topDebts = $pdo->query("
                        SELECT s.*, c.name as client_name, c.id as client_id
                        FROM sales s 
                        JOIN customers c ON s.customer_id = c.id
                        WHERE s.is_paid = 0 
                        ORDER BY s.price DESC
                        LIMIT 20
                    ")->fetchAll();
                    $debtTotal = array_sum(array_column($topDebts, 'price'));

                    if (empty($topDebts)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">لا توجد ديون قائمة حالياً.</td>
                        </tr>
                        <?php else:
                        foreach ($topDebts as $d):
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= getArabicDay($d['sale_date']) ?></div>
                                    <div class="small text-muted"><?= date('M d, Y', strtotime($d['sale_date'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($d['client_name']) ?></div>
                                    <div class="text-muted small">ID: <?= $d['client_id'] ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= $d['debt_type'] ?: 'دين عام' ?></span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    <?= number_format($d['price'] - $d['paid_amount']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= date('M d, Y', strtotime($d['due_date'])) ?></div>
                                    <?php if ($d['due_date'] < date('Y-m-d')): ?>
                                        <div class="text-danger small fw-bold"><i class="fas fa-exclamation-circle me-1"></i> متأخر جداً</div>
                                    <?php elseif ($d['due_date'] == date('Y-m-d')): ?>
                                        <div class="text-warning small fw-bold"><i class="fas fa-clock me-1"></i> يستحق اليوم</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($d['due_date'] < date('Y-m-d')): ?>
                                        <span class="badge bg-danger">متأخر</span>
                                    <?php elseif ($d['due_date'] == date('Y-m-d')): ?>
                                        <span class="badge bg-warning text-dark">اليوم</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success">في المهلة</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Totals row (#12) -->
                    <?php if (!empty($topDebts)): ?>
                        <tr class="table-warning fw-bold">
                            <td colspan="3" class="text-end">إجمالي الديون:</td>
                            <td class="text-end text-danger fs-5"><?= number_format($debtTotal) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function filterDebtReport() {
        const term = document.getElementById('debtReportSearch').value.toLowerCase();
        document.querySelectorAll('.report-table tbody tr:not(.table-warning)').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>