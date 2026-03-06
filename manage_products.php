<?php
require 'config/db.php';
include 'includes/header.php';

// Fetch Products (Qat Types) - Only those with ACTIVE (non-closed) shipments.
// When the day is closed (manually or automatically), purchase status becomes 'Closed',
// which will automatically clear this list for a fresh start the next day.
$stmt = $pdo->query("
    SELECT qt.*, 
           SUM(p.received_weight_grams) as total_received_grams,
           MAX(p.created_at) as last_shipment_date,
           MAX(p.purchase_date) as active_date
    FROM qat_types qt
    JOIN purchases p ON qt.id = p.qat_type_id
    WHERE qt.is_deleted = 0
      AND p.status IN ('Fresh', 'Momsi')
    GROUP BY qt.id
    ORDER BY qt.name ASC
");
$products = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-leaf me-2"></i> إدارة أنواع القات</h4>
                <button class="btn btn-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addProductModal">+ إضافة نوع</button>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success py-2 small shadow-sm mb-4"><i class="fas fa-check-circle me-1"></i> تم حفظ التغييرات بنجاح!</div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>الوسائط</th>
                                <th>الاسم</th>
                                <th>إجمالي المستلم</th>
                                <th>آخر شحنة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // Primary media from type, fallback to latest shipment media if missing
                                        $displayMedia = $p['media_path'];
                                        ?>
                                        <?php if ($displayMedia): ?>
                                            <div class="position-relative d-inline-block">
                                                <?php if (str_ends_with(strtolower($displayMedia), '.mp4') || str_ends_with(strtolower($displayMedia), '.mov')): ?>
                                                    <video src="<?= htmlspecialchars($displayMedia) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 2px solid #ffc107;" muted></video>
                                                    <div class="position-absolute bottom-0 end-0 bg-dark text-white rounded-circle p-1 m-1" style="font-size: 0.6rem;">
                                                        <i class="fas fa-video"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?= htmlspecialchars($displayMedia) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 2px solid #ffc107;" class="shadow-sm">
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center border" style="width: 80px; height: 80px; border-radius: 12px;">
                                                <i class="fas fa-leaf text-success opacity-50 fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td>
                                        <div class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                            <?= number_format($p['total_received_grams'] / 1000, 3) ?> كجم
                                        </div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('Y-m-d H:i', strtotime($p['last_shipment_date'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-outline-warning btn-sm"
                                                onclick="editProduct(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>', '<?= addslashes(htmlspecialchars($p['description'] ?: '')) ?>')">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </button>
                                            <form action="requests/process_product.php" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا النوع؟');" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash me-1"></i>
                                                </button>
                                            </form>
                                        </div>
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

<!-- Add/Edit Modal (Combined or Separate) -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalTitle">إضافة نوع جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/process_product.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body text-end">
                    <input type="hidden" name="action" id="modalAction" value="add">
                    <input type="hidden" name="id" id="productId">
                    <div class="mb-3">
                        <label class="form-label">اسم النوع</label>
                        <input type="text" name="name" id="productName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" id="productDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوسائط (صورة/فيديو)</label>
                        <input type="file" name="media" id="productMedia" class="form-control" accept="image/*,video/*">
                        <small class="text-muted">ارفع صورة أو فيديو لهذا النوع.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="saveBtn" class="btn btn-warning fw-bold px-4">إضافة النوع</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editProduct(id, name, desc) {
        document.getElementById('modalTitle').innerText = 'تعديل النوع';
        document.getElementById('modalAction').value = 'update';
        document.getElementById('productId').value = id;
        document.getElementById('productName').value = name;
        document.getElementById('productDescription').value = desc;
        document.getElementById('saveBtn').innerText = 'حفظ التغييرات';
        document.getElementById('saveBtn').className = 'btn btn-primary fw-bold px-4';

        new bootstrap.Modal(document.getElementById('productModal')).show();
    }

    // Reset modal when clicking "Add Product"
    document.querySelector('[data-bs-target="#addProductModal"]').setAttribute('data-bs-target', '#productModal');
    document.querySelector('[data-bs-target="#productModal"]').onclick = function() {
        document.getElementById('modalTitle').innerText = 'إضافة نوع جديد';
        document.getElementById('modalAction').value = 'add';
        document.getElementById('productId').value = '';
        document.getElementById('productName').value = '';
        document.getElementById('productDescription').value = '';
        document.getElementById('saveBtn').innerText = 'إضافة النوع';
        document.getElementById('saveBtn').className = 'btn btn-warning fw-bold px-4';
    };
</script>

<?php include 'includes/footer.php'; ?>