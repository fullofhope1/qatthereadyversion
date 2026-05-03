<?php
require 'config/db.php';
include 'includes/header.php';
?>


<?php
$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Initialize Repositories
$reportRepo = new ReportRepository($pdo);
$expenseRepo = new ExpenseRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);

// Fetch Isolated Data (By Team Role)
// 1. Today's Sales (Team level)
$todaySales = $pdo->prepare("SELECT SUM(s.price - COALESCE(s.refund_amount, 0)) FROM sales s JOIN users u ON s.created_by = u.id WHERE s.sale_date = ? AND s.is_returned = 0 AND u.role = ?");
$todaySales->execute([$today, $user_role]);
$todaySalesTotal = $todaySales->fetchColumn() ?: 0;

// 2. Total Debts (Team level)
$totalDebts = $pdo->prepare("SELECT SUM(c.total_debt) FROM customers c JOIN users u ON c.created_by = u.id WHERE u.role = ?");
$totalDebts->execute([$user_role]);
$totalDebtsTotal = $totalDebts->fetchColumn() ?: 0;

// 3. Today's Expenses (Team level)
$expenses = $expenseRepo->getTodayExpenses($today, $user_id, $user_role);
$todayExpensesTotal = 0;
foreach($expenses as $e) $todayExpensesTotal += $e['amount'];

// 4. Today's Purchases (Team level)
$todayPurchases = $pdo->prepare("SELECT SUM(p.agreed_price) FROM purchases p JOIN users u ON p.created_by = u.id WHERE p.purchase_date = ? AND u.role = ?");
$todayPurchases->execute([$today, $user_role]);
$todayPurchasesTotal = $todayPurchases->fetchColumn() ?: 0;
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4 text-center"><i class="fas fa-tachometer-alt me-2"></i> لوحة التحكم</h1>
        <div class="row">
            <!-- Today's Sales -->
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3 shadow">
                    <div class="card-header"><i class="fas fa-shopping-cart me-1"></i> مبيعات اليوم</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= number_format($todaySalesTotal) ?> ريال</h3>
                        <a href="sales.php" class="btn btn-light btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>

            <!-- Total Debts -->
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3 shadow">
                    <div class="card-header"><i class="fas fa-file-invoice-dollar me-1"></i> إجمالي الديون</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= number_format($totalDebtsTotal) ?> ريال</h3>
                        <a href="debts.php" class="btn btn-light btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>

            <!-- Today's Expenses -->
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3 shadow">
                    <div class="card-header"><i class="fas fa-wallet me-1"></i> مصاريف اليوم</div>
                    <div class="card-body">
                        <h3 class="card-title text-dark"><?= number_format($todayExpensesTotal) ?> ريال</h3>
                        <a href="expenses.php" class="btn btn-dark btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>

            <!-- Today's Purchases -->
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3 shadow">
                    <div class="card-header"><i class="fas fa-truck me-1"></i> مشتريات اليوم</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= number_format($todayPurchasesTotal) ?> ريال</h3>
                        <a href="purchases.php" class="btn btn-light btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> إجراءات سريعة</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <a href="sales.php" class="btn btn-success btn-lg"><i class="fas fa-plus-circle me-2"></i> مبيعات جديدة</a>
                            <a href="purchases.php" class="btn btn-primary btn-lg"><i class="fas fa-truck me-2"></i> استلام بضاعة</a>
                            <a href="expenses.php" class="btn btn-warning btn-lg"><i class="fas fa-wallet me-2"></i> إضافة مصروف</a>
                            <a href="reports.php" class="btn btn-info btn-lg text-white"><i class="fas fa-chart-line me-2"></i> عرض التقارير</a>
                            <a href="closing.php" class="btn btn-danger btn-lg"><i class="fas fa-calendar-check me-2"></i> إغلاق اليوم</a>
                            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                <a href="manage_ads.php" class="btn btn-dark btn-lg border-info"><i class="fas fa-ad me-2"></i> إدارة الإعلانات</a>
                                <a href="manage_products.php" class="btn btn-dark btn-lg border-info"><i class="fas fa-leaf me-2"></i> إدارة المنتجات</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>