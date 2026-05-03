<?php
// includes/reports/view_deposits.php
?>
<div class="card shadow-sm border-0" style="border-radius: 15px;">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-university me-2"></i> سجل الإيداعات والتوريدات المالية</h5>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">عدد العمليات: <?= count($listDeposits) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">التاريخ</th>
                        <th>المستلم / الجهة</th>
                        <th>العملة</th>
                        <th class="text-end">المبلغ</th>
                        <th class="text-end pe-4">ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalYER = 0;
                    $totalSAR = 0;
                    $totalUSD = 0;
                    foreach ($listDeposits as $d): 
                        if ($d['currency'] == 'YER') $totalYER += $d['amount'];
                        if ($d['currency'] == 'SAR') $totalSAR += $d['amount'];
                        if ($d['currency'] == 'USD') $totalUSD += $d['amount'];
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?= date('Y/m/d', strtotime($d['deposit_date'])) ?></div>
                                <div class="text-muted small"><?= date('h:i A', strtotime($d['created_at'] ?? 'now')) ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="fas fa-user-tie small"></i>
                                    </div>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($d['recipient']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= $d['currency'] ?></span>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                <?= number_format($d['amount'], ($d['currency'] == 'YER' ? 0 : 2)) ?>
                            </td>
                            <td class="text-end pe-4 text-muted small">
                                <?= htmlspecialchars($d['notes'] ?: '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($listDeposits)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-search mb-2 fs-3"></i><br>
                                لا توجد إيداعات مسجلة لهذه الفترة
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($listDeposits)): ?>
                <tfoot class="table-light fw-bold border-top-2">
                    <tr>
                        <td colspan="3" class="ps-4 text-end">إجمالي التوريدات (YEM):</td>
                        <td class="text-end text-primary"><?= number_format($totalYER) ?></td>
                        <td></td>
                    </tr>
                    <?php if ($totalSAR > 0): ?>
                    <tr>
                        <td colspan="3" class="ps-4 text-end">إجمالي التوريدات (SAR):</td>
                        <td class="text-end text-primary"><?= number_format($totalSAR, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($totalUSD > 0): ?>
                    <tr>
                        <td colspan="3" class="ps-4 text-end">إجمالي التوريدات (USD):</td>
                        <td class="text-end text-primary"><?= number_format($totalUSD, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
