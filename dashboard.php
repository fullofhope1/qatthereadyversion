<?php
require 'config/db.php';
include 'includes/header.php';
?>


<?php
$today = date('Y-m-d');

// Today's Sales Total
$todaySales = $pdo->query("SELECT SUM(price) FROM sales WHERE sale_date = '$today'")->fetchColumn() ?: 0;

// Total Debts
$totalDebts = $pdo->query("SELECT SUM(total_debt) FROM customers")->fetchColumn() ?: 0;

// Today's Expenses
$todayExpenses = $pdo->query("SELECT SUM(amount) FROM expenses WHERE expense_date = '$today'")->fetchColumn() ?: 0;

// Today's Purchases
$todayPurchases = $pdo->query("SELECT SUM(agreed_price) FROM purchases WHERE purchase_date = '$today'")->fetchColumn() ?: 0;
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
                        <h3 class="card-title"><?= number_format($todaySales) ?> ريال</h3>
                        <a href="sales.php" class="btn btn-light btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>

            <!-- Total Debts -->
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3 shadow">
                    <div class="card-header"><i class="fas fa-file-invoice-dollar me-1"></i> إجمالي الديون</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= number_format($totalDebts) ?> ريال</h3>
                        <a href="debts.php" class="btn btn-light btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>

            <!-- Today's Expenses -->
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3 shadow">
                    <div class="card-header"><i class="fas fa-wallet me-1"></i> مصاريف اليوم</div>
                    <div class="card-body">
                        <h3 class="card-title text-dark"><?= number_format($todayExpenses) ?> ريال</h3>
                        <a href="expenses.php" class="btn btn-dark btn-sm">عرض <i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
            </div>

            <!-- Today's Purchases -->
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3 shadow">
                    <div class="card-header"><i class="fas fa-truck me-1"></i> مشتريات اليوم</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= number_format($todayPurchases) ?> ريال</h3>
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