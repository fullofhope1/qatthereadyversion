<?php
require 'config/db.php';
include 'includes/header.php';
?>

<?php
$sub_role = $_SESSION['sub_role'] ?? 'full';
$is_full_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' && $sub_role === 'full');
?>

<div class="row mb-4">
    <div class="col-12 text-center py-4">
        <h1 class="fw-black display-5 mb-2 animate__animated animate__fadeInDown">
            <i class="fas fa-cog text-warning me-2"></i> الإعدادات
        </h1>
        <p class="text-secondary lead">إدارة حسابك <?php if ($is_full_admin) echo "وصلاحيات المستخدمين"; ?></p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_GET['success'] === 'password' ? 'تم تحديث كلمة المرور بنجاح!' : 'تم تحديث البيانات بنجاح!' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                خطأ: <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-dark p-0 border-0">
                <ul class="nav nav-pills nav-fill" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-0 text-white py-3 fw-bold" id="account-tab" data-bs-toggle="pill" data-bs-target="#account" type="button" role="tab" style="background-color: transparent;">
                            <i class="fas fa-user-shield me-2"></i> إعدادات الحساب
                        </button>
                    </li>
                    <?php if ($is_full_admin): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-0 text-white py-3 fw-bold" id="users-tab" data-bs-toggle="pill" data-bs-target="#users" type="button" role="tab" style="background-color: transparent;">
                                <i class="fas fa-users-cog me-2"></i> إدارة المستخدمين
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card-body p-4 bg-light">
                <div class="tab-content" id="settingsTabsContent">

                    <!-- Tab 1: Account Settings -->
                    <div class="tab-pane fade show active" id="account" role="tabpanel">
                        <div class="row justify-content-center">
                            <div class="col-md-10">

                                <div class="card shadow-sm border-0 rounded-4 mb-4">
                                    <div class="card-body p-4">
                                        <h5 class="mb-4 text-warning fw-bold"><i class="fas fa-key me-2"></i> تغيير كلمة المرور</h5>
                                        <form action="requests/process_settings.php" method="POST">
                                            <input type="hidden" name="action" value="password">

                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">كلمة المرور الحالية</label>
                                                <input type="password" name="current_password" class="form-control rounded-pill bg-light" required>
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label class="form-label text-secondary small fw-bold">كلمة المرور الجديدة</label>
                                                    <input type="password" name="new_password" class="form-control rounded-pill bg-light" required minlength="4">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-secondary small fw-bold">تأكيد كلمة المرور الجديدة</label>
                                                    <input type="password" name="confirm_password" class="form-control rounded-pill bg-light" required minlength="4">
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">حفظ التغييرات <i class="fas fa-save ms-1"></i></button>
                                        </form>
                                    </div>
                                </div>

                                <div class="card shadow-sm border-0 rounded-4">
                                    <div class="card-body p-4">
                                        <h5 class="mb-4 text-primary fw-bold"><i class="fas fa-id-badge me-2"></i> تغيير اسم المستخدم</h5>
                                        <form action="requests/process_settings.php" method="POST">
                                            <input type="hidden" name="action" value="username">

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label text-secondary small fw-bold">اسم المستخدم الحالي</label>
                                                    <input type="text" class="form-control rounded-pill bg-light text-muted" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" disabled>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-secondary small fw-bold">اسم المستخدم الجديد</label>
                                                    <input type="text" name="new_username" class="form-control rounded-pill bg-light" required minlength="3">
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label text-secondary small fw-bold">تأكيد بكلمة المرور</label>
                                                <input type="password" name="confirm_password_username" class="form-control rounded-pill bg-light" required>
                                            </div>

                                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">تغيير الاسم <i class="fas fa-user-edit ms-1"></i></button>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: User Management (Super Admin Only) -->
                    <?php if ($is_full_admin): ?>
                        <div class="tab-pane fade" id="users" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                <h4 class="text-dark fw-bold mb-0"><i class="fas fa-users text-primary me-2"></i> قائمة مستخدمي النظام</h4>
                                <button class="btn btn-success rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-user-plus me-1"></i> إضافة مستخدم جديد
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle border" id="usersTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الاسم الكامل</th>
                                            <th>اسم المستخدم</th>
                                            <th>رقم الهاتف</th>
                                            <th>الرتبة</th>
                                            <th>الصلاحية</th>
                                            <th class="text-center">إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-secondary">جاري تحميل المستخدمين...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Add User Modal -->
                        <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <form id="addUserForm">
                                        <div class="modal-header bg-dark text-white border-0">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 text-success"></i> إضافة مستخدم جديد</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 bg-light">
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">الاسم الكامل</label>
                                                <input type="text" name="display_name" class="form-control rounded-pill bg-white" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">اسم المستخدم (للدخول)</label>
                                                <input type="text" name="username" class="form-control rounded-pill bg-white" required minlength="3">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">رقم الهاتف</label>
                                                <input type="text" name="phone" class="form-control rounded-pill bg-white">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">الرتبة (الصلاحية الرئيسية)</label>
                                                <select name="role_group" id="userRoleSelect" class="form-select rounded-pill bg-white" required>
                                                    <option value="" disabled selected>اختر الصلاحية</option>
                                                    <option value="super_admin_full">مدير عام (تحكم كامل)</option>
                                                    <option value="super_admin_verifier">مستلم / مراجع (شراء الموردين وجرد البضاعة)</option>
                                                    <option value="super_admin_seller">بائع (مبيعات، ديون، مرتجعات، موظفين...)</option>
                                                    <option value="super_admin_accountant">محاسب (سندات الواتساب والتقارير)</option>
                                                    <option value="super_admin_partner">شريك (تقارير فقط)</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">كلمة المرور</label>
                                                <input type="password" name="password" class="form-control rounded-pill bg-white" required minlength="4">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 p-3 bg-light justify-content-between">
                                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">حفظ المستخدم <i class="fas fa-check ms-1"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <form id="editUserForm">
                                        <input type="hidden" name="user_id" id="editUserId">
                                        <div class="modal-header bg-dark text-white border-0">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-warning"></i> تعديل مستخدم</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 bg-light">
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">الاسم الكامل</label>
                                                <input type="text" name="display_name" id="editDisplayName" class="form-control rounded-pill bg-white" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">رقم الهاتف</label>
                                                <input type="text" name="phone" id="editPhone" class="form-control rounded-pill bg-white">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">الرتبة (الصلاحية الرئيسية)</label>
                                                <select name="role_group" id="editRoleSelect" class="form-select rounded-pill bg-white" required>
                                                    <option value="super_admin_full">مدير عام (تحكم كامل)</option>
                                                    <option value="super_admin_verifier">مستلم / مراجع (شراء الموردين وجرد البضاعة)</option>
                                                    <option value="super_admin_seller">بائع (مبيعات، ديون، مرتجعات، موظفين...)</option>
                                                    <option value="super_admin_accountant">محاسب (سندات الواتساب والتقارير)</option>
                                                    <option value="super_admin_partner">شريك (تقارير فقط)</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-secondary small fw-bold">تغيير كلمة المرور (اختياري)</label>
                                                <input type="password" name="password" class="form-control rounded-pill bg-white" placeholder="اتركه فارغاً إذا لم ترد التغيير" minlength="4">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 p-3 bg-light justify-content-between">
                                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">تحديث <i class="fas fa-sync-alt ms-1"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling for the nav pills inside the dark header */
    .nav-pills .nav-link {
        color: rgba(255, 255, 255, 0.7) !important;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link:hover {
        color: white !important;
        background-color: rgba(255, 255, 255, 0.1) !important;
    }

    .nav-pills .nav-link.active {
        color: #ffc107 !important;
        border-bottom-color: #ffc107;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
</style>

<?php if ($is_full_admin): ?>
    <script>
        function loadUsers() {
            console.log('loadUsers called');
            fetch('requests/manage_users.php?action=list')
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Server returned invalid data format. Check console.');
                    }
                })
                .then(data => {
                    console.log('Data received:', data);
                    const tbody = document.querySelector('#usersTable tbody');
                    if (!data.success) {
                        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">خطأ في جلب البيانات: ${data.error}</td></tr>`;
                        return;
                    }

                    const users = data.data;
                    if (users.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-secondary py-4">لا يوجد مستخدمون.</td></tr>`;
                        return;
                    }

                    let html = '';
                    users.forEach(user => {
                        let roleBadge = '';
                        let subRoleLabel = '';

                        if (user.role === 'super_admin' && user.sub_role === 'full') {
                            roleBadge = '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-crown me-1"></i> مدير عام</span>';
                            subRoleLabel = 'تحكم كامل';
                        } else if (user.role === 'super_admin' && user.sub_role === 'verifier') {
                            roleBadge = '<span class="badge bg-primary rounded-pill px-3 py-2"><i class="fas fa-truck-loading me-1"></i> مستلم / مراجع</span>';
                            subRoleLabel = 'استلام القطار';
                        } else if (user.role === 'super_admin' && user.sub_role === 'seller') {
                            roleBadge = '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-shopping-cart me-1"></i> بائع</span>';
                            subRoleLabel = 'مبيعات شاملة';
                        } else if (user.role === 'super_admin' && user.sub_role === 'accountant') {
                            roleBadge = '<span class="badge bg-info rounded-pill px-3 py-2 text-dark"><i class="fas fa-calculator me-1"></i> محاسب</span>';
                            subRoleLabel = 'كشوفات وتقارير';
                        } else if (user.role === 'super_admin' && user.sub_role === 'partner') {
                            roleBadge = '<span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-handshake me-1"></i> شريك</span>';
                            subRoleLabel = 'تقارير فقط';
                        } else {
                            roleBadge = `<span class="badge bg-secondary rounded-pill px-3 py-2">${user.role}</span>`;
                            subRoleLabel = user.sub_role;
                        }

                        // Protect current user from self-edit/delete, and protect the core admin role
                        let actions = '';
                        if (user.username !== '<?= addslashes($_SESSION['username']) ?>') {
                            if (user.role === 'admin') {
                                actions = '<span class="text-muted small"><i class="fas fa-lock me-1"></i> حساب أساسي</span>';
                            } else {
                                // Reconstruct role_group for edit modal
                                let roleGroup = user.role === 'super_admin' ? `super_admin_${user.sub_role}` : 'user_full';
                                actions = `
                                <button class="btn btn-sm btn-outline-warning rounded-circle me-1 edit-usr-btn" data-id="${user.id}" data-name="${user.display_name}" data-phone="${user.phone}" data-role="${roleGroup}" title="تعديل">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-circle del-usr-btn" data-id="${user.id}" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                            }
                        } else {
                            actions = '<span class="text-muted small">أنت</span>';
                        }

                        html += `
                        <tr>
                            <td class="fw-bold">${user.display_name}</td>
                            <td><span class="text-primary">${user.username}</span></td>
                            <td><span dir="ltr">${user.phone || '-'}</span></td>
                            <td>${roleBadge}</td>
                            <td class="small text-secondary fw-bold">${subRoleLabel}</td>
                            <td class="text-center">${actions}</td>
                        </tr>
                    `;
                    });
                    tbody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    document.querySelector('#usersTable tbody').innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">حدث خطأ في الاتصال: ${error.message}</td></tr>`;
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Load users if we click the tab, or if it's open
            const usersTabEl = document.getElementById('users-tab');
            if (usersTabEl) {
                usersTabEl.addEventListener('shown.bs.tab', loadUsers);
                // Load initially just in case
                loadUsers();
            }

            // Add User Form
            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add');

                fetch('requests/manage_users.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert('تمت الإضافة بنجاح');
                            this.reset();
                            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                            loadUsers();
                        } else {
                            alert('خطأ: ' + data.error);
                        }
                    });
            });

            // Edit User Form
            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'edit');

                fetch('requests/manage_users.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert('تم التحديث بنجاح');
                            this.reset();
                            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                            loadUsers();
                        } else {
                            alert('خطأ: ' + data.error);
                        }
                    });
            });

            // Delegate edit/delete clicks
            document.querySelector('#usersTable').addEventListener('click', function(e) {
                const editBtn = e.target.closest('.edit-usr-btn');
                const delBtn = e.target.closest('.del-usr-btn');

                if (editBtn) {
                    document.getElementById('editUserId').value = editBtn.dataset.id;
                    document.getElementById('editDisplayName').value = editBtn.dataset.name;
                    document.getElementById('editPhone').value = editBtn.dataset.phone;
                    document.getElementById('editRoleSelect').value = editBtn.dataset.role;
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                }

                if (delBtn) {
                    if (confirm('هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.')) {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('user_id', delBtn.dataset.id);
                        fetch('requests/manage_users.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    loadUsers();
                                } else {
                                    alert('خطأ: ' + data.error);
                                }
                            });
                    }
                }
            });
        });
    </script>
<?php endif; ?>