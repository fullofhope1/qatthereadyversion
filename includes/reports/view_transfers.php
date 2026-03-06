<?php
// reports/view_transfers.php

if ($reportType == 'Daily') {
    $where = "WHERE transfer_date = '$date'";
    $periodTitle = "Date: $date";
} elseif ($reportType == 'Monthly') {
    $where = "WHERE MONTH(transfer_date) = '$month' AND YEAR(transfer_date) = '$year'";
    $periodTitle = "Month: $month/$year";
} else {
    $where = "WHERE YEAR(transfer_date) = '$year'";
    $periodTitle = "Year: $year";
}

$transfers = $pdo->query("SELECT * FROM unknown_transfers $where ORDER BY created_at DESC")->fetchAll();
$totalTransfers = 0;
foreach ($transfers as $t) $totalTransfers += $t['amount'];
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-secondary shadow-sm">
            <h4 class="alert-heading"><i class="fas fa-money-check-alt me-2"></i> تحويلات مجهولة</h4>
            <p class="mb-0">
                الفترة: <strong><?= $periodTitle ?></strong> |
                العدد: <strong><?= count($transfers) ?></strong> |
                إجمالي المبلغ: <strong><?= number_format($totalTransfers) ?> ريال</strong>
            </p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <!-- Search box (#27) -->
        <input type="text" id="transferReportSearch" class="form-control mb-3" placeholder="بحث باسم الموظف أو رقم السند..." oninput="filterTransferReport()">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>التاريخ</th>
                        <th>رقم السند/الحوالة</th>
                        <th>اسم المرسل</th>
                        <th>المبلغ</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transfers) > 0): ?>
                        <?php foreach ($transfers as $t): ?>
                            <tr>
                                <td><?= $t['transfer_date'] ?></td>
                                <td class="font-monospace"><?= htmlspecialchars($t['receipt_number']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($t['sender_name']) ?></td>
                                <td class="text-success fw-bold"><?= number_format($t['amount']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($t['notes'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">لا توجد تحويلات في هذه الفترة.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function filterTransferReport() {
        const term = document.getElementById('transferReportSearch').value.toLowerCase();
        document.querySelectorAll('.table-hover tbody tr').forEach(row => {
            if (row.cells.length < 5) return;
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>