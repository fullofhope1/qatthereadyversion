<?php
// sourcing.php - Enhanced Multi-step Wizard
require 'config/db.php';
include 'includes/header.php';

// Strict Check: Sourcing is NOT for Super Admins
if ($_SESSION['role'] === 'super_admin') {
    header("Location: purchases.php");
    exit;
}

// Fetch Types
$types = $pdo->query("SELECT * FROM qat_types WHERE is_deleted = 0")->fetchAll();

// Fetch Providers
$providers = $pdo->query("SELECT * FROM providers ORDER BY name ASC")->fetchAll();

// Fetch Today's Shipments
$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT p.*, t.name as type_name, prov.name as provider_name 
    FROM purchases p 
    LEFT JOIN qat_types t ON p.qat_type_id = t.id 
    LEFT JOIN providers prov ON p.provider_id = prov.id 
    WHERE p.purchase_date = ? AND p.created_by = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$today, $user_id]);
$shipments = $stmt->fetchAll();
?>

<style>
    :root {
        --wizard-primary: #0d6efd;
        --wizard-bg: #f8fafc;
        --step-inactive: #e2e8f0;
    }

    .wizard-container {
        max-width: 900px;
        margin: 0 auto;
    }

    /* Stepper Styling */
    .stepper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3rem;
        position: relative;
    }

    .stepper::before {
        content: "";
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--step-inactive);
        z-index: 1;
    }

    .step-item {
        position: relative;
        z-index: 2;
        background: var(--wizard-bg);
        padding: 0 15px;
        text-align: center;
        flex: 1;
    }

    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--step-inactive);
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        margin: 0 auto 10px;
        transition: all 0.3s ease;
        border: 4px solid var(--wizard-bg);
    }

    .step-item.active .step-circle {
        background: var(--wizard-primary);
        color: white;
        box-shadow: 0 0 0 5px rgba(13, 110, 253, 0.2);
    }

    .step-item.completed .step-circle {
        background: #198754;
        color: white;
    }

    .step-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #64748b;
    }

    .step-item.active .step-label {
        color: var(--wizard-primary);
    }

    /* Form Card Styling */
    .wizard-card {
        background: white;
        border-radius: 24px;
        border: none;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .wizard-header {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        padding: 2.5rem;
        color: white;
        text-align: center;
    }

    .wizard-body {
        padding: 2.5rem;
    }

    .form-step {
        display: none;
        animation: fadeIn 0.4s ease-out;
    }

    .form-step.active {
        display: block;
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

    .input-group-premium {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 8px 15px;
        border: 2px solid transparent;
        transition: all 0.2s;
    }

    .input-group-premium:focus-within {
        border-color: var(--wizard-primary);
        background: white;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    .input-label-premium {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
        display: block;
    }

    .btn-nav {
        padding: 12px 30px;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.2s;
    }

    .total-badge-premium {
        background: #eff6ff;
        border: 2px dashed #3b82f6;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        margin-top: 1.5rem;
    }

    /* Shipments List Improvement */
    .shipment-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
        margin-bottom: 1rem;
    }

    .shipment-card:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="wizard-container animate__animated animate__fadeIn">
    <!-- Stepper -->
    <div class="stepper">
        <div class="step-item active" id="step-1-indicator">
            <div class="step-circle"><i class="fas fa-user-tag"></i></div>
            <div class="step-label">بيانات المورد</div>
        </div>
        <div class="step-item" id="step-2-indicator">
            <div class="step-circle"><i class="fas fa-balance-scale"></i></div>
            <div class="step-label">الوزن والسعر</div>
        </div>
        <div class="step-item" id="step-3-indicator">
            <div class="step-circle"><i class="fas fa-check-double"></i></div>
            <div class="step-label">تأكيد الإرسال</div>
        </div>
    </div>

    <!-- Wizard Card -->
    <div class="card wizard-card">
        <div class="wizard-header">
            <h3 class="mb-1 fw-bold">تسجيل شروة جديدة</h3>
            <p class="mb-0 opacity-75">يرجى إكمال خطوات التوريد بدقة</p>
        </div>
        <div class="wizard-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success rounded-4 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> تم تسجيل الشحنة بنجاح!
                </div>
            <?php endif; ?>

            <form action="requests/process_sourcing.php" method="POST" enctype="multipart/form-data" id="sourcingWizard">
                <!-- Step 1: Basics -->
                <div class="form-step active" id="step-1">
                    <div class="mb-4">
                        <label class="input-label-premium">تاريخ العملية</label>
                        <input type="date" class="form-control form-control-lg rounded-4" name="purchase_date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="input-label-premium">المورد (الرعية) <span class="text-danger">*</span></label>
                        <div class="mb-2">
                            <input type="text" id="provider_search" class="form-control form-control-sm rounded-3" placeholder="ابحث عن مورد..." onkeyup="filterProviders()">
                        </div>
                        <div class="input-group">
                            <select class="form-select form-select-lg rounded-start-4" name="provider_id" id="provider_select" required size="5">
                                <option value="">-- اختر المورد --</option>
                                <?php foreach ($providers as $prov): ?>
                                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-dark px-4 rounded-end-4" type="button" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="input-label-premium">نوع القات <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg rounded-4" name="type_name" id="type_name_input"
                            list="qat_types_datalist" required placeholder="ابحث أو اكتب اسماً جديداً...">
                        <datalist id="qat_types_datalist">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= htmlspecialchars($t['name']) ?>">
                                <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted mt-2 d-block">سيتم إضافة الأنواع الجديدة تلقائياً للنظام.</small>
                    </div>

                    <div class="text-end mt-5">
                        <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold" onclick="nextStep(2)">
                            التالي <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Weight & Price -->
                <div class="form-step" id="step-2">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="input-label-premium">الوزن (بالكيلو)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 rounded-start-4"><i class="fas fa-weight-hanging text-primary"></i></span>
                                <input type="number" step="0.001" class="form-control form-control-lg border-start-0 rounded-end-4" id="weight_kg" placeholder="0.000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="input-label-premium">الوزن (بالجرام) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 rounded-start-4 text-muted">جرام</span>
                                <input type="number" step="1" class="form-control form-control-lg border-start-0 rounded-end-4 fw-bold" id="weight_grams" name="source_weight_grams" required placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="input-label-premium">سعر الكيلو (المتفق عليه) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-4"><i class="fas fa-coins text-warning"></i></span>
                            <input type="number" step="1" class="form-control form-control-lg border-start-0 rounded-end-4 fw-bold text-primary" id="price_per_kilo" name="price_per_kilo" required placeholder="0">
                        </div>
                    </div>

                    <div class="total-badge-premium animate__animated animate__pulse animate__infinite">
                        <span class="text-secondary small fw-bold">إجمالي المبلغ التقريبي</span>
                        <h1 class="mb-0 fw-bold text-primary"><span id="total_cost_display">0</span> <small class="fs-4">ريال</small></h1>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-4 rounded-pill" onclick="nextStep(1)">
                            <i class="fas fa-arrow-right me-2"></i> السابق
                        </button>
                        <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold" onclick="nextStep(3)">
                            التالي <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Confirmation & Media -->
                <div class="form-step" id="step-3">
                    <div class="mb-4 text-center py-4 bg-light rounded-4">
                        <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3"></i>
                        <h5 class="fw-bold">صورة الشحنة (اختياري)</h5>
                        <p class="text-muted small">يمكنك رفع صورة للنوع أو الميزان لتوثيق الجودة</p>
                        <input type="file" name="media" class="form-control w-75 mx-auto rounded-3" accept="image/*">
                    </div>

                    <div class="alert alert-warning border-0 rounded-4">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">تأكيد المراجعة</h6>
                                <p class="mb-0 small">يرجى التأكد من صحة الوزن والسعر قبل الإرسال، حيث سيتم ترحيل البيانات لمسؤول الاستلام.</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-4 rounded-pill" onclick="nextStep(2)">
                            <i class="fas fa-arrow-right me-2"></i> السابق
                        </button>
                        <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill fw-bold shadow">
                            <i class="fas fa-paper-plane me-2"></i> إتمام وإرسال العملية
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Shipments History Section -->
    <div class="mt-5 mb-5 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <h4 class="fw-bold text-dark mb-0">سجل عمليات اليوم</h4>
            <span class="badge bg-dark rounded-pill px-3"><?= count($shipments) ?> عملية</span>
        </div>

        <div class="row g-3">
            <?php if (empty($shipments)): ?>
                <div class="col-12">
                    <div class="card bg-white border-0 shadow-sm rounded-4 p-5 text-center">
                        <i class="fas fa-history fa-4x text-muted opacity-25 mb-3"></i>
                        <p class="text-muted mb-0">لم يتم تسجيل أي عمليات اليوم بعد.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($shipments as $p): ?>
                    <div class="col-md-6">
                        <div class="card shipment-card bg-white p-3 shadow-sm border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($p['provider_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($p['type_name']) ?> • <?= number_format($p['price_per_kilo']) ?> /كجم</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary"><?= number_format($p['source_weight_grams'] / 1000, 3) ?> <small>كجم</small></div>
                                    <?php if ($p['is_received']): ?>
                                        <span class="badge bg-success-subtle text-success small">تم الاستلام</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning small">قيد الشحن</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="reports.php?report_type=Daily" class="btn btn-link text-decoration-none text-secondary small">
                <i class="fas fa-external-link-alt me-1"></i> عرض تقرير التوريد المفصل
            </a>
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">إضافة مورد جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" id="new_provider_name" class="form-control rounded-3" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">رقم الهاتف <span class="text-danger">*</span></label>
                    <input type="tel" id="new_provider_phone" class="form-control rounded-3" required inputmode="numeric" placeholder="7xxxxxxxxx">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-primary w-100 rounded-pill fw-bold" onclick="saveProvider()">حفظ المورد</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Wizard Navigation Logic
    function nextStep(step) {
        // Validation for Step 1
        if (step === 2) {
            const provider = document.getElementById('provider_select').value;
            const type = document.getElementById('type_name_input').value.trim();
            if (!provider || !type) {
                alert('يرجى اختيار المورد ونوع القات أولاً.');
                return;
            }
        }

        // Validation for Step 2
        if (step === 3) {
            const grams = document.getElementById('weight_grams').value;
            const price = document.getElementById('price_per_kilo').value;
            if (!grams || !price || grams <= 0 || price <= 0) {
                alert('يرجى إدخال الوزن والسعر بشكل صحيح.');
                return;
            }
        }

        // Hide all steps
        document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
        // Show target step
        document.getElementById('step-' + step).classList.add('active');

        // Update Stepper Indicators
        document.querySelectorAll('.step-item').forEach((el, idx) => {
            el.classList.remove('active', 'completed');
            if (idx + 1 < step) el.classList.add('completed');
            if (idx + 1 === step) el.classList.add('active');
        });

        // Scroll top
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Weight & Price Logic
    const kgInput = document.getElementById('weight_kg');
    const gramsInput = document.getElementById('weight_grams');
    const priceInput = document.getElementById('price_per_kilo');
    const totalDisplay = document.getElementById('total_cost_display');

    kgInput.addEventListener('input', function() {
        if (this.value) {
            gramsInput.value = Math.round(parseFloat(this.value) * 1000);
            calculateTotal();
        } else {
            gramsInput.value = '';
            calculateTotal();
        }
    });

    gramsInput.addEventListener('input', function() {
        if (this.value) {
            kgInput.value = (parseFloat(this.value) / 1000).toFixed(3);
            calculateTotal();
        } else {
            kgInput.value = '';
            calculateTotal();
        }
    });

    priceInput.addEventListener('input', calculateTotal);

    function calculateTotal() {
        const kg = parseFloat(kgInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = Math.round(kg * price);
        totalDisplay.textContent = total.toLocaleString();
    }

    function filterProviders() {
        const input = document.getElementById('provider_search');
        const filter = input.value.toLowerCase();
        const select = document.getElementById('provider_select');
        const options = select.getElementsByTagName('option');

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === "") continue;
            const txtValue = options[i].textContent || options[i].innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                options[i].style.display = "";
            } else {
                options[i].style.display = "none";
            }
        }
    }

    // AJAX Provider Add
    function saveProvider() {
        const name = document.getElementById('new_provider_name').value.trim();
        const phone = document.getElementById('new_provider_phone').value.trim();

        if (!name || !phone) return alert('جميع الحقول مطلوبة');
        if (!/^\d{7,15}$/.test(phone)) return alert('رقم الهاتف يجب أن يحتوي على أرقام فقط');

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
                    const select = document.getElementById('provider_select');
                    const option = new Option(data.provider.name, data.provider.id);
                    select.add(option);
                    select.value = data.provider.id;
                    bootstrap.Modal.getInstance(document.getElementById('addProviderModal')).hide();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }
</script>

<?php include 'includes/footer.php'; ?>