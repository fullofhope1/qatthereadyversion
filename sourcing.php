<?php
// sourcing.php - Enhanced Multi-step Wizard
require 'config/db.php';

// Strict Check: Sourcing is NOT for Super Admins
// Must happen BEFORE including header.php to avoid "headers already sent"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    header("Location: purchases.php");
    exit;
}

include 'includes/header.php';

// Fetch Types & Providers via Clean Architecture
$productRepo = new ProductRepository($pdo);
$types = $productRepo->getAllActive();

$providerRepo = new ProviderRepository($pdo);
$providers = $providerRepo->getAll();

// Fetch Today's Shipments
$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];
// Fetch Today's Shipments
$purchaseRepo = new PurchaseRepository($pdo);
$shipments = $purchaseRepo->getTodayShipmentsByUserId($today, $user_id);
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
                        <input type="date" class="form-control form-control-lg rounded-4" name="purchase_date" id="purchase_date" value="<?= date('Y-m-d') ?>" required enterkeyhint="next">
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
                    </div>

                    <div class="mb-4">
                        <label class="input-label-premium">طريقة التعامل (التقنية) <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <input type="radio" class="btn-check" name="unit_type" id="tech_weight" value="weight" checked onchange="toggleInputs()">
                            <label class="btn btn-outline-primary rounded-4 flex-fill py-3 fw-bold" for="tech_weight">
                                <i class="fas fa-balance-scale d-block mb-1"></i> وزن (جرام)
                            </label>

                            <input type="radio" class="btn-check" name="unit_type" id="tech_qabdah" value="قبضة" onchange="toggleInputs()">
                            <label class="btn btn-outline-primary rounded-4 flex-fill py-3 fw-bold" for="tech_qabdah">
                                <i class="fas fa-hand-holding d-block mb-1"></i> عد (قبضة)
                            </label>

                            <input type="radio" class="btn-check" name="unit_type" id="tech_qartas" value="قرطاس" onchange="toggleInputs()">
                            <label class="btn btn-outline-primary rounded-4 flex-fill py-3 fw-bold" for="tech_qartas">
                                <i class="fas fa-box-open d-block mb-1"></i> عد (قرطاس)
                            </label>
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold" id="btn-step-1-next" onclick="nextStep(2)">
                            التالي <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Weight & Price -->
                <div class="form-step" id="step-2">
                    <!-- Weight Section -->
                    <div id="weight_inputs" class="technique-section">
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
                                    <input type="number" step="1" class="form-control form-control-lg border-start-0 rounded-end-4 fw-bold" id="weight_grams" name="source_weight_grams" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="input-label-premium">سعر الكيلو (المتفق عليه) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 rounded-start-4"><i class="fas fa-coins text-warning"></i></span>
                                <input type="number" step="1" class="form-control form-control-lg border-start-0 rounded-end-4 fw-bold text-primary" id="price_per_kilo" name="price_per_kilo" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <!-- Count Section -->
                    <div id="count_inputs" class="technique-section d-none">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="input-label-premium">العدد <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-4"><i class="fas fa-sort-numeric-up text-primary"></i></span>
                                    <input type="number" step="1" class="form-control form-control-lg border-start-0 rounded-end-4 fw-bold" id="source_units" name="source_units" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label-premium">سعر الحبة/البطة <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-4 text-warning">ريال</span>
                                    <input type="number" step="1" class="form-control form-control-lg border-start-0 rounded-end-4 fw-bold" id="price_per_unit" name="price_per_unit" placeholder="0">
                                </div>
                            </div>
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
                        <button type="button" class="btn btn-primary btn-nav w-100" id="btn-step-3-weight" onclick="nextStep(3)">الخطوة التالية <i class="fas fa-arrow-left ms-2"></i></button>
                    </div>
                </div>

                <!-- Step 3: Confirmation & Media -->
                <div class="form-step" id="step-3">
                    <div class="mb-4 text-center py-4 bg-light rounded-4">
                        <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3" id="uploadIcon"></i>
                        <h5 class="fw-bold">صورة الشحنة (اختياري)</h5>
                        <p class="text-muted small">يمكنك رفع صورة للنوع أو الميزان لتوثيق الجودة</p>
                        <input type="file" name="media" id="mediaInput" class="form-control w-75 mx-auto rounded-3" accept="image/*" onchange="previewImage(this)">
                        <!-- Image preview shown after selection -->
                        <div id="imgPreviewBox" class="d-none mt-3">
                            <img id="imgPreview" src="" alt="معاينة الصورة" class="rounded-3 shadow" style="max-height:180px; max-width:100%; border:3px solid #198754;">
                            <div class="mt-2">
                                <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>تم اختيار الصورة</span>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearImage()"><i class="fas fa-times me-1"></i>إزالة</button>
                            </div>
                        </div>
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
                        <div class="card shipment-card bg-white p-3 shadow-sm border-0 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center flex-fill">
                                    <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                                        <i class="fas fa-truck-loading fs-5"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($p['provider_name']) ?></h6>
                                            <span class="text-muted x-small"><?= date('h:i A', strtotime($p['created_at'] ?? 'now')) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="fw-bold text-primary">
                                                <?php if ($p['unit_type'] === 'weight'): ?>
                                                    <?= number_format($p['source_weight_grams'] / 1000, 3) ?> <small class="text-muted">كجم</small>
                                                <?php else: ?>
                                                    <?= number_format($p['source_units']) ?> <small class="text-muted"><?= htmlspecialchars($p['unit_type']) ?></small>
                                                <?php endif; ?>
                                                <span class="mx-1 ms-2 text-secondary opacity-50">|</span>
                                                <span class="text-secondary small"><?= htmlspecialchars($p['type_name']) ?></span>
                                            </div>
                                            <div>
                                                <?php if ($p['is_received']): ?>
                                                    <span class="badge bg-success-subtle text-success rounded-pill border border-success-subtle px-2">تم الاستلام</span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="openDiscountModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['provider_name']) ?>', '<?= htmlspecialchars($p['type_name']) ?>')">
                                                        <i class="fas fa-percent"></i> خصم
                                                    </button>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-warning-subtle text-warning rounded-pill border border-warning-subtle px-2 me-2">قيد الشحن</span>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-light border" onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                                <i class="fas fa-edit text-primary"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-light border" onclick="confirmDelete(<?= $p['id'] ?>)">
                                                                <i class="fas fa-trash-alt text-danger"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
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
                    <label class="small fw-bold mb-1">رقم الهاتف (اختياري)</label>
                    <div class="input-group">
                        <input type="tel" id="new_provider_phone" class="form-control rounded-start-3" inputmode="numeric" placeholder="7xxxxxxxxx" enterkeyhint="done">
                        <button type="button" class="btn btn-warning rounded-end-3" onclick="pickContact('new_provider_phone')">
                            <i class="fas fa-address-book"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-primary w-100 rounded-pill fw-bold" id="btn_save_provider" onclick="saveProvider()">حفظ المورد</button>
            </div>
        </div>
    </div>
