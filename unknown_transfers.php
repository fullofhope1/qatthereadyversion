<?php
require 'config/db.php';
include 'includes/header.php';

// Initialization via Clean Architecture
$commRepo = new CommunicationRepository($pdo);
$service = new CommunicationService($commRepo);
$custRepo = new CustomerRepository($pdo);

$transfers = $service->getUnknownTransfersData();
$customers = $custRepo->getAllActive();
?>

<style>
    .step-wizard {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 1.5rem;
    }

    .step-wizard .step {
        flex: 1;
        text-align: center;
        padding: 10px 5px;
        background: #f1f5f9;
        border: 1px solid #dee2e6;
        font-weight: 600;
        font-size: 0.85rem;
        color: #6c757d;
        position: relative;
        cursor: default;
        transition: all 0.3s;
    }

    .step-wizard .step:first-child {
        border-radius: 12px 0 0 12px;
    }

    .step-wizard .step:last-child {
        border-radius: 0 12px 12px 0;
    }

    .step-wizard .step.active {
        background: #f59e0b;
        color: #000;
        border-color: #f59e0b;
    }

    .step-wizard .step.done {
        background: #22c55e;
        color: #fff;
        border-color: #22c55e;
    }

    .step-panel {
        display: none;
    }

    .step-panel.active {
        display: block;
    }

    /* Premium Upgrades */
    .transfer-card {
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }

    .transfer-header {
        background: var(--brand-gradient);
        padding: 2rem;
        position: relative;
    }

    .transfer-header::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.2) 0%, transparent 60%);
    }

    .table-hover tbody tr {
        transition: all 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(255, 193, 7, 0.05);
        transform: scale(1.005);
    }

    .btn-premium {
        border-radius: 12px;
        padding: 0.5rem 1rem;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="row justify-content-center">
    <!-- FORM SECTION -->
    <div class="col-md-6 mb-4">
        <div class="transfer-card">
            <div class="transfer-header text-dark">
                <h4 class="mb-0 fw-bold"><i class="fas fa-question-circle me-2"></i> إضافة تحويل مجهول</h4>
                <p class="mb-0 small opacity-75 mt-1">تسجيل تحويل مجهول المصدر خطوة بخطوة</p>
            </div>
            <div class="p-4 bg-white">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> تم حفظ التحويل بنجاح!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>

                <!-- Step Indicator -->
                <div class="step-wizard" id="stepWizard">
                    <div class="step active" id="ind1">① التاريخ والمبلغ</div>
                    <div class="step" id="ind2">② بيانات المرسل</div>
                    <div class="step" id="ind3">③ تأكيد ✓</div>
                </div>

                <form action="requests/process_unknown_transfer.php" method="POST" id="tfForm">

                    <!-- Step 1: Date + Amount -->
                    <div class="step-panel active" id="sp1">
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1 text-warning"></i> التاريخ</label>
                            <input type="date" class="form-control form-control-lg" name="transfer_date" id="f_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-user-tie me-1 text-warning"></i> اسم المستلم</label>
                            <input type="text" class="form-control form-control-lg" name="receiver_name" id="f_receiver" placeholder="من استلم الحوالة؟" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-7">
                                <label class="form-label fw-bold"><i class="fas fa-money-bill me-1 text-warning"></i> المبلغ</label>
                                <input type="number" step="0.01" class="form-control form-control-lg" name="amount" id="f_amount" placeholder="0.00" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label fw-bold">العملة</label>
                                <select name="currency" class="form-select form-select-lg">
                                    <option value="YER">🇾🇪 ريال (YER)</option>
                                    <option value="SAR">🇸🇦 سعودي (SAR)</option>
                                    <option value="USD">🇺🇸 دولار (USD)</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="btn btn-warning w-100 py-3 fw-bold" onclick="goStep(2)">
                            التالي <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                    </div>

                    <!-- Step 2: Sender Info -->
                    <div class="step-panel" id="sp2">
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-user-tie me-1 text-warning"></i> اسم المستلم</label>
                            <input type="text" class="form-control form-control-lg" name="receiver_name" id="f_receiver" placeholder="من استلم الحوالة؟" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-receipt me-1 text-warning"></i> رقم السند / الحوالة</label>
                            <input type="text" class="form-control form-control-lg" name="receipt_number" id="f_receipt" placeholder="مثال: 123456" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-user me-1 text-warning"></i> اسم المرسل</label>
                            <input type="text" class="form-control form-control-lg" name="sender_name" id="f_sender" placeholder="مجهول" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-sticky-note me-1 text-warning"></i> ملاحظات</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="أي معلومات إضافية..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-bold" onclick="goStep(1)">
                                <i class="fas fa-arrow-right me-2"></i> رجوع
                            </button>
                            <button type="button" class="btn btn-warning w-50 py-3 fw-bold" onclick="goStep(3)">
                                مراجعة <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Confirm -->
                    <div class="step-panel" id="sp3">
                        <div class="card bg-warning-subtle border-warning mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-warning-emphasis mb-3"><i class="fas fa-check-circle me-2"></i> مراجعة البيانات</h6>
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td class="text-muted fw-bold">التاريخ</td>
                                        <td id="rev_date">—</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-bold">المبلغ</td>
                                        <td id="rev_amount">—</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-bold">رقم السند</td>
                                        <td id="rev_receipt">—</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-bold">المرسل</td>
                                        <td id="rev_sender">—</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-bold" onclick="goStep(2)">
                                <i class="fas fa-arrow-right me-2"></i> رجوع
                            </button>
                            <button type="submit" class="btn btn-dark w-50 py-3 fw-bold">
                                <i class="fas fa-save me-2"></i> حفظ التحويل
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- List Section -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> سجل التحويلات</h5>
                <span class="badge bg-warning text-dark"><?= count($transfers) ?></span>
            </div>
            <div class="card-body p-3">
                <input type="text" id="tfSearch" class="form-control mb-3" placeholder="🔍 بحث باسم المرسل أو رقم السند...">
                <div style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-hover table-sm mb-0 align-middle" id="tfTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>التاريخ</th>
                                <th>رقم السند</th>
                                <th>المرسل</th>
                                <th>المبلغ</th>
                                <th>العملة</th>
                                <th>تعديل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transfers) > 0): ?>
                                <?php foreach ($transfers as $t): ?>
                                    <tr>
                                        <td class="small"><?= $t['transfer_date'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($t['receipt_number']) ?></td>
                                        <td><?= htmlspecialchars($t['sender_name']) ?></td>
                                        <td class="fw-bold text-success"><?= number_format($t['amount'], 0) ?></td>
                                        <td><span class="badge bg-secondary"><?= $t['currency'] ?? 'YER' ?></span></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="editTransfer(<?= htmlspecialchars(json_encode($t)) ?>)" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-success rounded-pill" onclick="openLinkModal(<?= htmlspecialchars(json_encode($t)) ?>)" title="ربط بعميل">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5"><i class="fas fa-inbox fs-1 d-block mb-2 opacity-25"></i>لا توجد تحويلات مسجلة.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editTransferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> تعديل التحويل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/update_unknown_transfer.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_tf_id">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">التاريخ</label>
                            <input type="date" name="transfer_date" id="edit_tf_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">رقم السند</label>
                            <input type="text" name="receipt_number" id="edit_tf_receipt" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">اسم المستلم</label>
                            <input type="text" name="receiver_name" id="edit_tf_receiver" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">اسم المرسل</label>
                            <input type="text" name="sender_name" id="edit_tf_sender" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-7">
                            <label class="form-label fw-bold">المبلغ</label>
                            <input type="number" step="0.01" name="amount" id="edit_tf_amount" class="form-control">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-bold">العملة</label>
                            <select name="currency" id="edit_tf_currency" class="form-select">
                                <option value="YER">YER</option>
                                <option value="SAR">SAR</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات</label>
                        <textarea name="notes" id="edit_tf_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4"><i class="fas fa-save me-2"></i> حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Link to Customer Modal -->
<div class="modal fade" id="linkTransferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-success text-white border-0" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-link me-2"></i> ربط التحويل بعميل</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <p class="mb-1 small text-muted">سيتم تحويل هذا المبلغ إلى "سداد" في حساب العميل المختار.</p>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span id="link_tf_info_receipt" class="fw-bold"></span>
                        <span id="link_tf_info_amount" class="badge bg-success fs-6"></span>
                    </div>
                </div>

                <input type="hidden" id="link_tf_id">

                <div class="mb-3">
                    <label class="form-label fw-bold">اختر العميل</label>
                    <input type="text" id="custSearch" class="form-control mb-2" placeholder="🔍 ابحث عن اسم العميل...">
                    <select id="link_tf_customer" class="form-select form-select-lg" size="5">
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['phone'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light btn-premium opacity-75" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success btn-premium px-4" onclick="confirmLink()">
                    <i class="fas fa-check-circle me-1"></i> تنفيذ الربط
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Search
    document.getElementById('tfSearch').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#tfTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    // Edit modal
    function editTransfer(t) {
        document.getElementById('edit_tf_id').value = t.id;
        document.getElementById('edit_tf_date').value = t.transfer_date;
        document.getElementById('edit_tf_receipt').value = t.receipt_number;
        document.getElementById('edit_tf_sender').value = t.sender_name;
        document.getElementById('edit_tf_receiver').value = t.receiver_name || '';
        document.getElementById('edit_tf_amount').value = t.amount;
        document.getElementById('edit_tf_currency').value = t.currency || 'YER';
        document.getElementById('edit_tf_notes').value = t.notes || '';
        new bootstrap.Modal(document.getElementById('editTransferModal')).show();
    }

    // Link Modal
    function openLinkModal(t) {
        document.getElementById('link_tf_id').value = t.id;
        document.getElementById('link_tf_info_receipt').textContent = 'سند رقم: ' + t.receipt_number;
        document.getElementById('link_tf_info_amount').textContent = parseFloat(t.amount).toLocaleString() + ' ' + (t.currency || 'YER');
        new bootstrap.Modal(document.getElementById('linkTransferModal')).show();
    }

    // Customer search in modal
    document.getElementById('custSearch').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        const select = document.getElementById('link_tf_customer');
        for (let opt of select.options) {
            opt.style.display = opt.text.toLowerCase().includes(term) ? '' : 'none';
        }
    });

    async function confirmLink() {
        const transferId = document.getElementById('link_tf_id').value;
        const customerId = document.getElementById('link_tf_customer').value;

        if (!customerId) {
            alert('يرجى اختيار العميل أولاً.');
            return;
        }

        if (!confirm('هل أنت متأكد من ربط هذا التحويل بهذا العميل؟ سيتم حذفه من هذه القائمة وإضافته كسداد للعميل.')) return;

        const formData = new FormData();
        formData.append('transfer_id', transferId);
        formData.append('customer_id', customerId);

        try {
            const resp = await fetch('requests/link_transfer_ajax.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'فشل في ربط التحويل.');
            }
        } catch (e) {
            alert('حدث خطأ أثناء الاتصال بالخادم.');
        }
    }

    // Step wizard
    function goStep(step) {
        // Validate step 1 before going to step 2
        if (step === 2) {
            const amt = document.getElementById('f_amount').value;
            const dt = document.getElementById('f_date').value;
            const receiver = document.getElementById('f_receiver').value;
            if (!amt || !dt || !receiver) {
                alert('يرجى تعبئة التاريخ والمستلم والمبلغ.');
                return;
            }
        }
        if (step === 3) {
            const receipt = document.getElementById('f_receipt').value;
            const sender = document.getElementById('f_sender').value;
            if (!receipt || !sender) {
                alert('يرجى تعبئة رقم السند واسم المرسل.');
                return;
            }
            // Fill review
            const currency = document.querySelector('select[name="currency"]').value;
            document.getElementById('rev_date').textContent = document.getElementById('f_date').value;
            document.getElementById('rev_amount').textContent = parseFloat(document.getElementById('f_amount').value || 0).toLocaleString() + ' ' + currency;
            document.getElementById('rev_receipt').textContent = receipt;
            document.getElementById('rev_sender').textContent = sender;
        }
        [1, 2, 3].forEach(i => {
            document.getElementById('sp' + i).classList.toggle('active', i === step);
            const ind = document.getElementById('ind' + i);
            ind.classList.remove('active', 'done');
            if (i < step) ind.classList.add('done');
            if (i === step) ind.classList.add('active');
        });
    }
</script>

<?php include 'includes/footer.php'; ?>