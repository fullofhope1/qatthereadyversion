<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-file-invoice me-2"></i> Daily Detailed Report</h4>
            <small class="text-white-50"><?= date('l, d F Y', strtotime($date)) ?></small>
        </div>
        <button class="btn btn-warning btn-sm fw-bold no-print" onclick="window.print()"><i class="fas fa-print me-1"></i> Print / PDF</button>
    </div>

    <div class="card-body bg-light">

        <!-- 1. EXECUTIVE FINANCIAL SUMMARY -->
        <h5 class="text-dark border-bottom pb-2 mb-4 fw-bold"><i class="fas fa-chart-pie me-2"></i> 1. Financial Performance (الأداء المالي)</h5>

        <div class="row g-3 text-center mb-5">
            <!-- REVENUE -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="icon-circle bg-success-subtle text-success mb-3 mx-auto" style="width:50px;height:50px;line-height:50px;border-radius:50%;"><i class="fas fa-hand-holding-dollar fs-4"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold">Total Revenue (المبيعات)</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= number_format($totalSales) ?></h3>
                        <small class="text-success"><?= count($listSales) ?> Transactions</small>
                    </div>
                </div>
            </div>

            <!-- COST OF GOODS (SOURCING) -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="icon-circle bg-primary-subtle text-primary mb-3 mx-auto" style="width:50px;height:50px;line-height:50px;border-radius:50%;"><i class="fas fa-truck-loading fs-4"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold">Sourcing Cost (تكلفة الشراء)</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= number_format($totalPurchases) ?></h3>
                        <small class="text-primary"><?= number_format(array_sum($invIn) ?? 0, 1) ?> KG Purchased</small>
                    </div>
                </div>
            </div>

            <!-- OPERATING EXPENSES -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="icon-circle bg-danger-subtle text-danger mb-3 mx-auto" style="width:50px;height:50px;line-height:50px;border-radius:50%;"><i class="fas fa-receipt fs-4"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold">Op. Expenses (المصاريف)</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= number_format($totalExpenses) ?></h3>
                        <small class="text-danger"><?= count($listExp) ?> Records</small>
                    </div>
                </div>
            </div>

            <!-- NET PROFIT -->
            <?php
            // Net Profit = (Sales - Refunds) - Costs - Expenses
            // Actually, Debt Refunds reduce Sales Revenue effectively? Or is it a separate expense?
            // Accounting wise: Sales Returns/Allowances reduce Net Sales.
            // Cash Refunds also reduce Net Sales (and Cash).
            // So True Revenue = Total Sales - (TotalCashRefunds + TotalDebtRefunds)
            $netRevenue = $totalSales - ($totalCashRefunds + $totalDebtRefunds);
            $netProfit = $netRevenue - $totalPurchases - $totalExpenses;
            $profitColor = $netProfit >= 0 ? 'success' : 'danger';
            ?>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-<?= $profitColor ?> text-white">
                    <div class="card-body">
                        <div class="icon-circle bg-white text-<?= $profitColor ?> mb-3 mx-auto" style="width:50px;height:50px;line-height:50px;border-radius:50%;"><i class="fas fa-chart-line fs-4"></i></div>
                        <h6 class="text-white-50 text-uppercase small fw-bold">Net Profit (صافي الربح)</h6>
                        <h3 class="fw-bold mb-0"><?= number_format($netProfit) ?></h3>
                        <small class="text-white-50">Adj. Rev - Costs - Exp</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1.5 CASH FLOW ADJUSTMENT -->
        <div class="alert alert-info shadow-sm text-center mb-5">
            <?php
            // Use centralized cash summary if available
            $netCash = $remainingCash ?? 0;
            $utSum = (float)($salesSummary['total_unknown_transfers'] ?? 0);
            ?>
            <h4 class="mb-0"><i class="fas fa-coins me-2"></i> Cash in Drawer (النقد المتوقع): <b><?= number_format($netCash) ?></b> YER</h4>
            <div class="small mt-2">
                Cash Sales (<?= number_format($cashSales) ?>)
                + Debt Payments (<?= number_format($collectedPayments) ?>)
                <?php if($utSum > 0): ?>+ Unknown Transfers (<?= number_format($utSum) ?>)<?php endif; ?>
                - Expenses (<?= number_format($totalExpenses) ?>)
                - Cash Refunds (<?= number_format($cashRefunds) ?>)
                <?php if(($depositsYER ?? 0) > 0): ?>- Deposits (<?= number_format($depositsYER) ?>)<?php endif; ?>
            </div>
        </div>

        <!-- 2. SOURCING BREAKDOWN -->
        <h5 class="text-dark border-bottom pb-2 mb-3 fw-bold"><i class="fas fa-truck me-2"></i> 2. Sourcing Breakdown (تفاصيل التوريد)</h5>
        <div class="table-responsive bg-white rounded shadow-sm p-3 mb-5">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Provider (المورد)</th>
                        <th>KG Supplied</th>
                        <th>Total Cost</th>
                        <th>Avg Price/KG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sourcingStats)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No sourcing records for today.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sourcingStats as $src): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($src['name']) ?></td>
                                <td><?= number_format($src['kg'], 2) ?> kg</td>
                                <td><?= number_format($src['cost']) ?> <small class="text-muted">YER</small></td>
                                <td>
                                    <?php
                                    $avg = $src['kg'] > 0 ? $src['cost'] / $src['kg'] : 0;
                                    echo number_format($avg);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 3. EXPENSES TABLE -->
        <?php if (!empty($listExp)): ?>
            <h5 class="text-dark border-bottom pb-2 mb-3 fw-bold"><i class="fas fa-wallet me-2"></i> 3. Detailed Expenses (تفاصيل المصاريف)</h5>
            <div class="table-responsive bg-white rounded shadow-sm p-3 mb-5">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>By Staff</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listExp as $e): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($e['category']) ?></span></td>
                                <td><?= htmlspecialchars($e['description']) ?></td>
                                <td><?= htmlspecialchars($e['staff_name'] ?? 'Admin') ?></td>
                                <td class="text-end fw-bold text-danger"><?= number_format($e['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- 4. SALES REGISTER -->
        <h5 class="text-dark border-bottom pb-2 mb-3 fw-bold"><i class="fas fa-shopping-cart me-2"></i> 4. Sales Register (سجل المبيعات)</h5>
        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table table-bordered table-sm table-hover text-center" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Type (النوع)</th>
                        <th>Weight</th>
                        <th>Price</th>
                        <th>Customer</th>
                        <th>Payment</th>
                        <th class="no-print">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listSales)): ?>
                        <tr>
                            <td colspan="7" class="py-4 text-muted">No sales recorded today.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listSales as $s): ?>
                            <tr>
                                <td><span class="text-muted">#<?= $s['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($s['type_name']) ?></td>
                                <td><?= $s['weight_grams'] < 1000 ? $s['weight_grams'] . ' g' : $s['weight_kg'] . ' kg' ?></td>
                                <td class="fw-bold"><?= number_format($s['price']) ?></td>
                                <td>
                                    <?= htmlspecialchars($s['cust_name'] ?? 'Fly Customer (طيار)') ?>
                                    <?php if ($s['cust_name']): ?><i class="fas fa-user-tag text-muted ms-1 small"></i><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['payment_method'] == 'Cash'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Cash</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning">Debt</span>
                                        <?php if ($s['is_paid']): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="no-print">
                                    <?php if ($s['payment_method'] == 'Debt' && !$s['is_paid']): ?>
                                        <button class="btn btn-sm btn-outline-success py-0" onclick="openPayModal(<?= $s['id'] ?>, '<?= $s['cust_name'] ?>', <?= $s['price'] ?>)">Pay</button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-link text-danger py-0" onclick="openRefundModal(<?= $s['id'] ?>, '<?= $s['cust_name'] ?>', <?= $s['price'] ?>)">Refund</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>