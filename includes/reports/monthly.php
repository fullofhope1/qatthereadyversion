<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i> Monthly Detailed Report</h4>
            <small class="text-white-50"><?= date('F Y', strtotime($month)) ?></small>
        </div>
        <button class="btn btn-warning btn-sm fw-bold no-print" onclick="window.print()"><i class="fas fa-print me-1"></i> Print / PDF</button>
    </div>

    <div class="card-body bg-light">

        <!-- 1. MONTHLY OVERVIEW CARDS -->
        <h5 class="text-dark border-bottom pb-2 mb-4 fw-bold">1. Monthly Overview (نظرة عامة شهرية)</h5>

        <div class="row g-3 text-center mb-5">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Sales</h6>
                        <h3 class="fw-bold text-success"><?= number_format($totalSales) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Purchases</h6>
                        <h3 class="fw-bold text-primary"><?= number_format($totalPurchases) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Expenses</h6>
                        <h3 class="fw-bold text-danger"><?= number_format($totalExpenses) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-dark">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Net Profit</h6>
                        <h3 class="fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($netProfit) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info shadow-sm text-center mb-5">
            <?php
            $netCash = $remainingCash ?? 0;
            $utSum = (float)($salesSummary['total_unknown_transfers'] ?? 0);
            ?>
            <h4 class="mb-0"><i class="fas fa-coins me-2"></i> Projected Cash in Drawer (النقد المتوقع): <b><?= number_format($netCash) ?></b> YER</h4>
            <div class="small mt-2">
                Cash Sales (<?= number_format($cashSales) ?>)
                + Debt Payments (<?= number_format($collectedPayments) ?>)
                <?php if($utSum > 0): ?>+ Unknown Transfers (<?= number_format($utSum) ?>)<?php endif; ?>
                - Expenses (<?= number_format($totalExpenses) ?>)
                - Refunds (<?= number_format($cashRefunds) ?>)
                - Deposits (<?= number_format($depositsYER) ?>)
            </div>
        </div>

        <!-- 2. DAY-BY-DAY MATRIX -->
        <h5 class="text-dark border-bottom pb-2 mb-3 fw-bold"><i class="fas fa-table me-2"></i> 2. Daily Performance Matrix (جدول الأداء اليومي)</h5>

        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table table-hover table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date (التاريخ)</th>
                        <th>Sales (المبيعات)</th>
                        <th>Purchases (المشتريات)</th>
                        <th>Expenses (المصاريف)</th>
                        <th>Net Profit (الربح)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrixData as $day => $data): ?>
                        <?php
                        // Skip days with no activity? Or show all?
                        // User wants detailed, so showing all serves as a record of "no business".
                        // But usually better to highlight active days.
                        $hasActivity = $data['sales'] > 0 || $data['purchases'] > 0 || $data['expenses'] > 0;
                        if (!$hasActivity) continue;
                        ?>
                        <tr>
                            <td class="fw-bold"><?= date('d/m/Y', strtotime($day)) ?></td>
                            <td><?= number_format($data['sales']) ?></td>
                            <td><?= number_format($data['purchases']) ?></td>
                            <td><?= number_format($data['expenses']) ?></td>
                            <td class="fw-bold <?= $data['net'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($data['net']) ?>
                            </td>
                            <td>
                                <?php if ($data['net'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="fas fa-arrow-up"></i> Profit</span>
                                <?php elseif ($data['net'] < 0): ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="fas fa-arrow-down"></i> Loss</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td>TOTALS</td>
                        <td><?= number_format($totalSales) ?></td>
                        <td><?= number_format($totalPurchases) ?></td>
                        <td><?= number_format($totalExpenses) ?></td>
                        <td class="<?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($netProfit) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>