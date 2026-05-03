<?php
// includes/reports/view_unknown_transfers.php
$listUT = $service->getDetailedViewData('unknown_transfers', $reportType, $date, $month, $year, null, $report_user_id ?? null);
?>

<div class="card border-0 shadow-sm rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-dark text-white p-4 rounded-top-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold"><i class="fas fa-question-circle me-2"></i>الحوالات المجهولة</h4>
                <small class="opacity-75">المبالغ المستلمة عبر التطبيقات البنكية دون تحديد صاحبها</small>
            </div>
            <div class="text-end d-flex gap-2 align-items-center flex-wrap justify-content-end">
                <?php 
                $totalsByCurrency = [];
                foreach ($listUT as $item) {
                    $curr = $item['currency'] ?? 'YER';
                    $totalsByCurrency[$curr] = ($totalsByCurrency[$curr] ?? 0) + $item['amount'];
                }
                foreach ($totalsByCurrency as $curr => $sum): 
                    $badgeClass = ($curr === 'YER') ? 'bg-warning text-dark' : 'bg-success text-white';
                ?>
                    <span class="badge <?= $badgeClass ?> fs-6 rounded-pill px-3 shadow-sm">
                        إجمالي (<?= $curr ?>): <?= number_format($sum) ?>
                    </span>
                <?php endforeach; ?>
                <button onclick="window.print()" class="btn btn-sm btn-light rounded-pill no-print shadow-sm ms-2">
                    <i class="fas fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-light text-center">
                        <th class="ps-4">التاريخ</th>
                        <th>رقم السند</th>
                        <th>اسم المستلم</th>
                        <th>اسم المرسل</th>
                        <th>المبلغ</th>
                        <th>العملة</th>
                        <th>ملاحظات / بيان</th>
                        <th class="pe-4">بواسطة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listUT)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted opacity-25 mb-3"></i>
                                <p class="text-muted">لا يوجد حوالات مجهولة مسجلة في هذه الفترة</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listUT as $ut): ?>
                            <tr class="text-center">
                                <td class="ps-4 text-start">
                                    <div class="fw-bold"><?= date('Y-m-d', strtotime($ut['transfer_date'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($ut['created_at'])) ?></small>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($ut['receipt_number']) ?></td>
                                <td class="text-primary fw-bold"><?= htmlspecialchars($ut['receiver_name'] ?? '-') ?></td>
                                <td class="text-dark fw-bold"><?= htmlspecialchars($ut['sender_name'] ?? '-') ?></td>
                                <td>
                                    <span class="fw-bold fs-5 text-success"><?= number_format($ut['amount']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3"><?= $ut['currency'] ?? 'YER' ?></span>
                                </td>
                                <td>
                                    <div class="text-wrap small text-muted mx-auto" style="max-width: 200px;">
                                        <?= htmlspecialchars($ut['notes'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <span class="badge bg-light text-dark border fw-normal px-3 py-2 rounded-pill">
                                        <i class="fas fa-user-edit me-1"></i> النظام
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
