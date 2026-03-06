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

    .staff-avatar {
        width: 40px;
        height: 40px;
        background: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #6c757d;
    }
</style>

<div class="card bg-gradient-warning border-0 shadow-sm mb-4 text-dark p-4" style="border-radius: 20px; background: linear-gradient(135deg, #fceabb 0%, #f8b500 100%);">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-users-cog me-2"></i> تقرير مستحقات ومسحوبات الموظفين</h4>
            <p class="mb-0 opacity-75">الفترة الحالية: <?= $periodTitle ?></p>
        </div>
        <div class="text-end">
            <div class="h3 fw-bold mb-0"><?= number_format($totalPeriodWithdrawals) ?></div>
            <div class="small fw-bold opacity-50">إجمالي المسحوبات (ريال)</div>
        </div>
    </div>
</div>

<!-- Overdraft Notifications (#35) -->
<?php
$overdrafts = [];
foreach ($staff as $s) {
    if ($s['base_salary'] > 0) {
        $w = $withdrawals[$s['id']] ?? 0;
        $p = ($w / $s['base_salary']) * 100;
        if ($p >= 90) {
            $overdrafts[] = [
                'name' => $s['name'],
                'percent' => round($p, 1),
                'amount' => $w,
                'salary' => $s['base_salary']
            ];
        }
    }
}

if (!empty($overdrafts)): ?>
    <div class="alert alert-danger shadow-sm border-0 mb-4 animate__animated animate__shakeX" style="border-radius: 15px;">
        <h6 class="fw-bold mb-3"><i class="fas fa-exclamation-triangle me-2"></i> تنبيه: موظفون تجاوزوا حد السحب (90%+)</h6>
        <div class="row g-3">
            <?php foreach ($overdrafts as $o): ?>
                <div class="col-md-4">
                    <div class="bg-white bg-opacity-75 p-2 rounded border border-danger border-opacity-25 d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($o['name']) ?></span>
                        <span class="badge bg-danger"><?= $o['percent'] ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list me-2 text-primary"></i> بيان الموظفين</h5>
        <!-- Search box (#27) -->
        <input type="text" id="staffReportSearch" class="form-control w-25" placeholder="بحث باسم الموظف..." oninput="filterStaffReport()">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>المسمى الوظيفي</th>
                        <th class="text-center">الراتب الأساسي</th>
                        <th class="text-center">المسحوب (الفترة)</th>
                        <th class="text-center">إحصائية السحب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s):
                        $w = $withdrawals[$s['id']] ?? 0;
                        $percent = $s['base_salary'] > 0 ? ($w / $s['base_salary']) * 100 : 0;
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="staff-avatar me-3">
                                        <?= mb_substr($s['name'], 0, 1, 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                        <div class="small text-muted">ID: <?= $s['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['role']) ?></span>
                            </td>
                            <td class="text-center fw-bold text-dark">
                                <?= number_format($s['base_salary']) ?>
                            </td>
                            <td class="text-center">
                                <span class="h6 fw-bold <?= $w > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= number_format($w) ?>
                                </span>
                            </td>
                            <td class="text-center" style="min-width: 150px;">
                                <?php if ($s['base_salary'] > 0): ?>
                                    <div class="small text-muted mb-1"><?= round($percent, 1) ?>% من الراتب</div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?= $percent > 80 ? 'bg-danger' : ($percent > 50 ? 'bg-warning' : 'bg-success') ?>"
                                            role="progressbar" style="width: <?= min($percent, 100) ?>%"></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">---</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function filterStaffReport() {
        const term = document.getElementById('staffReportSearch').value.toLowerCase();
        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }
</script>