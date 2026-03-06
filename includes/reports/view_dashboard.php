<div class="row g-4 mb-5">
    <!-- 1. NET WORTH CARD -->
    <div class="col-md-4">
        <div class="card bg-gradient-primary text-white h-100 shadow border-0" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="text-uppercase opacity-75 fw-bold letter-spacing-1">القيمة الصافية التقديرية</h5>
                    </div>
                    <i class="fas fa-gem fs-1 opacity-50"></i>
                </div>
                <h1 class="display-4 fw-bold mb-0"><?= number_format($netWorth) ?> <small class="fs-6">ريال</small></h1>
                <div class="mt-3 opacity-75 small">
                    <i class="fas fa-info-circle me-1"></i> الأصول - الالتزامات
                </div>
            </div>
        </div>
    </div>

    <!-- 2. ASSETS BREAKDOWN -->
    <div class="col-md-8">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-coins text-success me-2"></i> توزيع الأصول</h5>
            </div>
            <div class="card-body">
                <div class="row text-center h-100 align-items-center">
                    <!-- Cash -->
                    <div class="col-md-4 border-end">
                        <h6 class="text-muted text-uppercase small px-2">صافي نقد اليوم</h6>
                        <h4 class="text-success fw-bold"><?= number_format($netCash) ?></h4>
                    </div>
                    <!-- Receivables -->
                    <div class="col-md-4 border-end">
                        <h6 class="text-muted text-uppercase small px-2">إجمالي الديون عند العملاء</h6>
                        <h4 class="text-primary fw-bold"><?= number_format($totalReceivables) ?></h4>
                    </div>
                    <!-- Inventory -->
                    <div class="col-md-4">
                        <h6 class="text-muted text-uppercase small px-2">قيمة المخزون التقديرية</h6>
                        <h4 class="text-info fw-bold"><?= number_format($inventoryValue) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PARTNER / PROFIT SECTION -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i> الربح والأداء (اليوم)</h5>
                <span class="badge bg-white text-success fs-6"><?= $date ?></span>
            </div>
            <div class="card-body">
                <div class="row g-4 text-center">
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <h6 class="text-muted mb-1">إجمالي المبيعات</h6>
                            <h3 class="fw-bold text-dark mb-0"><?= number_format($totalSales) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <h6 class="text-muted mb-1">إجمالي التكلفة (المشتريات)</h6>
                            <h3 class="fw-bold text-dark mb-0"><?= number_format($totalPurchases) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <h6 class="text-muted mb-1">إجمالي المصاريف</h6>
                            <h3 class="fw-bold text-danger mb-0"><?= number_format($totalExpenses) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-<?= $netProfit >= 0 ? 'success' : 'danger' ?> text-white">
                            <h6 class="opacity-75 mb-1">صافي الربح</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($netProfit) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>