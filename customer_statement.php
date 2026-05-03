<?php
require 'config/db.php';

if (!isset($_GET['id'])) {
    die("معرف العميل مطلوب.");
}

$id = (int)$_GET['id'];

// Date range filtering (#36)
$from = $_GET['from'] ?? null;
$to   = $_GET['to']   ?? null;

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("العميل غير موجود.");
}

// Build date filter condition
$dateFilter = '';
$dateParams = [$id];
if ($from && $to) {
    $dateFilter = " AND DATE(t_date) BETWEEN ? AND ?";
    $dateParamsExtra = [$from, $to];
} elseif ($from) {
    $dateFilter = " AND DATE(t_date) >= ?";
    $dateParamsExtra = [$from];
} elseif ($to) {
    $dateFilter = " AND DATE(t_date) <= ?";
    $dateParamsExtra = [$to];
} else {
    $dateParamsExtra = [];
}

// 1. Fetch ALL Sales (both Cash and Debt)
// For Cash/Transfer: debit=price, credit=price (already paid, no effect on debt balance)
// For Debt:          debit=price, credit=0    (unpaid, increases debt balance)
$salesStmt = $pdo->prepare(
    "
    SELECT s.sale_date as t_date,
           CASE WHEN s.payment_method = 'Debt' THEN 'بيع آجل' ELSE CONCAT('بيع (' , s.payment_method, ')') END as t_type,
           CONCAT(COALESCE(t.name,'?'), ' (', COALESCE(s.weight_kg, s.quantity_units), ' ', CASE WHEN s.unit_type='weight' THEN 'كجم' ELSE 'وحدة' END, ')') as t_desc,
           s.price as debit,
           CASE WHEN s.payment_method != 'Debt' THEN s.price ELSE 0 END as credit,
           s.id as ref_id,
           s.payment_method
    FROM sales s
    LEFT JOIN qat_types t ON s.qat_type_id = t.id
    WHERE s.customer_id = ? AND s.is_returned = 0" . ($dateFilter ? str_replace('t_date', 's.sale_date', $dateFilter) : "")
);
$salesStmt->execute(array_merge([$id], $dateParamsExtra));
$sales_data = $salesStmt->fetchAll();

// 2. Fetch Payments (Credits)
$payStmt = $pdo->prepare(
    "
    SELECT payment_date as t_date, 'سداد' as t_type,
           note as t_desc, 0 as debit, amount as credit, id as ref_id
    FROM payments
    WHERE customer_id = ?" . ($dateFilter ? str_replace('t_date', 'payment_date', $dateFilter) : "")
);
$payStmt->execute(array_merge([$id], $dateParamsExtra));
$pay_data = $payStmt->fetchAll();

// 3. Fetch Refunds (Credits to Debt)
$refStmt = $pdo->prepare(
    "
    SELECT created_at as t_date, 'مرتجع' as t_type,
           reason as t_desc, 0 as debit, amount as credit, id as ref_id
    FROM refunds
    WHERE customer_id = ? AND refund_type = 'Debt'" . ($dateFilter ? str_replace('t_date', 'created_at', $dateFilter) : "")
);
$refStmt->execute(array_merge([$id], $dateParamsExtra));
$ref_data = $refStmt->fetchAll();

// Combine and Sort
$transactions = array_merge($sales_data, $pay_data, $ref_data);
usort($transactions, function ($a, $b) {
    return strtotime($a['t_date']) - strtotime($b['t_date']); // Oldest first
});

