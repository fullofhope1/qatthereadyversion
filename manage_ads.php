<?php
require 'config/db.php';
include 'includes/header.php';

// Fetch Advertisements
$stmt = $pdo->query("SELECT * FROM advertisements ORDER BY created_at DESC");
$ads = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-ad me-2"></i> إدارة الإعلانات</h4>
                <button class="btn btn-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addAdModal">+ إضافة إعلان</button>
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
                                <th>العميل</th>
                                <th>العنوان</th>
                                <th>الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td>
                                        <?php if ($ad['media_path']): ?>
                                            <?php if (str_ends_with(strtolower($ad['media_path']), '.mp4') || str_ends_with(strtolower($ad['media_path']), '.mov')): ?>
                                                <video src="<?= htmlspecialchars($ad['media_path']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;" muted></video>
                                            <?php else: ?>
                                                <img src="<?= htmlspecialchars($ad['media_path']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                            <?php endif; ?>
                                        <?php elseif ($ad['image_url']): ?>
                                            <img src="<?= htmlspecialchars($ad['image_url']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 8px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($ad['client_name']) ?></td>
                                    <td><?= htmlspecialchars($ad['title']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $ad['status'] === 'Active' ? 'success' : 'secondary' ?> rounded-pill">
                                            <?= $ad['status'] === 'Active' ? 'نشط' : 'متوقف' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-outline-warning btn-sm"
                                                onclick="editAd(<?= htmlspecialchars(json_encode($ad)) ?>)">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </button>
                                            <form action="requests/process_ad.php" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟');" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $ad['id'] ?>">
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="adModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalTitle">إضافة إعلان جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/process_ad.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body text-end">
                    <input type="hidden" name="action" id="modalAction" value="add">
                    <input type="hidden" name="id" id="adId">
                    <div class="mb-3">
                        <label class="form-label">اسم العميل</label>
                        <input type="text" name="client_name" id="clientName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <input type="text" name="title" id="adTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" id="adDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوسائط (صورة/فيديو)</label>
                        <input type="file" name="media" id="adMedia" class="form-control" accept="image/*,video/*">
                        <small class="text-muted">ارفع ملفاً أو ضع رابطاً بالأسفل.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رابط الصورة (اختياري)</label>
                        <input type="text" name="image_url" id="imageUrl" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رابط الصفحات الخارجية</label>
                        <input type="text" name="link_url" id="linkUrl" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" id="adStatus" class="form-select">
                            <option value="Active">نشط</option>
                            <option value="Inactive">متوقف</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="saveBtn" class="btn btn-warning fw-bold px-4">إضافة الإعلان</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editAd(ad) {
        document.getElementById('modalTitle').innerText = 'تعديل الإعلان';
        document.getElementById('modalAction').value = 'update';
        document.getElementById('adId').value = ad.id;
        document.getElementById('clientName').value = ad.client_name;
        document.getElementById('adTitle').value = ad.title;
        document.getElementById('adDescription').value = ad.description;
        document.getElementById('imageUrl').value = ad.image_url;
        document.getElementById('linkUrl').value = ad.link_url;
        document.getElementById('adStatus').value = ad.status;
        document.getElementById('saveBtn').innerText = 'حفظ التغييرات';
        document.getElementById('saveBtn').className = 'btn btn-primary fw-bold px-4';

        new bootstrap.Modal(document.getElementById('adModal')).show();
    }

    // Reset modal when clicking "Add Advertisement"
    document.querySelector('[data-bs-target="#addAdModal"]').setAttribute('data-bs-target', '#adModal');
    document.querySelector('[data-bs-target="#adModal"]').onclick = function() {
        document.getElementById('modalTitle').innerText = 'إضافة إعلان جديد';
        document.getElementById('modalAction').value = 'add';
        document.getElementById('adId').value = '';
        document.getElementById('clientName').value = '';
        document.getElementById('adTitle').value = '';
        document.getElementById('adDescription').value = '';
        document.getElementById('imageUrl').value = '';
        document.getElementById('linkUrl').value = '';
        document.getElementById('adStatus').value = 'Active';
        document.getElementById('saveBtn').innerText = 'إضافة الإعلان';
        document.getElementById('saveBtn').className = 'btn btn-warning fw-bold px-4';
    };
</script>

<?php include 'includes/footer.php'; ?>