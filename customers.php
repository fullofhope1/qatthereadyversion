<?php
require 'config/db.php';
include 'includes/header.php';

// Fetch Customers via Clean Architecture
$customerRepo = new CustomerRepository($pdo);
$customerService = new CustomerService($customerRepo);
$customers = $customerService->listCustomers();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="mb-0">العملاء</h4>
                <div class="flex-grow-1 mx-md-4">
                    <input type="text" id="custSearch" class="form-control" placeholder="بحث باسم العميل أو رقم الجوال..." onkeyup="filterCustomers()">
                </div>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">+ إضافة عميل</button>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الجوال</th>
                                <th>الدين الحالي</th>
                                <th>سقف الدين</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['name']) ?></td>
                                    <td><?= htmlspecialchars($c['phone']) ?></td>
                                    <td class="<?= $c['total_debt'] > 0 ? 'text-danger fw-bold' : ($c['total_debt'] < 0 ? 'text-success' : '') ?>">
                                        <?php if ($c['total_debt'] < 0): ?>
                                            <span class="badge bg-success">له: <?= number_format(abs($c['total_debt'])) ?></span>
                                        <?php else: ?>
                                            <?= number_format($c['total_debt']) ?>
                                        <?php endif; ?>
                                        / <span class="text-muted small"><?= number_format($c['debt_limit'] ?? 0) ?></span>
                                        <?php if (!is_null($c['debt_limit']) && $c['total_debt'] > $c['debt_limit']): ?>
                                            <span class="badge bg-danger ms-2">تجاوز السقف</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if (is_null($c['debt_limit'])) {
                                            echo 'بدون سقف';
                                        } else {
                                            echo number_format($c['debt_limit']) . ' ريال';
                                        } ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="customer_details.php?id=<?= $c['id'] ?>" class="btn btn-success btn-sm" title="سداد">
                                                <i class="fas fa-hand-holding-usd me-1"></i> سداد
                                            </a>
                                            <a href="customer_statement.php?id=<?= $c['id'] ?>" class="btn btn-dark btn-sm" title="كشف حساب">
                                                <i class="fas fa-print"></i> كشف
                                            </a>
                                            <a href="edit_customer.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="requests/delete_customer.php" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العميل؟ لا يمكن التراجع عن هذا الإجراء');">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="حذف">
                                                    <i class="fas fa-trash"></i>
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة عميل جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/add_customer.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الجوال <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="tel" class="form-control text-end" name="phone" id="c_phone" required pattern="[0-9]{7,15}" inputmode="numeric" placeholder="7xxxxxxxxx">
                            <button type="button" class="btn btn-warning" onclick="pickContact('c_phone')">
                                <i class="fas fa-address-book"></i>
                            </button>
                        </div>
                        <div class="form-text">أدخل أرقاماً فقط</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-danger fw-bold">سقف الدين (اتركه فارغاً لعدم وجود سقف)</label>
                        <input type="number" class="form-control" name="debt_limit" placeholder="بدون سقف">
                        <div class="form-text">الحد الأقصى للديون المسموح بها لهذا العميل. اتركه فارغاً لغير محدود.</div>
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
    function filterCustomers() {
        const input = document.getElementById('custSearch');
        const filter = input.value.toLowerCase();
        const tbody = document.getElementById('customersTableBody');
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCol = rows[i].getElementsByTagName('td')[0];
            const phoneCol = rows[i].getElementsByTagName('td')[1];

            if (nameCol || phoneCol) {
                const nameText = nameCol.textContent || nameCol.innerText;
                const phoneText = phoneCol.textContent || phoneCol.innerText;

                if (nameText.toLowerCase().indexOf(filter) > -1 || phoneText.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    }
    async function pickContact(fieldId) {
        if (!window.isSecureContext) {
            alert('عذراً، ميزة الوصول لجهات الاتصال تتطلب اتصالاً آمناً (HTTPS). يرجى التأكد من تشغيل الموقع عبر https:// للتمكن من استخدام هذه الميزة.');
            return;
        }
        if (!('contacts' in navigator && 'ContactsManager' in window)) {
            alert('هذه الميزة مدعومة فقط في متصفحات الجوال الحديثة (Chrome/Android) وعبر اتصال آمن.');
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
</script>

<?php include 'includes/footer.php'; ?>