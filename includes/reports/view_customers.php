<style>
    .report-table-card { border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important; }
    .report-table thead { background: #f1f4f8; }
    .report-table th { font-weight: 700; font-size: 0.75rem; padding: 1.25rem 1rem; color: #495057; }
</style>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fas fa-users me-2 text-primary"></i>
            نشاط العملاء والمبيعات
        </h5>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-muted fw-normal"><?= count($listCust) ?> عميل</span>
            <button onclick="window.print()" class="btn btn-sm btn-dark rounded-pill no-print">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="p-3 pb-0 no-print">
            <input type="text" id="custReportSearch" class="form-control" placeholder="بحث باسم العميل أو الهاتف..." oninput="filterCustReport()">
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table" id="custTable">
                <thead>
                    <tr>
                        <th class="ps-4">العميل</th>
                        <th class="text-center">عدد الفواتير</th>
                        <th class="text-end">إجمالي قيمة المشتريات</th>
                        <th class="text-end pe-4">الديون المتبقية (في هذه الفترة)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotalCustSales = 0;
                    $grandTotalCustDebt = 0;
                    if (empty($listCust)): ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">لا توجد بيانات لهذه الفترة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listCust as $c): 
                            $grandTotalCustSales += $c['total_sale_amount'];
                            $grandTotalCustDebt += $c['remaining_debt'];
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($c['phone']) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $c['total_sales_count'] ?></span>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    <?= number_format($c['total_sale_amount']) ?>
                                </td>
                                <td class="text-end pe-4 fw-bold text-danger">
                                    <?= number_format($c['remaining_debt']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($listCust)): ?>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end py-3">الإجمالي:</td>
                            <td class="text-end py-3 text-primary"><?= number_format($grandTotalCustSales) ?></td>
                            <td class="text-end pe-4 py-3 text-danger"><?= number_format($grandTotalCustDebt) ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    function filterCustReport() {
        const term = document.getElementById('custReportSearch').value.toLowerCase();
        document.querySelectorAll('#custTable tbody tr').forEach(row => {
            if (row.cells.length < 4) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>