<style>
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

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fas fa-wallet me-2 text-danger"></i>
            سجل المصاريف التشغيلية
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-muted fw-normal me-2"><?= count($listExp) ?> قيد</span>
            <button onclick="window.print()" class="btn btn-sm btn-dark rounded-pill no-print">
                <i class="fas fa-print me-1"></i> طباعة التقرير
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Search box (#27) -->
        <div class="p-3 pb-0">
            <input type="text" id="expenseReportSearch" class="form-control" placeholder="بحث بالتصنيف أو البيان أو الموظف..." oninput="filterExpenseReport()">
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>التصنيف</th>
                        <th>البيان (التفاصيل)</th>
                        <th>الموظف المسؤول</th>
                        <th class="text-end">المبلغ (ريال)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listExp)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-receipt fs-1 d-block mb-3 opacity-25"></i>
                                لا توجد مصاريف في هذه الفترة.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listExp as $e): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= getArabicDay($e['expense_date']) ?></div>
                                    <div class="small text-muted"><?= date('M d', strtotime($e['expense_date'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <?= htmlspecialchars($e['category']) ?>
                                    </span>
                                </td>
                                <td class="text-dark">
                                    <?= htmlspecialchars($e['description']) ?>
                                </td>
                                <td>
                                    <span class="text-muted"><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($e['staff_name'] ?? 'غير محدد') ?></span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    <?= number_format($e['amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($listExp)): ?>
                    <tfoot class="bg-light fw-bold" style="border-top: 2px solid #dee2e6;">
                        <tr>
                            <td colspan="4" class="text-end py-3">إجمالي المصاريف لهذه الفترة:</td>
                            <td class="text-end py-3 text-danger h5 fw-bold"><?= number_format($totalExpenses) ?> ريال</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    function filterExpenseReport() {
        const term = document.getElementById('expenseReportSearch').value.toLowerCase();
        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            if (row.cells.length < 5) return; // Skip empty row
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>