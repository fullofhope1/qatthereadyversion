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

// ✅ AUTO-REPAIR: Recalculate actual debt to fix any cache drift (like the 21,000 vs 7,000 issue)
$actualDebtStmt = $pdo->prepare("
    SELECT 
        (COALESCE(opening_balance, 0) - COALESCE(paid_opening_balance, 0)) + 
        (SELECT COALESCE(SUM(price - paid_amount - COALESCE(refund_amount, 0)), 0) 
         FROM sales WHERE customer_id = ? AND is_paid = 0 AND is_returned = 0) as real_debt
    FROM customers WHERE id = ?
");
$actualDebtStmt->execute([$id, $id]);
$realDebt = (float)$actualDebtStmt->fetchColumn();

if (abs($realDebt - (float)$customer['total_debt']) > 0.01) {
    $pdo->prepare("UPDATE customers SET total_debt = ? WHERE id = ?")->execute([$realDebt, $id]);
    $customer['total_debt'] = $realDebt; // Update for current page display
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
$backUrl = 'customers.php';
if ($back === 'debts') $backUrl = 'debts.php';
elseif (str_contains($back, '.php')) $backUrl = $back;
?>

<div class="row mb-3">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary">&larr; عودة</a>
        <a href="customer_statement.php?id=<?= $id ?>&back=<?= urlencode($backUrl) ?>" class="btn btn-dark shadow-sm">
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
                <?php if ($customer['opening_balance'] > 0): ?>
                    <div class="mb-2">
                        <span class="badge bg-secondary">رصيد افتتاحي: <?= number_format($customer['opening_balance']) ?></span>
                    </div>
                <?php endif; ?>
                <hr>
                <h5 class="text-muted"><?= $customer['total_debt'] < 0 ? 'رصيد دائن (له)' : 'الدين الحالي' ?></h5>
                <h2 class="<?= $customer['total_debt'] < 0 ? 'text-success' : 'text-danger' ?> display-4">
                    <?= number_format(abs($customer['total_debt'])) ?>
                </h2>
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
                        <label class="form-label">طريقة السداد</label>
                        <select class="form-select shadow-sm" name="payment_method" id="payMethod" onchange="toggleTransferFields()">
                            <option value="Cash">نقدي</option>
                            <option value="Transfer">حوالة</option>
                        </select>
                    </div>

                    <!-- Extra fields for Transfer -->
                    <div id="transferFields" class="d-none border rounded p-3 mb-3 bg-light animate__animated animate__fadeIn">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="small fw-bold">اسم المرسل</label>
                                <input type="text" name="transfer_sender" class="form-control form-control-sm" placeholder="اختياري">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">اسم المستلم</label>
                                <input type="text" name="transfer_receiver" class="form-control form-control-sm" placeholder="اختياري">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">رقم الحوالة</label>
                                <input type="text" name="transfer_number" class="form-control form-control-sm" placeholder="رقم الحوالة">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">شركة الصرافة</label>
                                <input type="text" name="transfer_company" class="form-control form-control-sm" placeholder="اختياري">
                            </div>
                        </div>
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

    <script>
        function toggleTransferFields() {
            const method = document.getElementById('payMethod').value;
            const fields = document.getElementById('transferFields');
            if (method === 'Transfer') {
                fields.classList.remove('d-none');
            } else {
                fields.classList.add('d-none');
            }
        }
    </script>

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
                            <th>الطريقة</th>
                            <th>ملاحظة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= $p['payment_date'] ?></td>
                                <td class="text-success fw-bold"><?= number_format($p['amount']) ?></td>
                                <td>
                                    <?php if (($p['payment_method'] ?? 'Cash') === 'Transfer'): ?>
                                        <span class="badge bg-info mb-1">حوالة</span>
                                        <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.2;">
                                            <div><strong>من:</strong> <?= htmlspecialchars($p['transfer_sender'] ?? '-') ?></div>
                                            <div><strong>رقم:</strong> <?= htmlspecialchars($p['transfer_number'] ?? '-') ?></div>
                                            <div><strong>عبر:</strong> <?= htmlspecialchars($p['transfer_company'] ?? '-') ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">نقدي</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($p['note']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>