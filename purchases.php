<?php
// purchases.php (Super Admin / Shop Admin)
require 'config/db.php';
include 'includes/header.php';

// Fetch Types for Manual Purchase if needed
$types = $pdo->query("SELECT * FROM qat_types WHERE is_deleted = 0")->fetchAll();

// Fetch Pending Shipments (Sent by Field Admin, Not yet Received)
$stmt = $pdo->prepare("
    SELECT p.*, t.name as type_name, prov.name as provider_name 
    FROM purchases p 
    LEFT JOIN qat_types t ON p.qat_type_id = t.id 
    LEFT JOIN providers prov ON p.provider_id = prov.id 
    WHERE p.is_received = 0 
    ORDER BY p.created_at ASC
");
$stmt->execute();
$pending_shipments = $stmt->fetchAll();

// Fetch Today's Received Purchases
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT p.*, t.name as type_name, prov.name as provider_name 
    FROM purchases p 
    LEFT JOIN qat_types t ON p.qat_type_id = t.id 
    LEFT JOIN providers prov ON p.provider_id = prov.id 
    WHERE p.is_received = 1 AND DATE(p.received_at) = ? 
    ORDER BY p.received_at DESC
");
$stmt->execute([$today]);
$received_purchases = $stmt->fetchAll();
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
                                            <?= number_format($p['source_weight_grams'] / 1000, 3) ?> كجم
                                        </td>
                                        <td><?= number_format($p['agreed_price']) ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm"
                                                onclick="openReceiveModal(<?= $p['id'] ?>, '<?= $p['provider_name'] ?>', '<?= $p['type_name'] ?>', <?= $p['source_weight_grams'] ?>)">
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
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($received_purchases as $p): ?>
                                <?php
                                $diff = $p['received_weight_grams'] - $p['source_weight_grams'];
                                $diff_kg = $diff / 1000;
                                $diff_class = $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : 'text-muted');
                                $diff_sign = $diff > 0 ? '+' : '';
                                ?>
                                <tr>
                                    <td><?= date('H:i', strtotime($p['received_at'])) ?></td>
                                    <td><?= htmlspecialchars($p['provider_name']) ?></td>
                                    <td><?= htmlspecialchars($p['type_name']) ?></td>
                                    <td><?= number_format($p['source_weight_grams'] / 1000, 3) ?></td>
                                    <td class="fw-bold"><?= number_format($p['received_weight_grams'] / 1000, 3) ?></td>
                                    <td class="<?= $diff_class ?>">
                                        <?= $diff_sign . number_format($diff_kg, 3) ?> كجم
                                    </td>
                                    <td>
                                        <span class="badge bg-success">تم التخزين</span>
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
                    <p><strong>الوزن المرسل:</strong> <span id="receive_sent_weight" class="text-primary fw-bold"></span> كجم</p>
                    <input type="hidden" id="sent_weight_grams_val">

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Received Weight (الوزن المستلم)</label>
                        <div class="row">
                            <div class="col-6">
                                <label class="small text-muted">كيلو</label>
                                <input type="number" step="0.001" class="form-control" id="receive_kg" required>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted">جرام</label>
                                <input type="number" step="1" class="form-control" id="receive_grams" name="received_weight_grams" required>
                            </div>
                        </div>
                    </div>

                    <div class="alert text-center fw-bold" id="diff_display">
                        الفرق: 0.000 كجم
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

    function openReceiveModal(id, provider, type, sentGrams) {
        document.getElementById('receive_purchase_id').value = id;
        document.getElementById('receive_provider').textContent = provider;
        document.getElementById('receive_type').textContent = type;
        document.getElementById('receive_sent_weight').textContent = (sentGrams / 1000).toFixed(3);

        sentWeightGrams = sentGrams;
        document.getElementById('sent_weight_grams_val').value = sentGrams;

        // Clear inputs
        document.getElementById('receive_kg').value = '';
        document.getElementById('receive_grams').value = '';
        document.getElementById('diff_display').textContent = 'الفرق: 0.000 كجم';
        document.getElementById('diff_display').className = 'alert text-center fw-bold alert-secondary';

        new bootstrap.Modal(document.getElementById('receiveModal')).show();
    }

    const rKg = document.getElementById('receive_kg');
    const rGrams = document.getElementById('receive_grams');
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

    function updateDiff() {
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
    }
</script>

<!-- Quick Report Link -->
<div class="text-center mt-4 mb-5 no-print">
    <a href="reports.php?report_type=Daily" class="btn btn-outline-secondary">
        <i class="fas fa-file-invoice me-2"></i> تقرير اليوم المفصل
    </a>
</div>

<?php include 'includes/footer.php'; ?>