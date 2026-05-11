<?php
require_once 'config/db.php';
include_once 'includes/header.php';

// Initialization via Clean Architecture
$staffRepo = new StaffRepository($pdo);
$expenseRepo = new ExpenseRepository($pdo);
$depositRepo = new DepositRepository($pdo);
$providerRepo = new ProviderRepository($pdo);

// Fetch Staff with current withdrawals
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$staff = $staffRepo->getWithCurrentWithdrawals($user_id, $user_role);
$jsonStaff = json_encode($staff);

$today = getOperationalDate();

// Providers list for the new category (Fetching all to ensure visibility)
$providers = $providerRepo->getAll();

// Expenses
$expenses = $expenseRepo->getTodayExpenses($today, $user_id);

// Deposits
$deposits = $depositRepo->getTodayDeposits($today, $user_id);

// Restored Categories list with role-based filtering
$isSuperAdmin = ($_SESSION['role'] ?? 'admin') === 'super_admin';
$categories = ['إيجار', 'كهرباء', 'ماء', 'تغذية', 'نقل'];
if ($isSuperAdmin) {
    $categories[] = 'تسديد مورد';
}
$categories[] = 'أخرى';

$totalExp = 0;
foreach ($expenses as $e) {
    $totalExp += $e['amount'];
}
?>