$business_name = "القادري و ماجد - لأجود أنواع القات";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>كشف حساب - <?= htmlspecialchars($customer['name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
        }

        .statement-header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .running-balance {
            font-weight: bold;
            background-color: #f1f1f1 !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .container {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="container mt-4">
        <!-- Date Filter (no-print) -->
        <div class="no-print card shadow-sm mb-4">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="GET">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">من تاريخ</label>
                        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">إلى تاريخ</label>
                        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">تصفية</button>
                    </div>
                    <div class="col-md-2">
                        <a href="customer_statement.php?id=<?= $id ?>" class="btn btn-outline-secondary w-100">إعادة تعيين</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="no-print mb-4 text-center d-flex justify-content-center flex-wrap gap-2">
            <!-- Print / Save as PDF -->
            <button onclick="window.print()" class="btn btn-primary btn-lg px-4">
                <i class="fas fa-print me-2"></i>طباعة / PDF
            </button>
            <?php
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $stmtUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/customer_statement.php?id=' . $id;
            // WhatsApp: send the PDF/statement link - user prints or saves as PDF then shares
            $waMsg = rawurlencode(
                "مرحباً *{$customer['name']}* 👋\n\n" .
                    "كشف حسابك لدى *القادري و ماجد*:\n\n" .
                    "💰 الرصيد المستحق: *" . number_format($customer['total_debt']) . " ريال*\n\n" .
                    "📄 كشف الحساب الكامل:\n{$stmtUrl}\n\n" .
                    "يرجى السداد في أقرب وقت.\n*القادري و ماجد*"
            );
            $waPhone = '967' . ltrim(substr($customer['phone'], -9), '0');
            ?>
            <!-- Send statement link via WhatsApp (user opens & prints/saves as PDF from phone) -->
            <a href="https://wa.me/<?= $waPhone ?>?text=<?= $waMsg ?>"
                target="_blank"
                class="btn btn-success btn-lg px-4">
                <i class="fab fa-whatsapp me-2"></i>إرسال الكشف (واتساب)
            </a>
            <?php
            $back = $_GET['back'] ?? "customer_details.php?id=$id";
            ?>
            <a href="<?= htmlspecialchars($back) ?>" class="btn btn-secondary btn-lg px-4">
                <i class="fas fa-arrow-right me-2"></i>عودة
            </a>
        </div>

        <div class="statement-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="logo.jpg" alt="Logo" class="me-3 rounded-circle shadow-sm" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #333;">
                <div>
                    <h1 class="fw-bold mb-0">كشف حساب عميل</h1>
                    <?php if ($from || $to): ?>
                        <p class="text-muted small mb-0">الفترة: <?= $from ?: 'البداية' ?> إلى <?= $to ?: 'اليوم' ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <h2 class="fw-bold mb-1"><?= $business_name ?></h2>
                <p class="mb-1 text-muted">ت: 775065459 - 774456261</p>
                <p class="mb-0 small"><?= date('Y-m-d H:i') ?></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <h5>بيانات العميل:</h5>
                <div class="border p-3 rounded bg-light">
                    <h4 class="mb-1"><?= htmlspecialchars($customer['name']) ?></h4>
                    <p class="mb-0">الهاتف: <?= htmlspecialchars($customer['phone']) ?></p>
                </div>
            </div>
            <div class="col-6 text-end">
                <h5>ملخص الدين:</h5>
                <div class="border p-3 rounded bg-danger text-white">
                    <small>إجمالي المديونية الحالية</small>
                    <h2 class="mb-0"><?= number_format($customer['total_debt']) ?> YER</h2>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th>التاريخ</th>
                    <th>الإجراء</th>
                    <th>التفاصيل</th>
                    <th>مدين (بيع)</th>
                    <th>دائن (سداد)</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $runningBalance = 0;
                foreach ($transactions as $t):
                    $runningBalance += ($t['debit'] - $t['credit']);
                ?>
                    <tr>
                        <td class="text-center"><?= date('Y-m-d', strtotime($t['t_date'])) ?></td>
                        <td class="text-center"><?= $t['t_type'] ?></td>
                        <td><?= htmlspecialchars($t['t_desc']) ?></td>
                        <td class="text-end"><?= $t['debit'] > 0 ? number_format($t['debit']) : '-' ?></td>
                        <td class="text-end text-success"><?= $t['credit'] > 0 ? number_format($t['credit']) : '-' ?></td>
                        <td class="text-end running-balance"><?= number_format($runningBalance) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا توجد حركات في هذه الفترة.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="mt-5 text-center text-muted small">
            <p>شكراً لتعاملكم معنا. هذا الكشف للفترة حتى تاريخ <?= date('Y-m-d') ?></p>
        </div>
    </div>

</body>

</html>