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
                <ul class="nav nav-pills nav-fill" id="settingsTabs">
                    <li class="nav-item">
                        <button class="nav-link active rounded-0 text-white py-3 fw-bold" id="account-tab" onclick="switchTab('account')" type="button">
                            <i class="fas fa-user-shield me-2"></i> إعدادات الحساب
                        </button>
                    </li>
                    <?php if ($is_full_admin): ?>
                        <li class="nav-item">
                            <button class="nav-link rounded-0 text-white py-3 fw-bold" id="users-tab" onclick="switchTab('users')" type="button">
                                <i class="fas fa-users-cog me-2"></i> إدارة المستخدمين
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-0 text-white py-3 fw-bold" id="system-tab" onclick="switchTab('system')" type="button">
                                <i class="fas fa-database me-2"></i> إدارة النظام
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card-body p-4 bg-light">
                <div class="tab-content" id="settingsTabsContent">

                    <!-- Tab 1: Account Settings -->
                    <div id="account" class="tab-pane-custom" style="display:block;">
                        <div class="row justify-content-center">
                            <div class="col-md-10">

                                <div class="card shadow-sm border-0 rounded-4 mb-4">
                                    <div class="card-body p-4">
                                        <h5 class="mb-4 text-warning fw-bold"><i class="fas fa-key me-2"></i> تغيير كلمة المرور</h5>
                                        <form action="requests/process_settings.php" method="POST">
                                            <input type="hidden" name="action" value="password">

                                            <div class="mb-3">
                                                <label for="current_password" class="form-label text-secondary small fw-bold">كلمة المرور الحالية</label>
                                                <input type="password" id="current_password" name="current_password" class="form-control rounded-pill bg-light" required autocomplete="new-password" value="">
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label for="new_password" class="form-label text-secondary small fw-bold">كلمة المرور الجديدة</label>
                                                    <input type="password" id="new_password" name="new_password" class="form-control rounded-pill bg-light" required minlength="4" autocomplete="new-password" value="">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="confirm_password" class="form-label text-secondary small fw-bold">تأكيد كلمة المرور الجديدة</label>
                                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control rounded-pill bg-light" required minlength="4" autocomplete="new-password" value="">
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
                                                    <label for="current_username_display" class="form-label text-secondary small fw-bold">اسم المستخدم الحالي</label>
                                                    <input type="text" id="current_username_display" class="form-control rounded-pill bg-light text-muted" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" disabled>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="new_username" class="form-label text-secondary small fw-bold">اسم المستخدم الجديد</label>
                                                    <input type="text" id="new_username" name="new_username" class="form-control rounded-pill bg-light" required minlength="3">
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <label for="confirm_password_username" class="form-label text-secondary small fw-bold">تأكيد بكلمة المرور</label>
                                                <input type="password" id="confirm_password_username" name="confirm_password_username" class="form-control rounded-pill bg-light" required autocomplete="new-password" value="">
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
                        <div id="users" class="tab-pane-custom" style="display:none;">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 border-bottom pb-3 gap-3">
                                <h4 class="text-dark fw-bold mb-0"><i class="fas fa-users text-primary me-2"></i> قائمة مستخدمي النظام</h4>
                                <div class="flex-grow-1 mx-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" id="userSearchInput" class="form-control border-start-0 rounded-end-pill" placeholder="بحث عن مستخدم (الاسم، الجوال، اليوزر)...">
                                    </div>
                                </div>
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

                        <!-- Tab 3: System Management (Super Admin Only) -->
                        <div id="system" class="tab-pane-custom" style="display:none;">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card shadow-sm border-0 rounded-4 h-100">
                                        <div class="card-body p-4">
                                            <h5 class="mb-4 text-success fw-bold"><i class="fas fa-cloud-upload-alt me-2"></i> النسخ الاحتياطي</h5>
                                            <p class="text-secondary small mb-4">توليد نسخة كاملة من قاعدة البيانات وإرسالها إلى البريد الإلكتروني <span class="badge bg-light text-dark">aiaiaiaihelp@gmail.com</span>.</p>

                                            <div id="backupStatus"></div>

                                            <button type="button" id="btnBackup" onclick="runBackup()" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm w-100 py-3">
                                                <i class="fas fa-share-alt me-2"></i> إنشاء والرفع للدرايف (مشاركة)
                                            </button>
                                            <div class="form-text mt-3 text-center"><i class="fas fa-info-circle me-1"></i> (من الجوال: سيفتح قائمة المشاركة للرفع للدرايف)</div>

                                            <hr class="my-4">
                                            <h6 class="fw-bold mb-3"><i class="fas fa-history me-2"></i> النسخ السابقة</h6>
                                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                                <table class="table table-sm table-hover small" id="backupsTable">
                                                    <thead class="table-light sticky-top">
                                                        <tr>
                                                            <th>الملف</th>
                                                            <th class="text-center">إجراءات</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="2" class="text-center py-3 text-muted">جاري تحميل النسخ...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card shadow-sm border-0 rounded-4 h-100">
                                        <div class="card-body p-4">
                                            <h5 class="mb-4 text-danger fw-bold"><i class="fas fa-cloud-download-alt me-2"></i> استعادة البيانات</h5>
                                            <p class="text-secondary small mb-4">رفع ملف <span class="badge bg-light text-dark">.sql</span> لاستعادة البيانات. <span class="text-danger fw-bold">تحذير:</span> سيتم استبدال البيانات الحالية بالكامل.</p>

                                            <form id="importForm" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <input type="file" name="sql_file" id="sqlFile" class="form-control rounded-pill bg-light" accept=".sql" required>
                                                </div>
                                                <button type="submit" id="btnImport" class="btn btn-outline-danger rounded-pill px-4 fw-bold w-100 py-3">
                                                    <i class="fas fa-upload me-2"></i> بدء عملية الاستيراد
                                                </button>
                                            </form>
                                            <div id="importStatus" class="mt-3"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($is_full_admin): ?>
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form id="addUserForm">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 text-success"></i> إضافة مستخدم جديد</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="mb-3">
                            <label for="add_display_name" class="form-label text-secondary small fw-bold">الاسم الكامل</label>
                            <input type="text" id="add_display_name" name="display_name" class="form-control rounded-pill bg-white" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_username" class="form-label text-secondary small fw-bold">اسم المستخدم (للدخول)</label>
                            <input type="text" id="add_username" name="username" class="form-control rounded-pill bg-white" required minlength="3">
                        </div>
                        <div class="mb-3">
                            <label for="add_phone" class="form-label text-secondary small fw-bold">رقم الهاتف</label>
                            <input type="text" id="add_phone" name="phone" class="form-control rounded-pill bg-white">
                        </div>

                        <div class="mb-3">
                            <label for="userRoleSelect" class="form-label text-secondary small fw-bold">الرتبة (الصلاحية الرئيسية)</label>
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
                            <label for="addPassword" class="form-label text-secondary small fw-bold">كلمة المرور</label>
                            <input type="password" name="password" id="addPassword" class="form-control rounded-pill bg-white" required minlength="4" autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label for="addConfirmPassword" class="form-label text-secondary small fw-bold">تأكيد كلمة المرور</label>
                            <input type="password" name="confirm_password" id="addConfirmPassword" class="form-control rounded-pill bg-white" required minlength="4" autocomplete="new-password">
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
    <div class="modal fade" id="editUserModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
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
                            <label for="editDisplayName" class="form-label text-secondary small fw-bold">الاسم الكامل</label>
                            <input type="text" name="display_name" id="editDisplayName" class="form-control rounded-pill bg-white" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label text-secondary small fw-bold">رقم الهاتف</label>
                            <input type="text" name="phone" id="editPhone" class="form-control rounded-pill bg-white">
                        </div>
                        <div class="mb-3">
                            <label for="editRoleSelect" class="form-label text-secondary small fw-bold">الرتبة (الصلاحية الرئيسية)</label>
                            <select name="role_group" id="editRoleSelect" class="form-select rounded-pill bg-white" required>
                                <option value="super_admin_full">مدير عام (تحكم كامل)</option>
                                <option value="super_admin_verifier">مستلم / مراجع (شراء الموردين وجرد البضاعة)</option>
                                <option value="super_admin_seller">بائع (مبيعات، ديون، مرتجعات، موظفين...)</option>
                                <option value="super_admin_accountant">محاسب (سندات الواتساب والتقارير)</option>
                                <option value="super_admin_partner">شريك (تقارير فقط)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label text-secondary small fw-bold">تغيير كلمة المرور (اختياري)</label>
                            <input type="password" id="editPassword" name="password" class="form-control rounded-pill bg-white" placeholder="اتركه فارغاً إذا لم ترد التغيير" minlength="4" autocomplete="new-password">
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
            fetch('requests/manage_users.php?action=list')
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('فشل في قراءة بيانات المستخدمين من الخادم.');
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

        var usersListenersAttached = false;

        function switchTab(target) {
            // Hide all panes
            document.querySelectorAll('.tab-pane-custom').forEach(function(pane) {
                pane.style.display = 'none';
            });
            // Remove active from all tab buttons
            document.querySelectorAll('#settingsTabs button').forEach(function(btn) {
                btn.classList.remove('active');
            });
            // Show the selected pane
            var pane = document.getElementById(target);
            if (pane) pane.style.display = 'block';
            // Mark selected button as active
            var btn = document.getElementById(target + '-tab');
            if (btn) btn.classList.add('active');
            // Load users if switching to users tab
            if (target === 'users') {
                loadUsers();
                attachUsersListeners();
            }
        }

        async function runBackup() {
            const btn = document.getElementById('btnBackup');
            const status = document.getElementById('backupStatus');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري التحضير...';
            status.innerHTML = '';

            try {
                const response = await fetch('requests/backup_db.php');
                const data = await response.json();
                
                if (data.success) {
                    status.innerHTML = `<div class="alert alert-success small">${data.message}</div>`;
                    
                    // Web Share API for Mobile
                    if (navigator.share && data.filename) {
                        try {
                            const fileUrl = 'backups/' + data.filename;
                            const fileRes = await fetch(fileUrl);
                            const blob = await fileRes.blob();
                            const file = new File([blob], data.filename, { type: 'application/sql' });
                            
                            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                                await navigator.share({
                                    files: [file],
                                    title: 'نسخة احتياطية - القادري وماجد',
                                    text: 'ملف قاعدة البيانات'
                                });
                            }
                        } catch (shareErr) {
                            console.log('Sharing failed', shareErr);
                        }
                    }
                } else {
                    status.innerHTML = `<div class="alert alert-danger small">${data.message}</div>`;
                }
            } catch (e) {
                status.innerHTML = `<div class="alert alert-danger small">خطأ في الاتصال: ${e.message}</div>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-share-alt me-2"></i> إنشاء والرفع للدرايف (مشاركة)';
                loadBackups();
            }
        }

        function loadBackups() {
            const tbody = document.querySelector('#backupsTable tbody');
            fetch('requests/backup_db.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.files.length > 0) {
                        let html = '';
                        data.files.forEach(f => {
                            html += `
                                <tr>
                                    <td class="align-middle">${f}</td>
                                    <td class="text-center">
                                        <a href="backups/${f}" download class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0" title="تحميل">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted">لا توجد نسخ حالياً.</td></tr>';
                    }
                })
                .catch(e => {
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-danger">خطأ في التحميل.</td></tr>';
                });
        }

        function attachUsersListeners() {
            if (usersListenersAttached) return;
            usersListenersAttached = true;

            // Edit/delete button delegation
            var usersTable = document.querySelector('#usersTable');
            if (usersTable) {
                usersTable.addEventListener('click', function(e) {
                    var editBtn = e.target.closest('.edit-usr-btn');
                    var delBtn = e.target.closest('.del-usr-btn');
                    if (editBtn) {
                        try {
                            document.getElementById('editUserId').value = editBtn.dataset.id;
                            document.getElementById('editDisplayName').value = editBtn.dataset.name;
                            document.getElementById('editPhone').value = editBtn.dataset.phone;
                            document.getElementById('editRoleSelect').value = editBtn.dataset.role;
                            var editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal'));
                            editModal.show();
                        } catch (err) {
                            console.error('Edit modal error:', err);
                            alert('خطأ في فتح نافذة التعديل: ' + err.message);
                        }
                    }
                    if (delBtn) {
                        if (confirm('هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.')) {
                            var formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('user_id', delBtn.dataset.id);
                            fetch('requests/manage_users.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(function(r) {
                                    return r.json();
                                })
                                .then(function(data) {
                                    if (data.success) {
                                        loadUsers();
                                    } else {
                                        alert('خطأ: ' + data.error);
                                    }
                                });
                        }
                    }
                });
            }
        }

        function initSettingsWhenReady() {
            if (typeof bootstrap !== 'undefined') {
                // User Search Logic
                var userSearchInput = document.getElementById('userSearchInput');
                if (userSearchInput) {
                    userSearchInput.addEventListener('input', function() {
                        var filter = this.value.toLowerCase();
                        var rows = document.querySelectorAll('#usersTable tbody tr');
                        rows.forEach(function(row) {
                            if (row.cells.length < 5) return; // Skip "Loading..." or "Error" rows
                            var text = row.innerText.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        });
                    });
                }

                // Add User Form submit
                var addUserForm = document.getElementById('addUserForm');

                // Extra security: Clear all password fields on load to fight aggressive browser auto-fill
                setTimeout(function() {
                    document.querySelectorAll('input[type="password"]').forEach(function(input) {
                        input.value = '';
                    });
                }, 500);

                if (addUserForm) addUserForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);

                    var pw = document.getElementById('addPassword').value;
                    var cpw = document.getElementById('addConfirmPassword').value;
                    if (pw !== cpw) {
                        alert('خطأ: كلمات المرور غير متطابقة!');
                        return;
                    }

                    formData.append('action', 'add');
                    fetch('requests/manage_users.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                alert('تمت الإضافة بنجاح');
                                addUserForm.reset();
                                var modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                                if (modal) modal.hide();
                                loadUsers();
                            } else {
                                alert('خطأ: ' + data.error);
                            }
                        });
                });

                // Edit User Form submit
                var editUserForm = document.getElementById('editUserForm');
                if (editUserForm) editUserForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    formData.append('action', 'edit');
                    fetch('requests/manage_users.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                alert('تم التحديث بنجاح');
                                editUserForm.reset();
                                var modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                                if (modal) modal.hide();
                                loadUsers();
                            } else {
                                alert('خطأ: ' + data.error);
                            }
                        });
                });

                // Import Form submit
                var importForm = document.getElementById('importForm');
                if (importForm) importForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!confirm('هل أنت متأكد؟ سيتم استبدال قاعدة البيانات الحالية بالكامل ولا يمكن التراجع عن ذلك.')) return;

                    const btn = document.getElementById('btnImport');
                    const status = document.getElementById('importStatus');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري الاستيراد...';
                    status.innerHTML = '';

                    var formData = new FormData(this);
                    fetch('requests/import_db.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                status.innerHTML = `<div class="alert alert-success small">${data.message}</div>`;
                                setTimeout(() => location.reload(), 2000);
                            } else {
                                status.innerHTML = `<div class="alert alert-danger small">${data.message}</div>`;
                            }
                        })
                        .catch(e => {
                            status.innerHTML = `<div class="alert alert-danger small">خطأ في الاتصال: ${e.message}</div>`;
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-upload me-2"></i> بدء عملية الاستيراد';
                        });
                });
            } else {
                setTimeout(initSettingsWhenReady, 50);
            }
        }

        // Initialize backups list on load
        window.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('backupsTable')) {
                loadBackups();
            }
        });

        window.addEventListener('DOMContentLoaded', initSettingsWhenReady);
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>