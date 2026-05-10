<?php
// purchases.php (Super Admin / Shop Admin)
require 'config/db.php';
include 'includes/header.php';

// Fetch Types via Clean Architecture
$productRepo = new ProductRepository($pdo);
$types = $productRepo->getAllActive();

// Fetch Shipments via Clean Architecture
$purchaseRepo = new PurchaseRepository($pdo);
$purchaseService = new PurchaseService($purchaseRepo, $productRepo);

$pending_shipments = $purchaseService->getPending();

$today = getOperationalDate();
$received_purchases = $purchaseService->getReceivedToday($today);
?>

<div class="row">
    <!-- Pending Shipments (Receiving Area) -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">شحنات واصلة - في الانتظار</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pending_shipments)): ?>
                    <p class="text-muted text-center">لا توجد شحنات في الانتظار.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>الميديا</th>
                                    <th>التاريخ</th>
                                    <th>المورد</th>
                                    <th>النوع</th>
                                    <th>الوزن المرسل</th>
                                    <th>إجمالي التكلفة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_shipments as $p): ?>
                                    <tr>
                                        <td>
                                            <?php if ($p['media_path']): ?>
                                                <img src="<?= htmlspecialchars($p['media_path']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                                    onclick="window.open('<?= htmlspecialchars($p['media_path']) ?>', '_blank')" role="button">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 4px;">
                                                    <i class="fas fa-image text-muted opacity-50"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $p['purchase_date'] ?></td>
                                        <td><?= htmlspecialchars($p['provider_name']) ?></td>
                                        <td><?= htmlspecialchars($p['type_name']) ?></td>
                                        <td class="fw-bold text-primary">
                                            <?php if ($p['unit_type'] === 'weight'): ?>
                                                <?= number_format($p['source_weight_grams'] / 1000, 3) ?> كجم
                                            <?php else: ?>
                                                <?= number_format($p['source_units']) ?> (<?= $p['unit_type'] ?>)
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($p['agreed_price']) ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm"
                                                onclick="openReceiveModal(<?= $p['id'] ?>, '<?= $p['provider_name'] ?>', '<?= $p['type_name'] ?>', '<?= $p['unit_type'] ?>', <?= $p['source_weight_grams'] ?>, <?= $p['source_units'] ?>)">
                                                استلام وتحقق
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Received History -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">تم استلامها اليوم</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>وقت الاستلام</th>
                                <th>المورد</th>
                                <th>النوع</th>
                                <th>المرسل</th>
                                <th>المستلم</th>
                                <th>الفرق</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($received_purchases as $p): ?>
                                <?php
                                if ($p['unit_type'] === 'weight') {
                                    $diff = $p['received_weight_grams'] - $p['source_weight_grams'];
                                    $diff_kg = $diff / 1000;
                                    $diff_class = $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : 'text-muted');
                                    $diff_sign = $diff > 0 ? '+' : '';

                                    $source_display = number_format($p['source_weight_grams'] / 1000, 3) . ' كجم';
                                    $received_display = number_format($p['received_weight_grams'] / 1000, 3) . ' كجم';
                                    $diff_display = $diff_sign . number_format($diff_kg, 3) . ' كجم';
                                } else {
                                    $diff = $p['received_units'] - $p['source_units'];
                                    $diff_class = $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : 'text-muted');
                                    $diff_sign = $diff > 0 ? '+' : '';

                                    $source_display = number_format($p['source_units']) . ' (' . $p['unit_type'] . ')';
                                    $received_display = number_format($p['received_units']) . ' (' . $p['unit_type'] . ')';
                                    $diff_display = $diff_sign . number_format($diff) . ' (' . $p['unit_type'] . ')';
                                }
                                ?>
                                <tr>
                                    <td><?= date('H:i', strtotime($p['received_at'])) ?></td>
                                    <td><?= htmlspecialchars($p['provider_name']) ?></td>
                                    <td><?= htmlspecialchars($p['type_name']) ?></td>
                                    <td><?= $source_display ?></td>
                                    <td class="fw-bold"><?= $received_display ?></td>
                                    <td class="<?= $diff_class ?>">
                                        <?= $diff_display ?>
                                    </td>
                                    <td class="text-primary fw-bold">
                                        <?php 
                                            $rem = $purchaseRepo->getRemainingStock($p['id']);
                                            echo ($p['unit_type'] === 'weight') ? number_format($rem['kg'], 3) . ' كجم' : number_format($rem['units']) . ' وحدة';
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">تم التخزين</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-danger btn-sm" onclick="openWasteModal(<?= $p['id'] ?>, '<?= addslashes($p['type_name']) ?>', '<?= $p['unit_type'] ?>', <?= ($p['unit_type'] === 'weight' ? $rem['kg'] : $rem['units']) ?>)">
                                            <i class="fas fa-trash-alt"></i> إتلاف
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="requests/process_receiving.php" method="POST">
                <input type="hidden" name="purchase_id" id="receive_purchase_id">
                <div class="modal-header">
                    <h5 class="modal-title">استلام شحنة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>المورد:</strong> <span id="receive_provider"></span></p>
                    <p><strong>النوع:</strong> <span id="receive_type"></span></p>
                    <p><strong>المرسل:</strong> <span id="receive_sent_amount" class="text-primary fw-bold"></span> <span id="receive_sent_unit"></span></p>
                    <input type="hidden" id="sent_weight_grams_val">
                    <input type="hidden" id="sent_units_val">
                    <input type="hidden" id="receive_unit_type" name="unit_type">

                    <hr>

                    <div class="mb-3" id="weight_receive_group">
                        <label class="form-label">الوزن المستلم</label>
                        <div class="row">
                            <div class="col-6">
                                <label class="small text-muted">كيلو</label>
                                <input type="number" step="0.001" class="form-control" id="receive_kg">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted">جرام</label>
                                <input type="number" step="1" class="form-control" id="receive_grams" name="received_weight_grams" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="count_receive_group">
                        <label class="form-label">العدد المستلم</label>
                        <input type="number" step="1" class="form-control" id="receive_units" name="received_units" value="0">
                    </div>

                    <div class="alert text-center fw-bold" id="diff_display">
                        الفرق: 0.000
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">تأكيد الاستلام</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let sentWeightGrams = 0;
    let sentUnits = 0;
    let currentUnitType = 'weight';

    function openReceiveModal(id, provider, type, unit_type, sentGrams, sent_units) {
        document.getElementById('receive_purchase_id').value = id;
        document.getElementById('receive_provider').textContent = provider;
        document.getElementById('receive_type').textContent = type;

        currentUnitType = unit_type;
        document.getElementById('receive_unit_type').value = unit_type;

        if (unit_type === 'weight') {
            document.getElementById('receive_sent_amount').textContent = (sentGrams / 1000).toFixed(3);
            document.getElementById('receive_sent_unit').textContent = 'كجم';
            sentWeightGrams = sentGrams;
            document.getElementById('sent_weight_grams_val').value = sentGrams;

            document.getElementById('weight_receive_group').classList.remove('d-none');
            document.getElementById('count_receive_group').classList.add('d-none');

            document.getElementById('receive_kg').required = true;
            document.getElementById('receive_grams').required = true;
            document.getElementById('receive_units').required = false;
        } else {
            document.getElementById('receive_sent_amount').textContent = sent_units;
            document.getElementById('receive_sent_unit').textContent = '(' + unit_type + ')';
            sentUnits = sent_units;
            document.getElementById('sent_units_val').value = sent_units;

            document.getElementById('weight_receive_group').classList.add('d-none');
            document.getElementById('count_receive_group').classList.remove('d-none');

            document.getElementById('receive_kg').required = false;
            document.getElementById('receive_grams').required = false;
            document.getElementById('receive_units').required = true;
        }

        // Clear inputs
        document.getElementById('receive_kg').value = '';
        document.getElementById('receive_grams').value = '';
        document.getElementById('receive_units').value = '';
        document.getElementById('diff_display').textContent = 'الفرق: 0.000';
        document.getElementById('diff_display').className = 'alert text-center fw-bold alert-secondary';

        new bootstrap.Modal(document.getElementById('receiveModal')).show();
    }

    const rKg = document.getElementById('receive_kg');
    const rGrams = document.getElementById('receive_grams');
    const rUnits = document.getElementById('receive_units');
    const diffDisplay = document.getElementById('diff_display');

    rKg.addEventListener('input', function() {
        if (this.value) {
            rGrams.value = Math.round(parseFloat(this.value) * 1000);
            updateDiff();
        }
    });

    rGrams.addEventListener('input', function() {
        if (this.value) {
            rKg.value = (parseFloat(this.value) / 1000).toFixed(3);
            updateDiff();
        }
    });

    rUnits.addEventListener('input', updateDiff);

    function updateDiff() {
        if (currentUnitType === 'weight') {
            const received = parseFloat(rGrams.value) || 0;
            const diff = received - sentWeightGrams;
            const diffKg = diff / 1000;
            const sign = diff > 0 ? '+' : '';

            diffDisplay.textContent = `الفرق: ${sign}${diffKg.toFixed(3)} كجم`;

            if (diff < 0) {
                diffDisplay.className = 'alert text-center fw-bold alert-danger';
            } else if (diff > 0) {
                diffDisplay.className = 'alert text-center fw-bold alert-success';
            } else {
                diffDisplay.className = 'alert text-center fw-bold alert-secondary';
            }
        } else {
            const received = parseInt(rUnits.value) || 0;
            const diff = received - sentUnits;
            const sign = diff > 0 ? '+' : '';

            diffDisplay.textContent = `الفرق: ${sign}${diff} (${currentUnitType})`;

            if (diff < 0) {
                diffDisplay.className = 'alert text-center fw-bold alert-danger';
            } else if (diff > 0) {
                diffDisplay.className = 'alert text-center fw-bold alert-success';
            } else {
                diffDisplay.className = 'alert text-center fw-bold alert-secondary';
            }
        }
    }
