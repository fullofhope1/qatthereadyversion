<?php
// reports/view_leftovers.php

$sub = $_GET['sub'] ?? 'Inventory'; // Sub-view: Inventory or Sales
$tomorrow = date('Y-m-d', strtotime('+1 day'));

if ($reportType == 'Daily') {
    $where = "WHERE source_date = '$date'";
    // For Momsi, show tomorrow's stock (carried over FROM today)
    $whereP = "WHERE purchase_date = '$tomorrow' AND status = 'Momsi'";
    $periodTitle = "التاريخ: $date (+ المحول للغد)";
} elseif ($reportType == 'Monthly') {
    $where = "WHERE MONTH(source_date) = '$month' AND YEAR(source_date) = '$year'";
    $whereP = "WHERE MONTH(purchase_date) = '$month' AND YEAR(purchase_date) = '$year' AND status = 'Momsi'";
    $periodTitle = "الشهر: $month/$year";
} else {
    $where = "WHERE YEAR(source_date) = '$year'";
    $whereP = "WHERE YEAR(purchase_date) = '$year' AND status = 'Momsi'";
    $periodTitle = "السنة: $year";
}

// 1. DATA FOR INVENTORY TAB
// Get leftovers from the leftovers table (manually managed)
$sql = "SELECT l.*, p.vendor_name, t.name as type_name, 'manual' as source_type
        FROM leftovers l
        LEFT JOIN purchases p ON l.purchase_id = p.id
        LEFT JOIN qat_types t ON p.qat_type_id = t.id
        $where 
        AND p.vendor_name IS NOT NULL
        ORDER BY l.source_date DESC";

$leftovers = $pdo->query($sql)->fetchAll();

// Also get Momsi purchases from closing process
$sqlMomsi = "SELECT p.id, p.purchase_date as source_date, prov.name as vendor_name, t.name as type_name, 
             p.quantity_kg as weight_kg, 'محول ممسية' as action, 
             'تم إنشاؤه تلقائياً عند الإغلاق اليومي' as notes, 'closing' as source_type
             FROM purchases p
             LEFT JOIN qat_types t ON p.qat_type_id = t.id
             LEFT JOIN providers prov ON p.provider_id = prov.id
             $whereP
             ORDER BY p.purchase_date DESC";

$momsi = $pdo->query($sqlMomsi)->fetchAll();
$leftovers = array_merge($leftovers, $momsi);

$totalWeight = 0;
$totalUnits = 0;
foreach ($leftovers as $l) {
    if ($l['unit_type'] === 'weight') {
        $totalWeight += $l['weight_kg'];
    } else {
        $totalUnits += $l['quantity_units'];
    }
}

// 2. DATA FOR SALES TAB (Rebirth Sales)
$selectedProvider = $_GET['provider_id'] ?? null;

// Fetch unique providers who have leftover sales
$sqlProv = "SELECT DISTINCT prov.id, prov.name 
            FROM providers prov
            JOIN purchases p ON prov.id = p.provider_id
            JOIN sales s ON p.id = s.purchase_id
            WHERE s.qat_status = 'Momsi' OR s.leftover_id IS NOT NULL";
$providersWithSales = $pdo->query($sqlProv)->fetchAll();

