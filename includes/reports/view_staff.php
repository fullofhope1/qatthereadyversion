<style>
    .report-table-card {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
    }
    .report-table thead { background: #f1f4f8; }
    .report-table th {
        font-weight: 700;
        font-size: 0.75rem;
        padding: 1.25rem 1rem;
        color: #495057;
    }
</style>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fas fa-user-tie me-2 text-info"></i>
            تقرير نشاط ومسحوبات الموظفين
        </h5>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-muted fw-normal"><?= count($listStaff) ?> موظف</span>
            <button onclick="window.print()" class="btn btn-sm btn-dark rounded-pill no-print">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="p-3 pb-0 no-print">
            <input type="text" id="staffReportSearch" class="form-control" placeholder="بحث باسم الموظف أو الصفة..." oninput="filterStaffReport()">
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table" id="staffTable">
                <thead>
                    <tr>
                        <th class="ps-4">الموظف</th>
                        <th>الصفة / الدور</th>
                        <th class="text-center">إجمالي المسحوبات (ريال)</th>
                        <th class="text-center">نفقات أخرى</th>
                        <th class="text-end pe-4">الإجمالي الكلي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotalDraws = 0;
                    if (empty($listStaff)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">لا توجد بيانات لهذه الفترة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listStaff as $s): 
                            $grandTotalDraws += $s['total_draws'];
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="small text-muted">ID: <?= $s['id'] ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['role']) ?></span>
                                </td>
                                <td class="text-center fw-bold text-danger">
                                    <?= number_format($s['total_draws']) ?>
                                </td>
                                <td class="text-center text-muted">
                                    <?= number_format($s['other_expenses']) ?>
                                </td>
                                <td class="text-end pe-4 fw-bold">
                                    <?= number_format($s['total_draws'] + $s['other_expenses']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($listStaff)): ?>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end py-3">إجمالي المسحوبات:</td>
                            <td class="text-center py-3 text-danger"><?= number_format($grandTotalDraws) ?> ريال</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    function filterStaffReport() {
        const term = document.getElementById('staffReportSearch').value.toLowerCase();
        document.querySelectorAll('#staffTable tbody tr').forEach(row => {
            if (row.cells.length < 5) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>