</script>

<!-- Manual Waste Modal -->
<div class="modal fade" id="wasteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i> تسجيل إتلاف / فاقد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2" id="wasteInfo"></div>
                <form id="wasteForm">
                    <input type="hidden" id="w_pid" name="purchase_id">
                    <input type="hidden" id="w_utype" name="unit_type">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">الكمية المراد إتلافها</label>
                        <div class="input-group input-group-lg">
                            <input type="number" step="0.001" class="form-control text-center" id="w_amount" name="amount" required>
                            <span class="input-group-text" id="w_unit_label"></span>
                        </div>
                        <div class="form-text text-danger" id="w_max_error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">السبب</label>
                        <select class="form-select" name="reason" id="w_reason">
                            <option value="Dropped">بضاعة تالفة / خربت</option>
                            <option value="Staff_Consumption">تخزينة عمال (مجاني)</option>
                            <option value="Other">سبب آخر</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات إضافية</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="اختياري..."></textarea>
                    </div>

                    <button type="button" class="btn btn-danger w-100 py-3 fw-bold shadow-sm" onclick="submitWaste()">
                        <i class="fas fa-check-circle me-1"></i> تأكيد الإتلاف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const wasteModal = new bootstrap.Modal(document.getElementById('wasteModal'));
    let maxAvailable = 0;

    function openWasteModal(pid, name, unitType, available) {
        document.getElementById('w_pid').value = pid;
        document.getElementById('w_utype').value = unitType;
        document.getElementById('wasteInfo').innerHTML = `إتلاف من شحنة: <b>${name}</b><br>المتوفر حالياً: <b>${available} ${unitType === 'weight' ? 'كجم' : 'وحدة'}</b>`;
        document.getElementById('w_unit_label').textContent = unitType === 'weight' ? 'كجم' : 'وحدة';
        document.getElementById('w_amount').value = '';
        document.getElementById('w_max_error').textContent = '';
        maxAvailable = available;
        wasteModal.show();
    }

    function submitWaste() {
        const amount = parseFloat(document.getElementById('w_amount').value) || 0;
        if (amount <= 0) return alert('يرجى إدخال كمية صحيحة');
        if (amount > maxAvailable) {
            document.getElementById('w_max_error').textContent = 'الكمية المدخلة أكبر من المتوفر!';
            return;
        }

        const formData = new FormData(document.getElementById('wasteForm'));
        fetch('requests/manual_waste.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('خطأ: ' + data.error);
            }
        });
    }
</script>

<!-- Quick Report Link -->
<div class="text-center mt-4 mb-5 no-print">
    <a href="reports.php?report_type=Daily" class="btn btn-outline-secondary">
        <i class="fas fa-file-invoice me-2"></i> تقرير اليوم المفصل
    </a>
</div>

<?php include 'includes/footer.php'; ?>