</div>

<!-- Discount Modal -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-percent me-2"></i>إضافة خصم على الشحنة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/add_purchase_discount.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="purchase_id" id="discount_purchase_id">
                    
                    <div class="text-center mb-4">
                        <div class="bg-light p-3 rounded-4">
                            <span class="text-muted small d-block mb-1">بيانات الشحنة</span>
                            <h6 id="discount_info" class="fw-bold text-dark mb-0">-</h6>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">مبلغ الخصم (ريال)</label>
                        <input type="number" name="discount_amount" class="form-control form-control-lg rounded-3 border-danger text-center fw-bold" required min="1" placeholder="0">
                        <div class="form-text text-danger x-small mt-2">
                            <i class="fas fa-info-circle me-1"></i> سيتم خصم هذا المبلغ من إجمالي مديونية المورد وتكلفة البضاعة.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">سبب الخصم (اختياري)</label>
                        <textarea name="discount_reason" class="form-control rounded-3" rows="2" placeholder="مثلاً: جودة ضعيفة، وزن ناقص..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-danger btn-lg w-100 rounded-pill fw-bold shadow-sm">تطبيق الخصم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shipment Modal -->

<div class="modal fade" id="editShipmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">تعديل بيانات الشحنة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="requests/update_sourcing.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="purchase_id" id="edit_purchase_id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">المورد</label>
                        <select class="form-select rounded-3" name="provider_id" id="edit_provider_id" required>
                            <?php foreach ($providers as $prov): ?>
                                <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">نوع القات</label>
                        <select class="form-select rounded-3" name="qat_type_id" id="edit_qat_type_id" required>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="edit_weight_section" class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">الوزن (جرام)</label>
                            <input type="number" name="source_weight_grams" id="edit_weight_grams" class="form-control rounded-3" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">السعر / كجم</label>
                            <input type="number" name="price_per_kilo" id="edit_price_per_kilo" class="form-control rounded-3" required>
                        </div>
                    </div>

                    <div id="edit_unit_section" class="row g-2 d-none">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">العدد</label>
                            <input type="number" name="source_units" id="edit_source_units" class="form-control rounded-3">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold mb-1">سعر الحبة</label>
                            <input type="number" name="price_per_unit" id="edit_price_per_unit" class="form-control rounded-3">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-body p-4 text-center">
                <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                <h5 class="fw-bold">هل أنت متأكد؟</h5>
                <p class="text-muted small">سيتم حذف بيانات الشحنة نهائياً من سجل اليوم.</p>
                <form action="requests/delete_sourcing.php" method="POST">
                    <input type="hidden" name="purchase_id" id="delete_purchase_id">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-100 rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold">نعم، احذف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openDiscountModal(id, provider, type) {
        document.getElementById('discount_purchase_id').value = id;
        document.getElementById('discount_info').innerText = provider + " (" + type + ")";
        new bootstrap.Modal(document.getElementById('discountModal')).show();
    }

    function openEditModal(data) {
        document.getElementById('edit_purchase_id').value = data.id;
        document.getElementById('edit_provider_id').value = data.provider_id;
        document.getElementById('edit_qat_type_id').value = data.qat_type_id;

        if (data.unit_type === 'weight') {
            document.getElementById('edit_weight_section').classList.remove('d-none');
            document.getElementById('edit_unit_section').classList.add('d-none');
            document.getElementById('edit_weight_grams').value = data.source_weight_grams;
            document.getElementById('edit_price_per_kilo').value = data.price_per_kilo;
        } else {
            document.getElementById('edit_weight_section').classList.add('d-none');
            document.getElementById('edit_unit_section').classList.remove('d-none');
            document.getElementById('edit_source_units').value = data.source_units;
            document.getElementById('edit_price_per_unit').value = data.price_per_unit;
        }

        new bootstrap.Modal(document.getElementById('editShipmentModal')).show();
    }

    function confirmDelete(id) {
        document.getElementById('delete_purchase_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }

    // Weight & Price Logic
    const kgInput = document.getElementById('weight_kg');
    const gramsInput = document.getElementById('weight_grams');
    const priceInput = document.getElementById('price_per_kilo');

    const unitsInput = document.getElementById('source_units');
    const priceUInput = document.getElementById('price_per_unit');

    const totalDisplay = document.getElementById('total_cost_display');

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
            toggleInputs(); // Ensure correct inputs shown
        }

        // Validation for Step 2
        if (step === 3) {
            const tech = document.querySelector('input[name="unit_type"]:checked').value;
            if (tech === 'weight') {
                const grams = gramsInput.value;
                const price = priceInput.value;
                if (!grams || !price || grams <= 0 || price <= 0) {
                    alert('يرجى إدخال الوزن والسعر بشكل صحيح.');
                    return;
                }
            } else {
                const units = unitsInput.value;
                const priceU = priceUInput.value;
                if (!units || !priceU || units <= 0 || priceU <= 0) {
                    alert('يرجى إدخال العدد وسعر الحبة بشكل صحيح.');
                    return;
                }
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

    function toggleInputs() {
        const tech = document.querySelector('input[name="unit_type"]:checked').value;
        const weightSection = document.getElementById('weight_inputs');
        const countSection = document.getElementById('count_inputs');

        if (tech === 'weight') {
            weightSection.classList.remove('d-none');
            countSection.classList.add('d-none');
        } else {
            weightSection.classList.add('d-none');
            countSection.classList.remove('d-none');
        }
        calculateTotal();
    }

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
    unitsInput.addEventListener('input', calculateTotal);
    priceUInput.addEventListener('input', calculateTotal);

    function calculateTotal() {
        const tech = document.querySelector('input[name="unit_type"]:checked').value;
        let total = 0;

        if (tech === 'weight') {
            const kg = parseFloat(kgInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            total = Math.round(kg * price);
        } else {
            const units = parseInt(unitsInput.value) || 0;
            const priceU = parseFloat(priceUInput.value) || 0;
            total = Math.round(units * priceU);
        }

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

        if (!name) return alert('اسم المورد مطلوب');
        if (phone && !/^\d{7,15}$/.test(phone)) return alert('رقم الهاتف يجب أن يحتوي على أرقام فقط');

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
                    
                    // Clear search and reset list visibility
                    const searchInput = document.getElementById('provider_search');
                    if (searchInput) {
                        searchInput.value = '';
                        filterProviders();
                    }

                    const modalEl = document.getElementById('addProviderModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    
                    // Clear modal fields
                    document.getElementById('new_provider_name').value = '';
                    document.getElementById('new_provider_phone').value = '';
                } else {
                    alert('خطأ: ' + data.message);
                }
            })
            .catch(err => alert('حدث خطأ في الاتصال: ' + err.message));
    }

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
                // Clean the phone number (remove spaces, dashes, etc)
                let phone = contacts[0].tel[0].replace(/[^0-9+]/g, '');
                document.getElementById(fieldId).value = phone;
            }
        } catch (e) {
            console.log('Contact picker cancelled or failed', e);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupFocusNavigation(['weight_kg', 'weight_grams', 'price_per_kilo'], 'btn-step-3-weight');
        setupFocusNavigation(['source_units', 'price_per_unit'], 'btn-step-3-units');
        setupFocusNavigation(['new_provider_name', 'new_provider_phone'], 'btn_save_provider');
    });
    // Image preview for sourcing
    function previewImage(input) {
        const previewBox = document.getElementById('imgPreviewBox');
        const previewImg = document.getElementById('imgPreview');
        const uploadIcon = document.getElementById('uploadIcon');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewBox.classList.remove('d-none');
                if (uploadIcon) uploadIcon.classList.add('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearImage() {
        document.getElementById('mediaInput').value = '';
        document.getElementById('imgPreviewBox').classList.add('d-none');
        const uploadIcon = document.getElementById('uploadIcon');
        if (uploadIcon) uploadIcon.classList.remove('d-none');
    }

</script>

<?php include 'includes/footer.php'; ?>