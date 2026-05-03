<?php
require 'config/db.php';
include 'includes/header.php';

$reportRepo = new ReportRepository($pdo);
$providers = $reportRepo->getProviderFinancialSummary();

$detailsId = $_GET['id'] ?? null;
$statement = null;
$selectedProvider = null;

if ($detailsId) {
    $statement = $reportRepo->getProviderStatement($detailsId);
    $providerRepo = new ProviderRepository($pdo);
    $selectedProvider = $providerRepo->getById($detailsId);
}
?>

<div class="row">
    <!-- Providers Summary List -->
    <div class="<?= $detailsId ? 'col-md-4' : 'col-md-12' ?>">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">كشوفات الموردين</h5>
                <a href="providers.php" class="btn btn-outline-light btn-sm">إدارة الموردين</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المورد</th>
                                <th>المشتريات</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $p): ?>
                                <tr onclick="window.location='provider_statements.php?id=<?= $p['id'] ?>'" 
                                    style="cursor: pointer;" 
                                    class="<?= $detailsId == $p['id'] ? 'table-primary' : '' ?>">
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['phone']) ?></small>
                                    </td>
                                    <td><?= number_format($p['total_purchases']) ?></td>
                                    <td class="text-success"><?= number_format($p['total_paid']) ?></td>
                                    <td class="<?= $p['balance'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <?= number_format($p['balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider Detailed Statement -->
    <?php if ($detailsId && $selectedProvider): ?>
    <div class="col-md-8 animate__animated animate__fadeIn">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">كشف حساب: <?= htmlspecialchars($selectedProvider['name']) ?></h5>
                <button class="btn btn-light btn-sm" onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light text-center">
                            <tr>
                                <th>التاريخ</th>
                                <th>الوصف</th>
                                <th>مشتريات (+)</th>
                                <th>مدفوعات (-)</th>
                                <th>الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $runningBalance = 0;
                            foreach ($statement as $row): 
                                $runningBalance += ($row['amount'] - $row['paid']);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $row['op_date'] ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="text-end"><?= $row['amount'] > 0 ? number_format($row['amount']) : '-' ?></td>
                                    <td class="text-end text-success"><?= $row['paid'] > 0 ? number_format($row['paid']) : '-' ?></td>
                                    <td class="text-end fw-bold <?= $runningBalance > 0 ? 'text-danger' : '' ?>">
                                        <?= number_format($runningBalance) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td colspan="2" class="text-center fw-bold">الإجمالي النهائي</td>
                                <td class="text-end"><?= number_format(array_sum(array_column($statement, 'amount'))) ?></td>
                                <td class="text-end"><?= number_format(array_sum(array_column($statement, 'paid'))) ?></td>
                                <td class="text-end"><?= number_format($runningBalance) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php elseif (!$detailsId): ?>
    <div class="col-md-12 mt-4 text-center text-muted">
        <div class="p-5 border rounded bg-light">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <p>يرجى اختيار مورد من القائمة لعرض كشف الحساب التفصيلي.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
