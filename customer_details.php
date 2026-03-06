<?php
require 'config/db.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$id = $_GET['id'];
$back = $_GET['back'] ?? 'customers'; // Support back=debts for debt management

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    echo "<div class='alert alert-danger'>لم يتم العثور على العميل!</div>";
    include 'includes/footer.php';
    exit;
}

// Fetch Payment History
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY payment_date DESC LIMIT 10");
$payStmt->execute([$id]);
$payments = $payStmt->fetchAll();

// Fetch Sales History
$salesStmt = $pdo->prepare("SELECT s.*, t.name as type_name FROM sales s LEFT JOIN qat_types t ON s.qat_type_id = t.id WHERE customer_id = ? ORDER BY sale_date DESC LIMIT 10");
$salesStmt->execute([$id]);
$sales = $salesStmt->fetchAll();

// Determine back URL
$backUrl = ($back === 'debts') ? 'debts.php' : 'customers.php';
?>

<div class="row mb-3">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <a href="<?= $backUrl ?>" class="btn btn-secondary">&larr; <?= ($back === 'debts') ? 'العودة لإدارة الديون' : 'العودة للقائمة' ?></a>
        <a href="customer_statement.php?id=<?= $id ?>" class="btn btn-dark shadow-sm">
            <i class="fas fa-print me-2"></i> كشف حساب
        </a>
    </div>
</div>

<div class="row">
    <!-- Customer Info & Debt -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <h3><?= htmlspecialchars($customer['name']) ?></h3>
                <p class="text-muted"><?= htmlspecialchars($customer['phone']) ?></p>
                <hr>
                <h5 class="text-muted">الدين الحالي</h5>
                <h2 class="text-danger display-4"><?= number_format($customer['total_debt']) ?></h2>
                <small>ريال</small>
            </div>
        </div>

        <!-- Add Payment Form -->
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">تسجيل سداد</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['pay_error'])): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['pay_error']) ?></div>
                <?php endif; ?>
                <form action="requests/process_payment.php" method="POST">
                    <input type="hidden" name="customer_id" value="<?= $id ?>">
                    <input type="hidden" name="back" value="<?= $back ?>">

                    <div class="mb-3">
                        <label class="form-label">المبلغ <span class="text-muted small">(الحد الأقصى: <?= number_format($customer['total_debt']) ?> ريال)</span></label>
                        <input type="number" step="1" class="form-control" name="amount" required
                            max="<?= $customer['total_debt'] ?>" min="1"
                            placeholder="أدخل مبلغ السداد">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ملاحظة</label>
                        <textarea class="form-control" name="note" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100">سداد</button>
                </form>
            </div>
        </div>
    </div>

    <!-- History -->
    <div class="col-md-8">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button">المشتريات الأخيرة</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">سجل السدادات</button>
            </li>
        </ul>
        <div class="tab-content pt-3" id="myTabContent">
            <!-- Sales Tab -->
            <div class="tab-pane fade show active" id="sales">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>السعر</th>
                            <th>حالة الدفع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $s): ?>
                            <tr>
                                <td><?= $s['sale_date'] ?></td>
                                <td><?= $s['type_name'] ?? '?' ?></td>
                                <td><?= number_format($s['price']) ?></td>
                                <td><?= $s['is_paid'] ? '<span class="badge bg-success">نقد</span>' : '<span class="badge bg-danger">آجل</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Payments Tab -->
            <div class="tab-pane fade" id="payments">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>ملاحظة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= $p['payment_date'] ?></td>
                                <td class="text-success fw-bold"><?= number_format($p['amount']) ?></td>
                                <td><?= htmlspecialchars($p['note']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>