<style>
    :root {
        --exp-primary: #f43f5e;
        --exp-bg: #fff1f2;
        --dep-primary: #0ea5e9;
        --dep-bg: #f0f9ff;
        --glass: rgba(255, 255, 255, 0.8);
    }

    body {
        background-color: #f8fafc;
    }

    .premium-card {
        background: var(--glass);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 20px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    /* Tab Controller */
    .type-toggle {
        display: inline-flex;
        background: #e2e8f0;
        padding: 4px;
        border-radius: 14px;
        margin-bottom: 2rem;
    }

    .type-btn {
        padding: 10px 30px;
        border-radius: 11px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .type-btn.active-exp {
        background: var(--exp-primary);
        color: white;
        box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
    }

    .type-btn.active-dep {
        background: var(--dep-primary);
        color: white;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .form-section {
        display: none;
    }

    .form-section.active {
        display: block;
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .input-group-text {
        background: transparent;
        border-right: none;
        color: #64748b;
    }

    .form-control,
    .form-select {
        border-left: none;
        border-radius: 0 10px 10px 0;
        padding: 12px;
        background: #fcfcfc;
    }

    .form-control:focus {
        box-shadow: none;
    }

    .floating-label {
        color: #94a3b8;
        font-size: 0.85rem;
        margin-bottom: 4px;
        display: block;
    }

    .btn-submit {
        padding: 14px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        width: 100%;
        transition: transform 0.2s ease;
    }

    .btn-submit:active {
        transform: scale(0.98);
    }

    /* Modern Tables */
    .card-list {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
    }

    .list-item {
        border-bottom: 1px solid #f1f5f9;
        padding: 12px 0;
        transition: background 0.2s;
    }

    .list-item:hover {
        background: #f8fafc;
    }

    .currency-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 5px;
        text-transform: uppercase;
    }
</style>

<div class="container py-4">
    <div class="text-center">
        <?php if ($isSuperAdmin): ?>
        <div class="type-toggle">
            <button class="type-btn active-exp" id="btnExp" onclick="switchTab('expense')">
                <i class="fas fa-arrow-up-right-from-square"></i> مصاريف (خرج)
            </button>
            <button class="type-btn" id="btnDep" onclick="switchTab('deposit')">
                <i class="fas fa-arrow-down-left-and-arrow-up-right-to-square"></i> توريد (إيداع)
            </button>
        </div>
        <?php else: ?>
            <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-wallet me-2"></i> إدارة المصاريف</h3>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6 mb-5">
            <div class="premium-card p-4">

                <!-- Expense Form -->
                <div id="expenseSection" class="form-section active">
                    <h4 class="mb-4 fw-bold text-danger"><i class="fas fa-minus-circle me-2"></i> بيانات المصروف</h4>
                    <form action="requests/process_expense.php" method="POST">
                        <input type="hidden" name="expense_date" value="<?= $today ?>">

                        <div class="mb-3">
                            <span class="floating-label">التصنيف</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <select name="category" class="form-select" id="catSelect" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Staff">سحبيات موظف</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3" id="staff_group" style="display:none;">
                            <span class="floating-label">الموظف</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                <select name="staff_id" id="staffSelect" class="form-select" onchange="checkStaffLimit()">
                                    <option value="">-- اختر موظف --</option>
                                    <?php foreach ($staff as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Real-time Warning (#35) -->
                            <div id="staffLimitWarning" class="mt-2 small fw-bold" style="display:none;"></div>
                        </div>

                        <div class="mb-3" id="provider_group" style="display:none;">
                            <span class="floating-label">المورد (الرعوي)</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-truck-loading"></i></span>
                                <select name="provider_id" id="providerSelect" class="form-select">
                                    <option value="">-- اختر مورد --</option>
                                    <?php foreach ($providers as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="floating-label">ملاحظة</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-right"></i></span>
                                <input type="text" name="description" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <span class="floating-label">المبلغ المطلوب</span>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">YER</span>
                                    <input type="number" name="amount" class="form-control form-control-lg fw-bold text-danger" placeholder="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <span class="floating-label">طريقة الدفع</span>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="Cash">نقد (كاش)</option>
                                        <?php if ($isSuperAdmin): ?>
                                            <option value="Transfer">تحويل (إلكتروني)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit bg-danger text-white mt-2">
                            <i class="fas fa-check-circle me-1"></i> تنفيذ العملية
                        </button>
                    </form>
                </div>

                <!-- Deposit Form -->
                <div id="depositSection" class="form-section">
                    <h4 class="mb-4 fw-bold text-primary"><i class="fas fa-university me-2"></i> بيانات التوريد</h4>
                    <form action="requests/process_deposit.php" method="POST">
                        <input type="hidden" name="deposit_date" value="<?= $today ?>">

                        <div class="mb-3">
                            <span class="floating-label">العملة</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                <select name="currency" class="form-select" required>
                                    <option value="YER">ريال يمني (YER)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="floating-label">المستلم (الجهة)</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <input type="text" name="recipient" class="form-control" placeholder="اسم الصراف أو المندوب" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="floating-label">المبلغ</span>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">YER</span>
                                <input type="number" step="0.01" name="amount" class="form-control form-control-lg fw-bold text-primary" placeholder="0" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <span class="floating-label">ملاحظات</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                                <input type="text" name="notes" class="form-control" placeholder="رقم الحوالة أو أي ملاحظة">
                            </div>
                        </div>

                        <button type="submit" class="btn-submit bg-primary text-white mt-2">
                            <i class="fas fa-download me-1"></i> حفظ الإيداع
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Tables Area -->
    <div class="row g-4 <?= $isSuperAdmin ? '' : 'justify-content-center' ?>">
        <div class="col-md-<?= $isSuperAdmin ? '6' : '8' ?>">
            <div class="card-list shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="mb-0 fw-bold text-danger border-bottom border-2 border-danger pb-1">مصاريف اليوم</h6>
                    <?php if ($totalExp > 0): ?>
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">الإجمالي: <?= number_format($totalExp) ?></span>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <tbody class="small">
                            <?php foreach ($expenses as $e): ?>
                                <tr class="list-item">
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($e['description'] ?: 'رقم ' . $e['id']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <span class="badge bg-secondary-subtle text-secondary me-1"><?= htmlspecialchars($e['category'] ?: 'غير مصنف') ?></span> 
                                            | <?= htmlspecialchars($e['staff_name'] ?? ($e['provider_name'] ?? 'مصروف عام')) ?>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-danger fs-6"><?= number_format($e['amount']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-primary" onclick="editExpense(<?= $e['id'] ?>, '<?= addslashes($e['category']) ?>', '<?= addslashes($e['description']) ?>', <?= $e['amount'] ?>, <?= $e['staff_id'] ?? 'null' ?>, <?= $e['provider_id'] ?? 'null' ?>, '<?= $e['payment_method'] ?>')"><i class="fas fa-edit"></i></button>
                                        <form action="requests/delete_expense.php" method="POST" onsubmit="return confirm('هل أنت متأكد؟')" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-muted"><i class="fas fa-times"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">لا توجد مصاريف اليوم</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($isSuperAdmin): ?>
        <div class="col-md-6">
            <div class="card-list shadow-sm">
                <h6 class="mb-4 fw-bold text-primary border-bottom border-2 border-primary pb-1">المبالغ الموردة</h6>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <tbody class="small">
                            <?php foreach ($deposits as $d): ?>
                                <tr class="list-item">
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($d['recipient']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($d['notes'] ?: 'بدون ملاحظات') ?></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-primary"><?= number_format($d['amount'], ($d['currency'] == 'YER' ? 0 : 2)) ?></div>
                                        <span class="badge bg-primary-subtle text-primary currency-badge"><?= $d['currency'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-primary" onclick="editDeposit(<?= $d['id'] ?>, '<?= addslashes($d['recipient']) ?>', '<?= addslashes($d['notes']) ?>', <?= $d['amount'] ?>, '<?= $d['currency'] ?>')"><i class="fas fa-edit"></i></button>
                                        <form action="requests/delete_deposit.php" method="POST" onsubmit="return confirm('هل أنت متأكد؟')" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-muted"><i class="fas fa-times"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">لا توجد إيداعات اليوم</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل المصروف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/update_expense.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_exp_id">
                    <div class="mb-3">
                        <label class="floating-label">التصنيف</label>
                        <select name="category" class="form-select" id="edit_exp_cat" required onchange="toggleEditStaffGroup()">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="Staff">سحبيات موظف</option>
                        </select>
                    </div>
                    <div class="mb-3" id="edit_staff_group" style="display:none;">
                        <label class="floating-label">الموظف</label>
                        <select name="staff_id" id="edit_staff_select" class="form-select">
                            <option value="">-- اختر موظف --</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="edit_provider_group" style="display:none;">
                        <label class="floating-label">المورد (الرعوي)</label>
                        <select name="provider_id" id="edit_provider_select" class="form-select">
                            <option value="">-- اختر مورد --</option>
                            <?php foreach ($providers as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="floating-label">ملاحظة</label>
                        <input type="text" name="description" id="edit_exp_desc" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="floating-label">المبلغ</label>
                            <input type="number" name="amount" id="edit_exp_amount" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="floating-label">طريقة الدفع</label>
                            <select name="payment_method" id="edit_exp_method" class="form-select" required>
                                <option value="Cash">نقد (كاش)</option>
                                <?php if ($isSuperAdmin): ?>
                                    <option value="Transfer">تحويل (إلكتروني)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Deposit Modal -->
<div class="modal fade" id="editDepositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل التوريد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/update_deposit.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_dep_id">
                    <div class="mb-3">
                        <label class="floating-label">المستلم (الجهة)</label>
                        <input type="text" name="recipient" id="edit_dep_recipient" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="floating-label">العملة</label>
                        <select name="currency" id="edit_dep_currency" class="form-select" required>
                            <option value="YER">ريال يمني (YER)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="floating-label">المبلغ</label>
                        <input type="number" step="0.01" name="amount" id="edit_dep_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="floating-label">ملاحظات</label>
                        <input type="text" name="notes" id="edit_dep_notes" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function switchTab(type) {
        const btnExp = document.getElementById('btnExp');
        const btnDep = document.getElementById('btnDep');
        const secExp = document.getElementById('expenseSection');
        const secDep = document.getElementById('depositSection');

        if (type === 'expense') {
            btnExp.classList.add('active-exp');
            btnDep.classList.remove('active-dep');
            secExp.classList.add('active');
            secDep.classList.remove('active');
            document.getElementById('catSelect').focus();
        } else {
            btnExp.classList.remove('active-exp');
            btnDep.classList.add('active-dep');
            secExp.classList.remove('active');
            secDep.classList.add('active');
            document.querySelector('#depositSection input[name="recipient"]').focus();
        }
    }

    // Toggle Staff/Provider select
    document.getElementById('catSelect').addEventListener('change', function() {
        console.log("Category Selected:", this.value);
        document.getElementById('staff_group').style.display = (this.value === 'Staff' ? 'block' : 'none');
        // Robust comparison for the category name
        const isProviderPayment = (this.value.trim() === 'تسديد مورد');
        console.log("Is Provider Payment?", isProviderPayment);
        document.getElementById('provider_group').style.display = isProviderPayment ? 'block' : 'none';
        
        if (this.value !== 'Staff') document.getElementById('staffLimitWarning').style.display = 'none';
    });

    const staffData = <?= $jsonStaff ?>;

    function checkStaffLimit() {
        const staffId = document.getElementById('staffSelect').value;
        const warning = document.getElementById('staffLimitWarning');

        if (!staffId) {
            warning.style.display = 'none';
            return;
        }

        const s = staffData.find(item => item.id == staffId);
        if (s) {
            const current = parseFloat(s.current_withdrawals || 0);
            // Use withdrawal_limit if exists, else fallback to daily_salary
            const limit = s.withdrawal_limit !== null ? parseFloat(s.withdrawal_limit) : parseFloat(s.daily_salary);
            const rem = limit - current;

            if (limit <= 0) {
                warning.style.display = 'none';
                return;
            }

            warning.style.display = 'block';
            if (rem <= 0) {
                warning.className = 'mt-2 small fw-bold text-danger';
                warning.innerHTML = `<i class="fas fa-exclamation-triangle"></i> تجاوز السقف! المسحوب: ${Math.round(current)} | الراتب: ${Math.round(limit)}`;
            } else if (rem < (limit * 0.2)) {
                warning.className = 'mt-2 small fw-bold text-warning';
                warning.innerHTML = `<i class="fas fa-info-circle"></i> اقترب من السقف! المتبقي: ${Math.round(rem)}`;
            } else {
                warning.className = 'mt-2 small fw-bold text-success';
                warning.innerHTML = `<i class="fas fa-check-circle"></i> ضمن المسموح. المتبقي: ${Math.round(rem)}`;
            }
        } else {
            warning.style.display = 'none';
        }
    }

    function editExpense(id, category, description, amount, staffId, providerId) {
        document.getElementById('edit_exp_id').value = id;

        // Ensure category exists in dropdown, else add it dynamically
        const catSelect = document.getElementById('edit_exp_cat');
        let exists = false;
        for (let i = 0; i < catSelect.options.length; i++) {
            if (catSelect.options[i].value === category) {
                exists = true;
                break;
            }
        }
        if (!exists && category) {
            const opt = document.createElement('option');
            opt.value = category;
            opt.text = category;
            catSelect.add(opt);
        }

        catSelect.value = category || "أخرى";
        document.getElementById('edit_exp_desc').value = description;
        document.getElementById('edit_exp_amount').value = amount;
        
        // Handle payment method if it exists in the row data
        // For simplicity, we'll assume it's passed or defaults to Cash
        const method = arguments[6] || 'Cash'; 
        document.getElementById('edit_exp_method').value = method;

        // Set Staff
        const staffSelect = document.getElementById('edit_staff_select');
        if (staffId && staffId !== "null") {
            staffSelect.value = staffId;
            document.getElementById('edit_staff_group').style.display = 'block';
        } else {
            staffSelect.value = "";
            document.getElementById('edit_staff_group').style.display = (category === 'Staff' ? 'block' : 'none');
        }

        // Set Provider
        const providerSelect = document.getElementById('edit_provider_select');
        if (providerId && providerId !== "null") {
            providerSelect.value = providerId;
            document.getElementById('edit_provider_group').style.display = 'block';
        } else {
            providerSelect.value = "";
            document.getElementById('edit_provider_group').style.display = (category === 'تسديد مورد' ? 'block' : 'none');
        }

        new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
    }

    function toggleEditStaffGroup() {
        const cat = document.getElementById('edit_exp_cat').value;
        document.getElementById('edit_staff_group').style.display = (cat === 'Staff' ? 'block' : 'none');
        document.getElementById('edit_provider_group').style.display = (cat === 'تسديد مورد' ? 'block' : 'none');
    }

    function editDeposit(id, recipient, notes, amount, currency) {
        document.getElementById('edit_dep_id').value = id;
        document.getElementById('edit_dep_recipient').value = recipient;
        document.getElementById('edit_dep_notes').value = notes;
        document.getElementById('edit_dep_amount').value = amount;
        document.getElementById('edit_dep_currency').value = currency;
        new bootstrap.Modal(document.getElementById('editDepositModal')).show();
    }
</script>

<?php include_once 'includes/footer.php'; ?>