<style>
    .report-table-card { border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important; }
    .report-table thead { background: #f1f4f8; }
    .report-table th { font-weight: 700; font-size: 0.75rem; padding: 1.25rem 1rem; color: #495057; }
</style>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fas <?= $subIcon ?> me-2 <?= $subColor ?>"></i>
            <?= $subTitle ?>
        </h5>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-muted fw-normal"><?= count($subList) ?> صنف</span>
            <button onclick="window.print()" class="btn btn-sm btn-dark rounded-pill no-print">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th class="ps-4">تاريخ المصدر</th>
                        <th>الرعوي (المورد)</th>
                        <th>النوع</th>
                        <th class="text-center">الكمية</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-end pe-4">قرار الترحيل/الإتلاف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subList)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">لا توجد سجلات في هذا القسم.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subList as $l): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= $l['source_date'] ?></div>
                                    <div class="small text-muted"><?= getArabicDay($l['source_date']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($l['prov_name'] ?? '---') ?></td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($l['type_name']) ?></span>
                                </td>
                                <td class="text-center fw-bold">
                                    <?= ($l['unit_type'] === 'weight') ? number_format($l['weight_kg'], 3).' كجم' : $l['quantity_units'].' '.$l['unit_type'] ?>
                                </td>
                                <td class="text-center text-muted small">
                                    <?= htmlspecialchars($l['status']) ?>
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    <?= $l['decision_date'] ? 'تم في: '.$l['decision_date'] : '---' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
