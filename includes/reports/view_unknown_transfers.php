<?php
// includes/reports/view_unknown_transfers.php
$listUT = $service->getDetailedViewData('UnknownTransfers', $reportType, $date, $month, $year, null, $current_user_id);
?>

<div class="card border-0 shadow-sm rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-dark text-white p-4 rounded-top-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold"><i class="fas fa-question-circle me-2"></i>الحوالات المجهولة</h4>
                <small class="opacity-75">المبالغ المستلمة عبر التطبيقات البنكية دون تحديد صاحبها</small>
            </div>
            <div class="text-end d-flex gap-2 align-items-center">
                <span class="badge bg-warning text-dark fs-5 rounded-pill px-4">
                    إجمالي: <?= number_format(array_sum(array_column($listUT, 'amount'))) ?> YER
                </span>
                <button onclick="window.print()" class="btn btn-sm btn-light rounded-pill no-print">
                    <i class="fas fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">التاريخ</th>
                        <th>المبلغ</th>
                        <th>البنك / القناة</th>
                        <th>البيان / الوصف</th>
                        <th>الحالة</th>
                        <th class="pe-4 text-center">بواسطة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listUT)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted opacity-25 mb-3"></i>
                                <p class="text-muted">لا يوجد حوالات مجهولة مسجلة في هذه الفترة</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listUT as $ut): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= date('Y-m-d', strtotime($ut['transfer_date'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($ut['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold fs-5 text-primary"><?= number_format($ut['amount']) ?></span>
                                    <small class="text-muted">ريال</small>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info rounded-pill px-3">
                                        <?= htmlspecialchars($ut['source_bank'] ?? 'غير محدد') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-wrap" style="max-width: 300px;">
                                        <?= htmlspecialchars($ut['description']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($ut['status'] === 'Resolved'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                            <i class="fas fa-check-circle me-1"></i> تم التعرف
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3">
                                            <i class="fas fa-clock me-1"></i> مجهولة
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-center">
                                    <span class="text-muted small">
                                        <i class="fas fa-user-edit me-1"></i>
                                        <?= htmlspecialchars($ut['created_by_name'] ?? 'النظام') ?>
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
