<?php
// includes/reports/view_waste.php
$totalWasteWeight = array_sum(array_column($listWaste, 'weight_kg'));
?>

<div class="card report-table-card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-danger">
            <i class="fas fa-trash-alt me-2"></i> تقرير التوالف (البقايا المنتهية)
        </h5>
        <div>
            <span class="badge bg-danger fs-6 fw-normal me-2">
                إجمالي الوزن التالف: <?= number_format($totalWasteWeight, 3) ?> كجم
            </span>
            <span class="badge bg-light text-muted fw-normal"><?= count($listWaste) ?> عملية</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead class="table-light">
                    <tr>
                        <th>رقم العملية</th>
                        <th>تاريخ الإتلاف (اليومية)</th>
                        <th>المورد (الرعوي)</th>
                        <th>النوع</th>
                        <th class="text-end">الوزن التالف (كجم)</th>
                        <th>حالة الإتلاف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listWaste as $w): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $w['id'] ?></span></td>
                            <td><?= date('Y-m-d', strtotime($w['sale_date'])) ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="fas fa-user-tag fs-6"></i>
                                    </div>
                                    <div class="fw-bold"><?= htmlspecialchars($w['prov_name'] ?? 'غير معروف') ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark border border-info">
                                    <?= htmlspecialchars($w['type_name'] ?? 'غير محدد') ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold text-danger fs-5">
                                <?= number_format($w['weight_kg'], 3) ?>
                            </td>
                            <td>
                                <?php if ($w['status'] == 'Auto_Dropped'): ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis"><i class="fas fa-robot me-1"></i> إتلاف آلي</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis"><i class="fas fa-user-times me-1"></i> إتلاف يدوي</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($listWaste)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="mb-3 opacity-50">
                                    <i class="fas fa-check-circle fs-1 text-success"></i>
                                </div>
                                <p class="mb-0 fs-5">لا توجد توالف مسجلة في هذه الفترة.</p>
                                <small>جميع البضائع تم بيعها أو جردها بنجاح.</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>