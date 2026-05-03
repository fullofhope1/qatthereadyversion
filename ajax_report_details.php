<?php
require 'config/db.php';
require 'includes/classes/BaseRepository.php';
require 'includes/classes/ReportRepository.php';
require 'includes/classes/ReportService.php';
require 'includes/classes/ProviderRepository.php';

session_start();

$category = $_GET['category'] ?? '';
$reportType = $_GET['report_type'] ?? 'Daily';
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$year = $_GET['year'] ?? date('Y');
$userId = $_SESSION['user_id'] ?? null;

$reportRepo = new ReportRepository($pdo);
$service = new ReportService($reportRepo);

$data = [];
$title = '';

switch ($category) {
    case 'Sales':
        $data = $reportRepo->getSalesList($reportType, $date, $month, $year);
        $title = 'تفاصيل المبيعات';
        break;
    case 'Expenses':
        $data = $reportRepo->getExpensesList($reportType, $date, $month, $year, $userId);
        $title = 'تفاصيل المصاريف';
        break;
    case 'Payments':
        $data = $reportRepo->getDetailedPayments($reportType, $date, $month, $year);
        $title = 'تفاصيل التحصيلات';
        break;
    case 'Returns':
        $refunds = $reportRepo->getRefunds($reportType, $date, $month, $year);
        $data = array_filter($refunds, function($r) {
            return ($r['weight_kg'] > 0 || $r['quantity_units'] > 0);
        });
        $title = 'تفاصيل المرتجعات';
        break;
    case 'Compensations':
        $refunds = $reportRepo->getRefunds($reportType, $date, $month, $year);
        $data = array_filter($refunds, function($r) {
            return ($r['weight_kg'] == 0 && $r['quantity_units'] == 0);
        });
        $title = 'تفاصيل التعويضات';
        break;
}

if (empty($data)) {
    echo "<div class='p-5 text-center text-muted'>لا توجد بيانات متاحة لهذه الفترة</div>";
    exit;
}
?>

<div class="table-responsive">
    <table class="table table-hover table-striped mb-0">
        <thead class="table-dark">
            <tr>
                <?php if ($category === 'Sales'): ?>
                    <th>التاريخ</th>
                    <th>العميل</th>
                    <th>النوع</th>
                    <th>الكمية</th>
                    <th>المبلغ</th>
                    <th>الطريقة</th>
                <?php elseif ($category === 'Expenses'): ?>
                    <th>التاريخ</th>
                    <th>البند</th>
                    <th>الوصف</th>
                    <th>المبلغ</th>
                    <th>الطريقة</th>
                <?php elseif ($category === 'Payments'): ?>
                    <th>التاريخ</th>
                    <th>العميل</th>
                    <th>المبلغ</th>
                    <th>الطريقة</th>
                <?php else: ?>
                    <th>التاريخ</th>
                    <th>العميل</th>
                    <th>المبلغ</th>
                    <th>السبب</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php if ($category === 'Sales'): ?>
                        <td><?= $row['sale_date'] ?></td>
                        <td><?= htmlspecialchars($row['customer_name'] ?? 'عام') ?></td>
                        <td><?= $row['qat_status'] ?></td>
                        <td><?= $row['unit_type'] == 'weight' ? ($row['weight_grams']/1000 . ' كجم') : ($row['quantity_units'] . ' ربطة') ?></td>
                        <td class="fw-bold"><?= number_format($row['price']) ?></td>
                        <td><?= $row['payment_method'] ?></td>
                    <?php elseif ($category === 'Expenses'): ?>
                        <td><?= $row['expense_date'] ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td class="fw-bold text-danger"><?= number_format($row['amount']) ?></td>
                        <td><?= $row['payment_method'] ?></td>
                    <?php elseif ($category === 'Payments'): ?>
                        <td><?= $row['payment_date'] ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td class="fw-bold text-success"><?= number_format($row['amount']) ?></td>
                        <td><?= $row['payment_method'] ?></td>
                    <?php else: ?>
                        <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                        <td><?= htmlspecialchars($row['cust_name']) ?></td>
                        <td class="fw-bold"><?= number_format($row['amount']) ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
