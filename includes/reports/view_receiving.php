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
            <i class="fas fa-truck me-2 text-success"></i>
            سجل التوريد والاستلام التفصيلي
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-muted fw-normal me-2"><?= count($listPurch) ?> شحنة</span>
            <button onclick="window.print()" class="btn btn-sm btn-dark rounded-pill no-print">
                <i class="fas fa-print me-1"></i> طباعة التقرير
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Search box -->
        <div class="p-3 pb-0 no-print">
            <input type="text" id="receivingReportSearch" class="form-control" placeholder="بحث باسم المورد أو النوع..." oninput="filterReceivingReport()">
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th class="ps-4">التاريخ والوقت</th>
                        <th>الرعوي (المورد)</th>
                        <th>النوع</th>
                        <th class="text-center">الكمية المرسلة</th>
                        <th class="text-center">الكمية المستلمة</th>
                        <th class="text-center">الفارق (النقص)</th>
                        <th class="text-end pe-4">التكلفة الصافية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listPurch)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fs-1 d-block mb-3 opacity-25"></i>
                                لا توجد سجلات استلام في هذه الفترة.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listPurch as $p): 
                            $isWeight = ($p['unit_type'] === 'weight');
                            $diff = 0;
                            $unitLabel = "";
                            
                            if ($isWeight) {
                                $sentValue = $p['source_weight_grams'];
                                $recvValue = $p['received_weight_grams'];
                                $diff = $sentValue - $recvValue;
                                $sentFormatted = number_format($sentValue/1000, 2) . " كجم";
                                $recvFormatted = number_format($recvValue/1000, 2) . " كجم";
                                $diffFormatted = ($diff > 0) ? number_format($diff, 0) . " جم" : "-";
                            } else {
                                $sentValue = $p['source_units'];
                                $recvValue = $p['received_units'];
                                $diff = $sentValue - $recvValue;
                                $unitLabel = " " . ($p['unit_type'] ?: 'حبة');
                                $sentFormatted = number_format($sentValue) . $unitLabel;
                                $recvFormatted = number_format($recvValue) . $unitLabel;
                                $diffFormatted = ($diff > 0) ? number_format($diff) . $unitLabel : "-";
                            }
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= getArabicDay($p['purchase_date']) ?></div>
                                    <div class="small text-muted"><?= date('H:i', strtotime($p['purchase_date'])) ?> | <?= date('Y-m-d', strtotime($p['purchase_date'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['prov_name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= htmlspecialchars($p['type_name']) ?></span>
                                </td>
                                <td class="text-center text-muted">
                                    <?= $sentFormatted ?>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    <?= $recvFormatted ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($diff > 0): ?>
                                        <span class="text-danger fw-bold"><i class="fas fa-caret-down me-1"></i><?= $diffFormatted ?></span>
                                    <?php else: ?>
                                        <span class="text-success small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($p['discount_amount'] > 0): ?>
                                        <div class="small text-muted text-decoration-line-through"><?= number_format($p['net_cost']) ?></div>
                                        <div class="text-danger small">-<?= number_format($p['discount_amount']) ?></div>
                                        <div class="fw-bold text-success h6 mb-0"><?= number_format($p['final_cost']) ?></div>
                                    <?php else: ?>
                                        <div class="fw-bold text-success"><?= number_format($p['net_cost']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($listPurch)): ?>
                    <tfoot class="bg-light fw-bold" style="border-top: 2px solid #dee2e6;">
                        <tr>
                            <td colspan="6" class="text-end py-3">إجمالي تكلفة التوريد:</td>
                            <td class="text-end py-3 text-success h5 fw-bold pe-4"><?= number_format($totalPurchases) ?> ريال</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    function filterReceivingReport() {
        const term = document.getElementById('receivingReportSearch').value.toLowerCase();
        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            if (row.cells.length < 7) return; 
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>