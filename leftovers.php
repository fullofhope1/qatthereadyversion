<?php
require 'config/db.php';
include 'includes/header.php';

$today = date('Y-m-d');

// 1. Fetch Today's Purchases - include provider name from providers table
$stmt = $pdo->prepare("SELECT p.id, p.vendor_name, p.qat_type_id, p.quantity_kg, t.name as type_name, prov.name as provider_name
                       FROM purchases p
                       JOIN qat_types t ON p.qat_type_id = t.id
                       LEFT JOIN providers prov ON p.provider_id = prov.id
                       WHERE p.purchase_date = ? AND p.status = 'Fresh'");
$stmt->execute([$today]);
$stocks = $stmt->fetchAll();

// 2. Fetch Today's Sales to calc remaining
$stmt2 = $pdo->prepare("SELECT purchase_id, SUM(weight_kg) as sold_kg FROM sales WHERE sale_date = ? AND purchase_id IS NOT NULL GROUP BY purchase_id");
$stmt2->execute([$today]);
$salesMap = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Fetch ALREADY MANAGED Leftovers for Today (all statuses count as handled)
$stmtL = $pdo->prepare("SELECT purchase_id, SUM(weight_kg) as managed_kg FROM leftovers WHERE source_date = ? AND status IN ('Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Auto_Dropped') GROUP BY purchase_id");
$stmtL->execute([$today]);
$managedMap = $stmtL->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Fetch History (with vendor name)
$stmt3 = $pdo->query("SELECT l.*, p.vendor_name FROM leftovers l LEFT JOIN purchases p ON l.purchase_id = p.id ORDER BY l.created_at DESC LIMIT 30");
$history = $stmt3->fetchAll();
?>

<div class="row">
    <!-- Management Section -->
    <div class="col-md-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">إدارة البقايا</h5>
                <small class="opacity-75">يوم <?= $today ?></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="fas fa-user-tie me-1"></i> الراعي (المورد)</th>
                                <th><i class="fas fa-leaf me-1"></i> النوع</th>
                                <th>شراء</th>
                                <th>بيع</th>
                                <th>باقي</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks as $s): ?>
                                <?php
                                $pid      = $s['id'];
                                $sold     = isset($salesMap[$pid]) ? $salesMap[$pid] : 0;
                                $managed  = isset($managedMap[$pid]) ? $managedMap[$pid] : 0;
                                $remaining = max(0, round($s['quantity_kg'] - $sold - $managed, 3));
                                ?>
                                <tr>
                                    <td class="fw-bold"><i class="fas fa-user-tie me-1 text-muted"></i><?= htmlspecialchars($s['provider_name'] ?: $s['vendor_name'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($s['type_name']) ?></td>
                                    <td><?= $s['quantity_kg'] ?> كجم</td>
                                    <td><?= $sold ?> كجم</td>
                                    <td class="fw-bold <?= $remaining > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= $remaining ?> كجم
                                        <?php if ($managed > 0): ?>
                                            <br><small class="text-muted">(<?= $managed ?> كجم تم التصرف بها)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($remaining > 0): ?>
                                            <button class="btn btn-sm btn-outline-danger shadow-sm"
                                                onclick="openModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['type_name'], ENT_QUOTES) ?>', <?= $s['qat_type_id'] ?>, <?= $remaining ?>, '<?= htmlspecialchars($s['vendor_name'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-tasks me-1"></i> تصرف
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success shadow-sm"><i class="fas fa-check me-1"></i> تم</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stocks)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا توجد مشتريات اليوم.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- History Section -->
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">سجل البقايا</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($history as $h): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold"><?= $h['weight_kg'] ?> كجم</div>
                                <small class="text-muted">
                                    <i class="far fa-clock me-1"></i><?= $h['source_date'] ?>
                                    <?php if (!empty($h['vendor_name'])): ?>
                                        — <i class="fas fa-user me-1"></i><?= htmlspecialchars($h['vendor_name']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <?php
                                $badge = match ($h['status']) {
                                    'Dropped'              => '<span class="badge bg-danger"><i class="fas fa-trash-alt me-1"></i> إتلاف يدوي</span>',
                                    'Auto_Dropped'         => '<span class="badge bg-danger opacity-75"><i class="fas fa-robot me-1"></i> إتلاف تلقائي</span>',
                                    'Transferred_Next_Day' => '<span class="badge bg-primary"><i class="fas fa-arrow-right me-1"></i> ترحيل يدوي</span>',
                                    'Auto_Momsi'           => '<span class="badge bg-info text-dark"><i class="fas fa-robot me-1"></i> ترحيل تلقائي</span>',
                                    default                => '<span class="badge bg-secondary">' . htmlspecialchars($h['status']) . '</span>'
                                };
                                echo $badge;
                                ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                        <li class="list-group-item text-center text-muted">لا يوجد سجل.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="leftoverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إدارة الباقي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/process_leftover.php" method="POST" onsubmit="return validateLeftover()">
                <div class="modal-body text-end">
                    <input type="hidden" name="purchase_id" id="m_pid">
                    <input type="hidden" name="qat_type_id" id="m_tid">
                    <input type="hidden" id="m_max_weight">

                    <p>النوع: <b id="m_tname"></b></p>
                    <p>المورد: <b id="m_vendor"></b></p>
                    <p>الكمية المتبقية القصوى: <b id="m_max_display" class="text-danger"></b> كجم</p>

                    <div class="mb-3">
                        <label>الكمية المراد التصرف بها</label>
                        <input type="number" step="0.01" class="form-control" name="weight_kg" id="m_weight"
                            placeholder="يجب ألا تتجاوز الكمية المتبقية">
                        <div class="text-danger small mt-1" id="weightError"></div>
                    </div>

                    <div class="mb-3">
                        <label>الإجراء</label>
                        <select class="form-select" name="action">
                            <option value="Drop">إتلاف / تالف</option>
                            <option value="SellNextDay">بيع اليوم التالي</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>ملاحظات</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(pid, tname, tid, weight, vendor) {
        document.getElementById('m_pid').value = pid;
        document.getElementById('m_tid').value = tid;
        document.getElementById('m_tname').innerText = tname;
        document.getElementById('m_vendor').innerText = vendor || '—';
        document.getElementById('m_weight').value = weight;
        document.getElementById('m_max_weight').value = weight;
        document.getElementById('m_max_display').innerText = weight;
        document.getElementById('weightError').innerText = '';

        new bootstrap.Modal(document.getElementById('leftoverModal')).show();
    }

    // Validate weight does not exceed remaining (#40)
    function validateLeftover() {
        const input = parseFloat(document.getElementById('m_weight').value) || 0;
        const max = parseFloat(document.getElementById('m_max_weight').value) || 0;
        if (input <= 0) {
            document.getElementById('weightError').innerText = 'يرجى إدخال كمية أكبر من صفر';
            return false;
        }
        if (input > max) {
            document.getElementById('weightError').innerText =
                `⚠ الكمية المدخلة (${input} كجم) تتجاوز الكمية المتبقية (${max} كجم)`;
            return false;
        }
        return true;
    }
</script>

<?php include 'includes/footer.php'; ?>