<?php
// includes/reports/view_shipments.php
// $listShipments is passed from reports.php
?>

<div class="card border-0 shadow-sm" style="border-radius: 20px;">
    <div class="card-header bg-white border-0 py-4 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-truck-loading me-2 text-warning"></i> أداء الشحنات والمشتريات</h4>
                <p class="text-muted mb-0 small">تتبع أرباح وخسائر كل شحنة بشكل مستقل من لحظة الشراء حتى اكتمال البيع</p>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="direction: rtl;">
                <thead class="bg-light">
                    <tr class="text-muted small">
                        <th class="ps-4 py-3">التاريخ / المورد</th>
                        <th>الوزن (المتوقع / المستلم)</th>
                        <th>العجز</th>
                        <th>التكلفة</th>
                        <th>مبيعات (فرش / أول / ثاني)</th>
                        <th>إجمالي المبيعات</th>
                        <th>التوالف / المتبقي</th>
                        <th class="pe-4 text-end">صافي الربح</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listShipments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">لا توجد بيانات شحنات لهذه الفترة</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listShipments as $ship): 
                            $profitClass = $ship['net_profit'] >= 0 ? 'text-success' : 'text-danger';
                            $profitIcon = $ship['net_profit'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                            $shortageClass = $ship['shortage_kg'] > 0 ? 'text-danger fw-bold' : 'text-muted';
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($ship['provider_name']) ?></div>
                                    <div class="small text-muted"><?= $ship['purchase_date'] ?></div>
                                    <div class="badge bg-light text-dark border extra-small"><?= htmlspecialchars($ship['qat_type']) ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted">متوقع: <?= number_format($ship['expected_quantity_kg'], 2) ?></div>
                                    <div class="fw-bold">مستلم: <?= number_format($ship['received_kg'], 2) ?></div>
                                </td>
                                <td class="<?= $shortageClass ?>">
                                    <?= number_format($ship['shortage_kg'], 2) ?> كجم
                                </td>
                                <td class="fw-bold text-muted"><?= number_format($ship['total_cost']) ?></td>
                                <td>
                                    <div class="small"><span class="text-success">فرش:</span> <?= number_format($ship['fresh_kg'], 2) ?></div>
                                    <div class="small"><span class="text-primary">أول:</span> <?= number_format($ship['momsi1_kg'], 2) ?></div>
                                    <div class="small"><span class="text-warning">ثاني:</span> <?= number_format($ship['momsi2_kg'], 2) ?></div>
                                </td>
                                <td class="fw-bold text-dark fs-6"><?= number_format($ship['total_revenue']) ?></td>
                                <td>
                                    <div class="text-danger small"><i class="fas fa-trash-alt"></i> توالف: <?= number_format($ship['waste_kg'], 2) ?></div>
                                    <div class="text-warning small"><i class="fas fa-box"></i> متبقي: <?= number_format($ship['remaining_kg'], 2) ?></div>
                                </td>
                                <td class="pe-4 text-end fw-bold <?= $profitClass ?> fs-5">
                                    <i class="fas <?= $profitIcon ?> me-1 small"></i>
                                    <?= number_format($ship['net_profit']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
