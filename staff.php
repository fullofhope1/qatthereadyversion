<?php
require 'config/db.php';
include 'includes/header.php';

// Initialization via Clean Architecture
$staffRepo = new StaffRepository($pdo);
$service = new StaffService($staffRepo);

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? null;
$subRole = $_SESSION['sub_role'] ?? 'full';

$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == 1;
$staffMembers = $service->getStaffList($user_id, $role, $subRole, $showInactive);
$allWithdrawals = $service->getTotalWithdrawals($user_id);
?>

<div class="row mb-4">
    <div class="col-md-12 text-center">
        <div class="card bg-info text-dark shadow">
            <div class="card-body">
                <h3>إجمالي مسحوبات الموظفين</h3>
                <h2 class="fw-bold"><?= number_format($allWithdrawals) ?> YER</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Add New Staff -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">إضافة موظف جديد</h5>
            </div>
            <div class="card-body">
                <form action="requests/add_staff.php" method="POST" id="addStaffForm">
                    <div class="mb-3">
                        <label class="form-label">الاسم</label>
                        <input type="text" name="name" id="s_name" class="form-control" required enterkeyhint="next">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوظيفة</label>
                        <input type="text" name="role" id="s_role" class="form-control" placeholder="مثال: مبيعات، عامل" enterkeyhint="next">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف</label>
                        <div class="input-group">
                            <input type="tel" name="phone" id="s_phone" class="form-control text-end" placeholder="7xxxxxxxxx" enterkeyhint="next" inputmode="numeric">
                            <button type="button" class="btn btn-warning" onclick="pickContact('s_phone')">
                                <i class="fas fa-address-book"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الراتب اليومي</label>
                        <input type="number" step="1" name="daily_salary" id="s_salary" class="form-control" value="0" required enterkeyhint="next" inputmode="numeric">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">سقف السحب</label>
                        <input type="number" step="1" name="withdrawal_limit" id="s_limit" class="form-control" placeholder="اختياري" enterkeyhint="done" inputmode="numeric">
                        <div class="form-text">اتركه فارغاً إذا لا يوجد سقف للسحب</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="btn_save_staff">إضافة موظف</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Staff List -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة الموظفين</h5>
                <a href="?show_inactive=<?= $showInactive ? 0 : 1 ?>" class="btn btn-sm <?= $showInactive ? 'btn-light' : 'btn-outline-light' ?>">
                    <?= $showInactive ? 'عرض النشطين فقط' : 'عرض الموظفين الملغى تنشيطهم' ?>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>قائمة الموظفين</th>
                                <th>الراتب اليومي</th>
                                <th>السقف</th>
                                <th>المسحوب</th>
                                <th>المتبقي</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffMembers as $s):
                                $hasLimit = ($s['withdrawal_limit'] !== null);
                                $withdrawn = $s['current_withdrawals'] ?? 0;
                                $rem = $hasLimit ? (($s['withdrawal_limit'] ?: 0) - $withdrawn) : null;
                                $rowClass = ($hasLimit && $rem <= 0) ? 'table-danger' : '';
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td><?= number_format($s['daily_salary'] ?: 0) ?></td>
                                    <td><?= $hasLimit ? number_format($s['withdrawal_limit']) : '<span class="text-muted">بدون سقف</span>' ?></td>
                                    <td class="fw-bold text-danger"><?= number_format($withdrawn) ?></td>
                                    <td class="fw-bold">
                                        <?php if ($hasLimit): ?>
                                            <span class="<?= $rem > 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($rem) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1" style="justify-content: center;">
                                            <a href="staff_details.php?id=<?= $s['id'] ?>" class="btn btn-info btn-sm text-white" title="تفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-warning btn-sm" title="تعديل" 
                                                onclick="editStaff(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= htmlspecialchars(addslashes($s['role'])) ?>', '<?= htmlspecialchars(addslashes($s['phone'] ?? '')) ?>', <?= $s['daily_salary'] ?: 0 ?>, '<?= $s['withdrawal_limit'] !== null ? $s['withdrawal_limit'] : '' ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($s['is_active']): ?>
                                                <form action="requests/deactivate_staff.php" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من إلغاء تنشيط هذا الموظف؟ لن يظهر في القائمة النشطة ولكن بياناته ستبقى محفوظة.');">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm" title="إلغاء تنشيط">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="requests/deactivate_staff.php" method="POST" style="display:inline;" onsubmit="return confirm('هل تريد إعادة تنشيط هذا الموظف؟');">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <input type="hidden" name="activate" value="1">
                                                    <button type="submit" class="btn btn-success btn-sm" title="إعادة تنشيط">
                                                        <i class="fas fa-user-check"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- Quick Report Link -->
