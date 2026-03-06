<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-calendar me-2"></i> Yearly Detailed Report</h4>
            <small class="text-white-50">Financial Year: <?= $year ?></small>
        </div>
        <button class="btn btn-warning btn-sm fw-bold no-print" onclick="window.print()"><i class="fas fa-print me-1"></i> Print / PDF</button>
    </div>

    <div class="card-body bg-light">

        <!-- 1. YEARLY OVERVIEW CARDS -->
        <h5 class="text-dark border-bottom pb-2 mb-4 fw-bold">1. Yearly Summary (ملخص السنة)</h5>

        <div class="row g-3 text-center mb-5">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-bottom border-4 border-success">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Annual Sales</h6>
                        <h3 class="fw-bold text-success display-6"><?= number_format($totalSales) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-bottom border-4 border-primary">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Purchases</h6>
                        <h3 class="fw-bold text-primary display-6"><?= number_format($totalPurchases) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-bottom border-4 border-danger">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Expenses</h6>
                        <h3 class="fw-bold text-danger display-6"><?= number_format($totalExpenses) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-bottom border-4 border-goldenrod"> <!-- Custom color class or style needed, defaulting to dark logic -->
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Net Annual Profit</h6>
                        <h3 class="fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?> display-6"><?= number_format($netProfit) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MONTH-BY-MONTH MATRIX -->
        <h5 class="text-dark border-bottom pb-2 mb-3 fw-bold"><i class="fas fa-layer-group me-2"></i> 2. Monthly Performance Matrix (جدول الأداء الشهري)</h5>

        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table table-hover table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Month (الشهر)</th>
                        <th>Sales (المبيعات)</th>
                        <th>Purchases (المشتريات)</th>
                        <th>Expenses (المصاريف)</th>
                        <th>Net Profit (الربح)</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $months = [1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December"];
                    foreach ($matrixData as $m => $data): ?>
                        <tr>
                            <td class="fw-bold text-start ps-4"><?= $months[$m] ?></td>
                            <td><?= number_format($data['sales']) ?></td>
                            <td><?= number_format($data['purchases']) ?></td>
                            <td><?= number_format($data['expenses']) ?></td>
                            <td class="fw-bold <?= $data['net'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($data['net']) ?>
                            </td>
                            <td>
                                <!-- Simple Visual Bar -->
                                <?php
                                $max = $totalSales > 0 ? $totalSales : 1;
                                $percent = ($data['sales'] / $max) * 100 * 5; // Scaling factor for visual
                                $percent = min($percent, 100);
                                ?>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percent ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td>ANNUAL TOTAL</td>
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