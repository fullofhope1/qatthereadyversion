<?php
// providers.php - Management page for Providers (الرعية)
require 'config/db.php';
include 'includes/header.php';

// Fetch Providers via Clean Architecture
$providerRepo = new ProviderRepository($pdo);
$providerService = new ProviderService($providerRepo);
$providers = $providerService->listProviders($_SESSION['user_id']);
?>
<style>
    @keyframes highlightRow {
        0% { background-color: #fff3cd; }
        100% { background-color: transparent; }
    }
    .new-row-highlight {
        animation: highlightRow 3s ease-out;
    }
</style>

<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark p-3 rounded-circle me-3">
                        <i class="fas fa-users-cog fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold">إدارة الرعية (الموردين)</h4>
                        <p class="mb-0 small opacity-75">عرض وإدارة بيانات الموردين المسجلين في النظام</p>
                    </div>
                </div>
                <button class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                    <i class="fas fa-plus me-1"></i> إضافة راعي جديد
                </button>
            </div>
            <div class="card-body p-0">
                <!-- Search Box -->
                <div class="p-3 bg-light border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="providerSearch" class="form-control border-start-0" placeholder="بحث باسم الراعي أو رقم الهاتف..." onkeyup="filterProviders()">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase fw-bold">
                            <tr>
                                <th class="px-4 py-3">الاسم</th>
                                <th class="px-4 py-3">رقم الهاتف</th>
                                <th class="px-4 py-3 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="providerTableBody">
                            <?php if (empty($providers)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                                        <p>لا يوجد موردين مسجلين بعد.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($providers as $p): ?>
                                    <tr>
                                        <td class="px-4 py-3 fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></td>
                                        <td class="px-4 py-3 text-secondary"><?= htmlspecialchars($p['phone']) ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                <button class="btn btn-sm btn-outline-warning" onclick='editProvider(<?= json_encode($p) ?>)' title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProvider(<?= $p['id'] ?>)" title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">إضافة راعي جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addProviderForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">الاسم <span class="text-danger">*</span></label>
                        <input type="text" id="new_name" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">الهاتف <span class="text-danger">*</span></label>
                        <input type="tel" id="new_phone" class="form-control rounded-3" required inputmode="numeric" placeholder="7xxxxxxxxx">
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning fw-bold rounded-pill px-4">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Provider Modal -->
<div class="modal fade" id="editProviderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold">تعديل بيانات الراعي</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProviderForm">
                <input type="hidden" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">الاسم <span class="text-danger">*</span></label>
                        <input type="text" id="edit_name" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">الهاتف <span class="text-danger">*</span></label>
                        <input type="tel" id="edit_phone" class="form-control rounded-3" required inputmode="numeric">
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function filterProviders() {
        const term = document.getElementById('providerSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#providerTableBody tr');
        rows.forEach(row => {
            if (row.cells.length < 2) return;
            const text = row.cells[0].innerText.toLowerCase() + " " + row.cells[1].innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    }

    document.getElementById('addProviderForm').onsubmit = function(e) {
        e.preventDefault();
        const name = document.getElementById('new_name').value.trim();
        const phone = document.getElementById('new_phone').value.trim();

        if (!name || !phone) return alert('جميع الحقول مطلوبة');

        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);

        fetch('requests/add_provider.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('providerTableBody');
                    if (tbody.querySelector('.py-5')) tbody.innerHTML = '';

                    const tr = document.createElement('tr');
                    tr.className = 'new-row-highlight';
                    tr.innerHTML = `
                        <td class="px-4 py-3 fw-bold text-dark">${name}</td>
                        <td class="px-4 py-3 text-secondary">${phone}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                <button class="btn btn-sm btn-outline-warning" onclick='editProvider(${JSON.stringify(data.provider)})' title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProvider(${data.provider.id})" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.prepend(tr);

                    const modalEl = document.getElementById('addProviderModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    
                    document.getElementById('addProviderForm').reset();
                    document.getElementById('providerSearch').value = '';
                    filterProviders();
                } else {
                    alert('خطأ: ' + data.message);
                }
            })
            .catch(err => alert('حدث خطأ في الاتصال: ' + err.message));
    };

    function editProvider(p) {
        document.getElementById('edit_id').value = p.id;
        document.getElementById('edit_name').value = p.name;
        document.getElementById('edit_phone').value = p.phone;
        new bootstrap.Modal(document.getElementById('editProviderModal')).show();
    }

    document.getElementById('editProviderForm').onsubmit = function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const name = document.getElementById('edit_name').value.trim();
        const phone = document.getElementById('edit_phone').value.trim();

        const formData = new FormData();
        formData.append('id', id);
        formData.append('name', name);
        formData.append('phone', phone);

        fetch('requests/update_provider.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('خطأ: ' + data.message);
            });
    };

    function deleteProvider(id) {
        if (!confirm('هل أنت متأكد من حذف هذا الراعي؟ قد لا تنجح العملية إذا كان لديه شحنات مسبقة.')) return;

        fetch('requests/delete_provider.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('خطأ: ' + data.message);
            });
    }
</script>

<?php include 'includes/footer.php'; ?>