$rebirthSales = [];
if ($selectedProvider) {
    $sqlRS = "SELECT s.*, c.name as cust_name, t.name as type_name, prov.name as prov_name
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.id
              LEFT JOIN purchases p ON s.purchase_id = p.id
              LEFT JOIN providers prov ON p.provider_id = prov.id
              LEFT JOIN qat_types t ON s.qat_type_id = t.id
              WHERE (s.qat_status = 'Momsi' OR s.leftover_id IS NOT NULL)
              AND p.provider_id = ?
              ORDER BY s.sale_date DESC";
    $stmtRS = $pdo->prepare($sqlRS);
    $stmtRS->execute([$selectedProvider]);
    $rebirthSales = $stmtRS->fetchAll();
}
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 bg-dark text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-recycle me-2"></i> البقايا - <?= $periodTitle ?></h4>
                </div>
                <!-- Sub-nav pills -->
                <div class="btn-group">
                    <a href="?view=Leftovers&sub=Inventory&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn <?= $sub == 'Inventory' ? 'btn-primary' : 'btn-outline-light' ?>">المخزن</a>
                    <a href="?view=Leftovers&sub=Sales&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn <?= $sub == 'Sales' ? 'btn-primary' : 'btn-outline-light' ?>">المبيعات</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($sub == 'Inventory'): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-warehouse me-2"></i> البضاعة المتبقية</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-danger fs-6">إجمالي الوزن: <?= number_format($totalWeight, 2) ?> كجم</span>
                <?php if ($totalUnits > 0): ?>
                    <span class="badge bg-success fs-6">إجمالي الوحدات: <?= $totalUnits ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Search box (#27) -->
            <div class="p-3 pb-0">
                <input type="text" id="leftoverInvSearch" class="form-control" placeholder="بحث باسم الرعوي أو النوع أو الملاحظات..." oninput="filterLeftoverInv()">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>اليوم والتاريخ</th>
                            <th>الرعوي</th>
                            <th>النوع</th>
                            <th>الكمية</th>
                            <th>الحالة الإجرائية</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leftovers) > 0): ?>
                            <?php foreach ($leftovers as $l): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= getArabicDay($l['source_date']) ?></div>
                                        <div class="small text-muted"><?= $l['source_date'] ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($l['vendor_name'] ?? 'غير معروف') ?></td>
                                    <td><?= htmlspecialchars($l['type_name'] ?? 'غير معروف') ?></td>
                                    <td class="fw-bold">
                                        <?php if ($l['unit_type'] === 'weight'): ?>
                                            <?= number_format($l['weight_kg'], 2) ?> كجم
                                        <?php else: ?>
                                            <?= $l['quantity_units'] ?> <?= htmlspecialchars($l['unit_type'] ?: 'وحدة') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $s = $l['status'];
                                        if ($s === 'Momsi_Day_1') {
                                            echo '<span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-star"></i> المبيعة الأولى</span>';
                                        } elseif ($s === 'Momsi_Day_2') {
                                            echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="fas fa-star-half-alt"></i> المبيعة الثانية</span>';
                                        } elseif ($s === 'Auto_Dropped' || $s === 'Dropped' || $s === 'Reception_Loss') {
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fas fa-times-circle"></i> تالف / فاقد</span>';
                                        } elseif (isset($l['source_type']) && $l['source_type'] == 'closing') {
                                            echo '<span class="badge bg-info-subtle text-info border border-info"><i class="fas fa-moon"></i> ممسية</span>';
                                        } else {
                                            echo '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="fas fa-arrow-right"></i> ' . htmlspecialchars($s) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($l['notes'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">لا توجد بقايا مسجلة في هذه الفترة.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function filterLeftoverInv() {
            const term = document.getElementById('leftoverInvSearch').value.toLowerCase();
            document.querySelectorAll('.table-hover tbody tr').forEach(row => {
                if (row.cells.length < 6) return;
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        }
    </script>

<?php else: ?>
    <!-- SALES SUB-TAB -->
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4 text-end" dir="rtl">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">اختر الرعوي</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($providersWithSales)): ?>
                        <div class="list-group-item text-muted text-center py-4">لا توجد مبيعات بقايا مسجلة</div>
                    <?php else: ?>
                        <?php foreach ($providersWithSales as $prov): ?>
                            <a href="?view=Leftovers&sub=Sales&provider_id=<?= $prov['id'] ?>&report_type=<?= $reportType ?>&date=<?= $date ?>&month=<?= $month ?>&year=<?= $year ?>"
                                class="list-group-item list-group-item-action <?= $selectedProvider == $prov['id'] ? 'active' : '' ?>">
                                <i class="fas fa-user-tie me-2"></i> <?= htmlspecialchars($prov['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8 text-end" dir="rtl">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-users me-2"></i> قائمة الزبائن</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Search box (#27) -->
                    <div class="p-3 pb-0">
                        <input type="text" id="leftoverSalesSearch" class="form-control" placeholder="بحث باسم الزبون..." oninput="filterLeftoverSales()">
                    </div>
                    <?php if (!$selectedProvider): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                            يرجى اختيار مورد لرؤية مبيعات البقايا الخاصة به
                        </div>
                    <?php elseif (empty($rebirthSales)): ?>
                        <div class="text-center py-5 text-muted">لم يتم العثور على مبيعات لهذا المورد في البقايا</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark small">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>الزبون</th>
                                        <th>النوع</th>
                                        <th class="text-center">الوزن</th>
                                        <th class="text-end">السعر</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rebirthSales as $s): ?>
                                        <tr>
                                            <td class="small">
                                                <div><?= getArabicDay($s['sale_date']) ?></div>
                                                <div class="text-white-50"><?= $s['sale_date'] ?></div>
                                            </td>
                                            <td class="fw-bold"><?= htmlspecialchars($s['cust_name'] ?? 'زبون طيار') ?></td>
                                            <td><span class="badge bg-info-subtle text-dark border"><?= htmlspecialchars($s['type_name']) ?></span></td>
                                            <td class="text-center">
                                                <?php if (($s['unit_type'] ?? 'weight') === 'weight'): ?>
                                                    <?= ($s['weight_grams'] ?? 0) / 1000 ?> كجم
                                                <?php else: ?>
                                                    <?= (int)($s['quantity_units'] ?? 0) ?> <?= htmlspecialchars($s['unit_type']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold text-success"><?= number_format($s['price']) ?> ريال</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        function filterLeftoverSales() {
            const term = document.getElementById('leftoverSalesSearch').value.toLowerCase();
            document.querySelectorAll('.table-hover tbody tr').forEach(row => {
                if (row.cells.length < 5) return;
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        }
    </script>
<?php endif; ?>