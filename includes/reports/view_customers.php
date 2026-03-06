<style>
    .report-table-card {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
    }

    .report-table thead {
        background: #f1f4f8;
    }

    .report-table th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1.25rem 1rem;
        border: none;
        color: #495057;
    }

    .report-table td {
        padding: 1rem;
        border-color: #f1f4f8;
    }

    .cust-avatar {
        width: 35px;
        height: 35px;
        background: #f8f9fa;
        color: #0d6efd;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 1px solid #dee2e6;
    }
</style>

<div class="card bg-gradient-info border-0 shadow-sm mb-4 text-white p-4" style="border-radius: 20px; background: linear-gradient(135deg, #0575E6 0%, #021B79 100%);">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-users me-2"></i> تقرير الرصيد والديون للعملاء</h4>
            <p class="mb-0 opacity-75">إجمالي قاعدة البيانات: <?= count($customers) ?> عميل</p>
        </div>
        <div class="text-center text-md-end">
            <div class="h3 fw-bold mb-0"><?= number_format($totalDebt) ?></div>
            <div class="small fw-bold opacity-50 text-uppercase">إجمالي الديون المعلقة (ريال)</div>
        </div>
    </div>
</div>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list-ol me-2 text-primary"></i> قائمة العملاء حسب حجم الدين</h5>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary">تصدير PDF</button>
            <button class="btn btn-outline-secondary">تصدير Excel</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>بيانات الاتصال</th>
                        <th class="text-center">سقف الدين</th>
                        <th class="text-end">الرصيد الحالي</th>
                        <th class="text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $i => $c): ?>
                        <tr>
                            <td><span class="text-muted small">#<?= $i + 1 ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="cust-avatar me-3">
                                        <i class="fas fa-user small"></i>
                                    </div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-bold"><?= htmlspecialchars($c['phone']) ?></div>
                                <div class="small text-muted"><i class="fab fa-whatsapp text-success me-1"></i> متاح للطلب</div>
                            </td>
                            <td class="text-center">
                                <?php if ($c['debt_limit'] > 0): ?>
                                    <span class="fw-bold text-secondary"><?= number_format($c['debt_limit']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted fw-normal">بلا سقف</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <span class="h6 fw-bold <?= $c['total_debt'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($c['total_debt']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                if ($c['debt_limit'] > 0 && $c['total_debt'] > $c['debt_limit']) {
                                    echo '<span class="badge bg-danger rounded-pill">تجاوز السقف</span>';
                                } elseif ($c['total_debt'] > 0) {
                                    echo '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">دين قائم</span>';
                                } else {
                                    echo '<span class="badge bg-success-subtle text-success rounded-pill">خالص</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>