<div class="text-center mt-4 mb-5 no-print">
    <a href="reports.php?report_type=Daily" class="btn btn-outline-secondary">
        <i class="fas fa-file-invoice me-2"></i> عرض تقرير اليوم المفصل
    </a>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-end">
      <form action="requests/update_staff.php" method="POST">
        <div class="modal-header d-flex justify-content-between align-items-center bg-warning">
          <h5 class="modal-title m-0 w-100" id="editStaffModalLabel">تعديل بيانات الموظف</h5>
          <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="edit_s_id">
            <input type="hidden" name="return_url" value="../staff.php">
            <div class="mb-3">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" id="edit_s_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">الوظيفة</label>
                <input type="text" name="role" id="edit_s_role" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <input type="tel" name="phone" id="edit_s_phone" class="form-control text-end" inputmode="numeric">
            </div>
            <div class="mb-3">
                <label class="form-label">الراتب اليومي</label>
                <input type="number" step="1" name="daily_salary" id="edit_s_salary" class="form-control" required inputmode="numeric">
            </div>
            <div class="mb-3">
                <label class="form-label">سقف السحب</label>
                <input type="number" step="1" name="withdrawal_limit" id="edit_s_limit" class="form-control" placeholder="اختياري" inputmode="numeric">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
          <button type="submit" class="btn btn-warning">حفظ التعديلات</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    // Universal Focus Navigation Helper
    function setupFocusNavigation(fieldIds, submitBtnId = null) {
        fieldIds.forEach((id, index) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (index < fieldIds.length - 1) {
                        const next = document.getElementById(fieldIds[index + 1]);
                        if (next) next.focus();
                    } else if (submitBtnId) {
                        const btn = document.getElementById(submitBtnId);
                        if (btn) btn.click();
                    }
                }
            });
        });
    }

    // Contact Picker
    async function pickContact(fieldId) {
        if (!('contacts' in navigator && 'ContactsManager' in window)) {
            alert('هذه الميزة مدعومة فقط في متصفحات الجوال الحديثة (Chrome/Android).');
            return;
        }
        try {
            const contacts = await navigator.contacts.select(['tel'], { multiple: false });
            if (contacts && contacts.length > 0 && contacts[0].tel && contacts[0].tel.length > 0) {
                let phone = contacts[0].tel[0].replace(/[^0-9+]/g, '');
                document.getElementById(fieldId).value = phone;
            }
        } catch (e) {
            console.log('Contact picker cancelled or failed', e);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupFocusNavigation(['s_name', 's_role', 's_phone', 's_salary', 's_limit'], 'btn_save_staff');
    });

    function editStaff(id, name, role, phone, salary, limit) {
        document.getElementById('edit_s_id').value = id;
        document.getElementById('edit_s_name').value = name;
        document.getElementById('edit_s_role').value = role;
        document.getElementById('edit_s_phone').value = phone;
        document.getElementById('edit_s_salary').value = salary;
        document.getElementById('edit_s_limit').value = limit;
        
        let editModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
        editModal.show();
    }
</script>

<?php include 'includes/footer.php'; ?>