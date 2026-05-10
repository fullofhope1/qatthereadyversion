<?php
require 'config/db.php';
include 'includes/header.php';

// Fetch Types & Customers via Clean Architecture
$productRepo = new ProductRepository($pdo);
$types = $productRepo->getAllActive();
$customerRepo = new CustomerRepository($pdo);
$customers = $customerRepo->getAllActive();

// Fetch Today's Stock via Clean Architecture
$today = getOperationalDate();
$purchaseRepo = new PurchaseRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);
$unitSalesService = new UnitSalesService($purchaseRepo, $leftoverRepo, $saleRepo);
$saleService = new SaleService($saleRepo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitSalesService);

$todaysStock = $saleService->getTodaysStock($today);

$jsonStock = json_encode($todaysStock);
$jsonCustomers = json_encode($customers);

// --- Receipt Notification Logic ---
$saleReceipt = null;
if (isset($_GET['success']) && isset($_GET['sale_id'])) {
    $saleId = (int)$_GET['sale_id'];
    $saleReceipt = $saleRepo->getById($saleId);
    if ($saleReceipt) {
        $saleReceipt['cust_name'] = $customerRepo->getById($saleReceipt['customer_id'])['name'] ?? 'عميل';
        $saleReceipt['cust_phone'] = $customerRepo->getById($saleReceipt['customer_id'])['phone'] ?? '';
        $saleReceipt['cust_total_debt'] = $customerRepo->getById($saleReceipt['customer_id'])['total_debt'] ?? 0;
        $saleReceipt['type_name'] = $productRepo->getById($saleReceipt['qat_type_id'])['name'] ?? 'قات';
        
        // Notifications Config
        $notifData = require 'config/notifications.php';
        $acc = $notifData['accounts'];
        
        // Template
        $todayAr = getOperationalDate(); 
        $saleAmount = number_format($saleReceipt['price']);
        $totalDebt = number_format($saleReceipt['cust_total_debt']);
        $weightOrUnits = ($saleReceipt['unit_type'] === 'weight') ? ($saleReceipt['weight_grams'] . ' جرام') : ($saleReceipt['quantity_units'] . ' ' . $saleReceipt['unit_type']);
        
        $msg = "إشعار من القادري وماجد: عليك مبلغ " . $saleAmount . " ريال. " . "حساباتنا: جيب/جوالي " . $acc['jawwali'] . " كريمي " . $acc['kuraimi'];
        
        $saleReceipt['wa_url'] = "https://wa.me/" . $saleReceipt['cust_phone'] . "?text=" . str_replace(' ', '%20', $msg);
    }
}
?>

<style>
    .step-container {
        display: none;
        text-align: center;
        animation: fadeIn 0.4s;
    }

    .step-container.active {
        display: block;
    }

    .grid-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
        margin-top: 20px;
    }

    .circle-btn {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: none;
        color: white;
        font-weight: bold;
        font-size: 1.1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
    }

    .circle-btn:active {
        transform: scale(0.95);
    }

    .circle-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    /* Colors */
    .btn-type {
        background: linear-gradient(135deg, #198754, #28a745);
    }

    .btn-provider {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    }

    .btn-cust {
        background: linear-gradient(135deg, #6610f2, #6f42c1);
    }

    .btn-weight {
        background: linear-gradient(135deg, #ffc107, #ffca2c);
        color: #000;
    }

    .btn-price {
        background: linear-gradient(135deg, #fd7e14, #ff9f43);
    }

    .btn-pay {
        background: linear-gradient(135deg, #20c997, #28a745);
    }

    .summary-bar {
        background: #343a40;
        color: white;
        padding: 10px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-around;
        font-size: 0.9rem;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<div class="container-fluid">

    <!-- Summary Bar (Progress) -->
    <div class="row justify-content-center">
        <div class="col-md-10">
            <?php if ($saleReceipt): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-4 animate__animated animate__bounceIn" dir="rtl">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="fw-bold mb-1 text-success"><i class="fas fa-check-circle me-2"></i> تم تسجيل البيع بنجاح!</h4>
                            <p class="mb-0 text-secondary">العميل: <b><?= htmlspecialchars($saleReceipt['cust_name']) ?></b> | المبلغ: <b><?= number_format($saleReceipt['price']) ?> ريال</b></p>
                        </div>
                        <div>
                            <a href="<?= $saleReceipt['wa_url'] ?>" target="_blank" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fab fa-whatsapp me-2"></i> إرسال إشعار للعميل
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="summary-bar" id="summaryBar" dir="rtl">
                <span>النوع: <b id="s_type">-</b></span>
                <span>الرعوي: <b id="s_rawi">-</b></span>
                <span>الزبون: <b id="s_cust">-</b></span>
                <span>الوزن: <b id="s_weight">-</b></span>
                <span>السعر: <b id="s_price">-</b></span>
            </div>

            <!-- Cancel Button (Below Summary) -->
            <div class="text-start mb-3 d-flex gap-2">
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" onclick="location.reload()">
                    إلغاء العملية / جديد (X)
                </button>
                <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" onclick="startStaffConsumption()">
                    <i class="fas fa-utensils me-1"></i> تسجيل تخزينة عمال
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN FORM -->
    <form action="requests/process_sale.php" method="POST" id="saleForm">
        <input type="hidden" name="sale_date" value="<?= getOperationalDate() ?>">
        <input type="hidden" name="qat_type_id" id="i_type">
        <input type="hidden" name="purchase_id" id="i_pid">
        <input type="hidden" name="leftover_id" id="i_leftover">
        <input type="hidden" name="customer_id" id="i_cust">
        <input type="hidden" name="weight_grams" id="i_weight">
        <input type="hidden" name="quantity_units" id="i_units">
        <input type="hidden" name="unit_type" id="i_utype">
        <input type="hidden" name="price" id="i_price">
        <input type="hidden" name="payment_method" id="i_method">

        <!-- Transfer Details -->
        <input type="hidden" name="transfer_sender" id="i_tsender">
        <input type="hidden" name="transfer_receiver" id="i_treceiver">
        <input type="hidden" name="transfer_number" id="i_tnum">
        <input type="hidden" name="transfer_company" id="i_tcompany">

        <input type="hidden" name="debt_type" id="i_dtype">

        <!-- STEP 1: Qat Type -->
        <div id="step1" class="step-container active">
            <h3>اختر النوع</h3>
            <!-- STRICTLY ARABIC BUTTONS from DB (updated by script) -->
            <div class="grid-container">
                <?php foreach ($types as $t): ?>
                    <?php 
                        // Check if this type has any stock in $todaysStock
                        $hasStock = false;
                        foreach ($todaysStock as $item) {
                            if ($item['qat_type_id'] == $t['id'] && $item['type'] !== 'leftover') {
                                if (isset($item['remaining_kg']) && $item['remaining_kg'] > 0) { $hasStock = true; break; }
                                if (isset($item['remaining_units']) && $item['remaining_units'] > 0) { $hasStock = true; break; }
                            }
                        }
                        if (!$hasStock) continue;
                    ?>
                    <button type="button" class="circle-btn btn-type" onclick="nextStep(1, {id: <?= $t['id'] ?>, name: '<?= $t['name'] ?>'})">
                        <?= $t['name'] ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- STEP 2: Provider -->
        <div id="step2" class="step-container">
            <h3>اختر الرعوي</h3>
            <div class="grid-container" id="providerGrid"></div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(1)">عودة</button></div>
        </div>

        <!-- STEP 3: Customer -->
        <div id="step3" class="step-container">
            <h3>من الزبون؟</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-cust" onclick="showCustList()" style="text-align:center;">
                    عميل مستمر
                </button>
                <button type="button" class="circle-btn btn-cust" style="background: #dc3545; text-align:center;" onclick="showAddCust()">
                    عميل جديد
                </button>
            </div>

            <!-- Hidden List for Existing -->
            <div id="custList" class="d-none mt-3 w-50 mx-auto">
                <input type="text" id="cSearch" class="form-control mb-2 p-3 text-end" placeholder="...ابدأ بالكتابة" onkeyup="filterCust()" enterkeyhint="search">
                <div class="list-group text-end" id="cListGroup" style="max-height: 200px; overflow-y:auto;">
                    <!-- JS Populated -->
                </div>
            </div>

            <!-- Hidden Form for New -->
            <div id="newCustForm" class="d-none mt-3 w-50 mx-auto bg-white p-3 rounded shadow text-end">
                <h5>إضافة زبون جديد</h5>
                <input type="text" id="new_name" class="form-control mb-2 text-end" placeholder="الاسم الكامل" enterkeyhint="next">
                <div class="input-group mb-2">
                    <input type="tel" id="new_phone" class="form-control text-end" placeholder="رقم الهاتف" inputmode="numeric" enterkeyhint="done">
                    <button type="button" class="btn btn-warning" onclick="pickContact('new_phone')" title="اختيار من جهات الاتصال">
                        <i class="fas fa-address-book"></i>
                    </button>
                </div>
                <button type="button" class="btn btn-success w-100" id="btn_save_new_cust" onclick="saveNewCust()">حفظ واختيار</button>
            </div>

            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(2)">عودة</button></div>
        </div>

        <!-- STEP 4: Weight -->
        <div id="step4" class="step-container">
            <h3>الوزن</h3>
            <div class="grid-container">
                <!-- Gram Presets -->
                <button type="button" class="circle-btn btn-weight" onclick="nextStep(4, 50)">50 جرام<br>حق 1000</button>
                <button type="button" class="circle-btn btn-weight" onclick="nextStep(4, 100)">100 جرام<br>ثمن</button>
                <button type="button" class="circle-btn btn-weight" onclick="nextStep(4, 250)">250 جرام<br>ربع</button>
                <button type="button" class="circle-btn btn-weight" onclick="nextStep(4, 500)">500 جرام<br>نص</button>
                <button type="button" class="circle-btn btn-weight" onclick="nextStep(4, 1000)">1000 جرام<br>كيلو</button>

                <!-- Manual Input -->
                <button type="button" class="circle-btn bg-dark text-white" onclick="document.getElementById('manualWeight').classList.remove('d-none')">
                    يدوي
                </button>
            </div>

            <div id="manualWeight" class="d-none mt-4 w-75 mx-auto">
                <div class="row g-2">
                    <div class="col-6">
                        <label>جرام</label>
                        <input type="number" id="m_grams" class="form-control p-3 text-center fs-4" placeholder="جرام" oninput="syncWeight('g')" inputmode="numeric" enterkeyhint="next">
                    </div>
                    <div class="col-6">
                        <label>كيلو</label>
                        <input type="number" id="m_kg" class="form-control p-3 text-center fs-4" placeholder="كيلوجرام" oninput="syncWeight('k')" inputmode="numeric" enterkeyhint="done">
                    </div>
                </div>
                <div id="weight_error_msg" class="alert alert-danger d-none mt-2 text-center fw-bold"></div>
                <!-- Confirmation -->
                <button type="button" class="btn btn-primary w-100 mt-3 p-3 fs-5" id="btn_confirm_weight" onclick="confirmManualWeight()">تأكيد الوزن ✓</button>
            </div>

            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(3)">عودة</button></div>
        </div>

        <div id="unit_error_msg" class="alert alert-danger d-none mt-2 text-center fw-bold mx-auto" style="max-width:400px"></div>
        <!-- STEP 4U: Units (Count-based) - dynamically populated -->
        <div id="step4u" class="step-container">
            <h3>كم <span id="unitLabel">حبة</span>؟</h3>
            <p id="unitMaxLabel" class="text-muted small"></p>
            <div class="grid-container" id="unitBtnsGrid"></div>
            <div id="manualUnits" class="d-none mt-4 w-75 mx-auto">
                <input type="number" id="m_units_val" class="form-control p-4 text-center fw-bold" style="font-size:2rem; border-radius:12px;" placeholder="العدد" inputmode="numeric" enterkeyhint="done">
                <button type="button" class="btn btn-primary w-100 mt-3 p-3 fs-5" id="btn_confirm_units" onclick="confirmManualUnits()">تأكيد العدد ✓</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(3)">عودة</button></div>
        </div>

        <!-- STEP 5: Price -->
        <div id="step5" class="step-container">
            <h3>السعر</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-price" onclick="nextStep(5, 30000)">30,000</button>
                <button type="button" class="circle-btn btn-price" onclick="nextStep(5, 35000)">35,000</button>
                <button type="button" class="circle-btn btn-price" onclick="nextStep(5, 40000)">40,000</button>
                <button type="button" class="circle-btn btn-price" onclick="nextStep(5, 45000)">45,000</button>
                <button type="button" class="circle-btn btn-price" onclick="nextStep(5, 100000)">100,000</button>
                <button type="button" class="circle-btn bg-dark text-white" onclick="document.getElementById('manualPrice').classList.remove('d-none')">
                    يدوي
                </button>
            </div>
            <div id="manualPrice" class="d-none mt-3 w-75 mx-auto">
                <input type="number" id="m_price_val" class="form-control p-4 text-center fw-bold" style="font-size:2rem; letter-spacing:2px; border-radius:16px; border:3px solid #fd7e14;" placeholder="0" inputmode="numeric" enterkeyhint="done">
                <div id="price_preview" class="text-center mt-2 fw-bold text-primary fs-5"></div>
                <button type="button" class="btn btn-primary w-100 mt-3 p-3 fs-5" id="btn_confirm_price" onclick="confirmManualPrice()">تأكيد السعر ✓</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(4)">عودة</button></div>
        </div>

        <!-- STEP 6: Payment Method -->
        <div id="step6" class="step-container">
            <h3>طريقة الدفع</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-pay" onclick="finishSale('Cash', null)">نقد</button>
                <button type="button" class="circle-btn btn-pay" style="background: #dc3545;" onclick="nextStep(6, 'Debt')">آجل</button>
                <button type="button" class="circle-btn btn-pay" style="background: #0dcaf0;" onclick="showTransferInputs()">تحويل</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(5)">عودة</button></div>
        </div>

        <!-- STEP 6.5: Transfer Details -->
        <div id="step_transfer" class="step-container">
            <h3>تفاصيل التحويل</h3>
            <div class="w-75 mx-auto text-end">
                <div class="mb-3">
                    <label class="form-label fw-bold">المستلم (الذي استلم)</label>
                    <select id="t_receiver_val" class="form-select text-end p-3" onchange="toggleOtherReceiver(this)">
                        <option value="محمد القادري">محمد القادري</option>
                        <option value="ابن اخيه">ابن اخيه</option>
                        <option value="أخرى">أخرى</option>
                    </select>
                    <input type="text" id="t_receiver_other" class="form-control mt-2 d-none" placeholder="اكتب اسم المستلم" enterkeyhint="next">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الشركة المستخدمة للتحويل</label>
                    <select id="t_company_val" class="form-select text-end p-3" onchange="toggleOtherCompany(this)">
                        <option value="جيب">جيب</option>
                        <option value="جوالي">جوالي</option>
                        <option value="الكريمي">الكريمي</option>
                        <option value="ون كاش">ون كاش</option>
                        <option value="نجم">نجم</option>
                        <option value="ويسترن يونيون">ويسترن يونيون</option>
                        <option value="موني جرام">موني جرام</option>
                        <option value="شرهان">شرهان</option>
                        <option value="الروضة">الروضة</option>
                        <option value="أخرى">أخرى</option>
                    </select>
                    <input type="text" id="t_company_other" class="form-control mt-2 d-none" placeholder="اكتب اسم الشركة" enterkeyhint="next">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">اسم المرسل (الذي حول)</label>
                    <input type="text" id="t_sender_val" class="form-control text-end p-3" enterkeyhint="next">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الحوالة / السند <span class="text-danger">*</span></label>
                    <input type="number" id="t_num_val" class="form-control text-end p-3" required placeholder="رقم الحوالة إلزامي" inputmode="numeric" enterkeyhint="done">
                </div>
                <button type="button" class="btn btn-success w-100 p-3 fs-5" onclick="finishTransfer()">حفظ التحويل ✓</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(6)">عودة</button></div>
        </div>

        <!-- STEP 7: Debt Type -->
        <div id="step7" class="step-container">
            <h3>نوع الدين</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-pay" style="background: #dc3545;" onclick="finishSale('Debt', 'Daily')">يومي</button>
                <button type="button" class="circle-btn btn-pay" style="background: #fd7e14;" onclick="finishSale('Debt', 'Monthly')">شهري</button>
                <button type="button" class="circle-btn btn-pay" style="background: #ffc107;" onclick="finishSale('Debt', 'Yearly')">سنوي</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(6)">عودة</button></div>
        </div>

    </form>
</div>

<!-- AJAX Script -->
<script>
    const allCustomers = <?= $jsonCustomers ?>;
    const stockData = <?= $jsonStock ?>;
    let currentStep = 1;
    let isStaffConsumption = false;

    function startStaffConsumption() {
        isStaffConsumption = true;
        alert('نمط تسجيل تخزينة العمال مفعل. اختر النوع والوزن وسيتم الحفظ تلقائياً كفاقد.');
        goTo(1);
    }

    function nextStep(step, data) {
        // Handle Data Logic
        if (step === 1) { // Type
            document.getElementById('i_type').value = data.id;
            document.getElementById('s_type').innerText = data.name;
            populateProviders(data.id);
        } else if (step === 2) { // Provider/Stock Item
            // Reset both IDs first to ensure mutual exclusivity
            document.getElementById('i_pid').value = "";
            document.getElementById('i_leftover').value = "";

            if (data.type === 'leftover') {
                document.getElementById('i_leftover').value = data.id;
            } else {
                document.getElementById('i_pid').value = data.id;
            }
            
            document.getElementById('s_rawi').innerText = data.name;
            document.getElementById('i_utype').value = data.unit_type;
            window._selectedRemainingUnits = data.remaining_units || 0;
            window._selectedRemainingKg = data.remaining_kg || 0;
            
            // For real-time validation
            window.currentMaxKg = data.remaining_kg || 0;
            window.currentMaxUnits = data.remaining_units || 0;

            goTo(3); // Go to Customer selection
            return;
        } else if (step === 3) { // Customer
            document.getElementById('i_cust').value = data.id;
            document.getElementById('s_cust').innerText = data.name;

            // Route to Weight or Units based on unit_type from Step 2
            const utype = document.getElementById('i_utype').value;
            if (utype === 'weight') {
                goTo(4);
            } else {
                // Build dynamic unit buttons
                const maxU = window._selectedRemainingUnits || 10;
                const grid = document.getElementById('unitBtnsGrid');
                grid.innerHTML = '';
                const btnCount = Math.min(maxU, 8);
                for (let i = 1; i <= btnCount; i++) {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'circle-btn btn-weight';
                    b.textContent = i;
                    b.onclick = () => nextStep(4.5, i);
                    grid.appendChild(b);
                }
                const manBtn = document.createElement('button');
                manBtn.type = 'button';
                manBtn.className = 'circle-btn bg-dark text-white';
                manBtn.textContent = 'يدوي';
                manBtn.onclick = () => document.getElementById('manualUnits').classList.remove('d-none');
                grid.appendChild(manBtn);
                document.getElementById('unitLabel').innerText = utype;
                document.getElementById('unitMaxLabel').innerText = 'المتاح: ' + maxU + ' ' + utype;
                goTo('4u');
            }
            return;
        } else if (step === 4) { // Weight (Finalizing Weight)
            document.getElementById('i_weight').value = data;
            document.getElementById('s_weight').innerText = data + 'g';
            if (isStaffConsumption) {
                finishStaffConsumption(data);
                return;
            }
            goTo(5);
            return;
        } else if (step === 4.5) { // Units (Finalizing Units)
            document.getElementById('i_units').value = data;
            document.getElementById('s_weight').innerText = data + ' (' + document.getElementById('i_utype').value + ')';
            if (isStaffConsumption) {
                finishStaffConsumption(data);
                return;
            }
            goTo(5);
            return;
        } else if (step === 5) { // Price
            document.getElementById('i_price').value = data;
            document.getElementById('s_price').innerText = data;
        } else if (step === 6) { // Debt Init
            document.getElementById('i_method').value = data;
            if (data === 'Debt') {
                goTo(7);
                return;
            }
        }

        goTo(step + 1);
    }

    function showTransferInputs() {
        document.getElementById('i_method').value = 'Internal Transfer';
        goTo('_transfer');
    }

    function toggleOtherReceiver(sel) {
        const otherInput = document.getElementById('t_receiver_other');
        if (otherInput) {
            otherInput.classList.toggle('d-none', sel.value !== 'أخرى');
            if (sel.value !== 'أخرى') otherInput.value = '';
        }
    }

    function toggleOtherCompany(sel) {
        const otherInput = document.getElementById('t_company_other');
        if (otherInput) {
            otherInput.classList.toggle('d-none', sel.value !== 'أخرى');
            if (sel.value !== 'أخرى') otherInput.value = '';
        }
    }

    function finishTransfer() {
        let receiver = document.getElementById('t_receiver_val').value;
        if (receiver === 'أخرى') {
            const otherRec = document.getElementById('t_receiver_other');
            receiver = otherRec ? otherRec.value.trim() : '';
            if (!receiver) return alert('يرجى كتابة اسم المستلم');
        }
        const sender = document.getElementById('t_sender_val').value;
        const num = document.getElementById('t_num_val').value;
        let company = document.getElementById('t_company_val').value;
        if (company === 'أخرى') {
            const otherInput = document.getElementById('t_company_other');
            company = otherInput ? otherInput.value.trim() : '';
            if (!company) return alert('يرجى كتابة اسم الشركة');
        }
        if (!sender) return alert('اسم المرسل مطلوب');
        if (!num) return alert('رقم الحوالة مطلوب قبل القبول');

        document.getElementById('i_tsender').value = sender;
        document.getElementById('i_treceiver').value = receiver;
        document.getElementById('i_tnum').value = num;
        document.getElementById('i_tcompany').value = company;

        finishSale('Internal Transfer', null);
    }

    function finishSale(method, debtType) {
        if (method) document.getElementById('i_method').value = method;
        if (debtType) document.getElementById('i_dtype').value = debtType;

        // Validation for Debt
        const m = document.getElementById('i_method').value;
        const c = document.getElementById('i_cust').value;
        if (m === 'Debt' && !c) {
            alert('يرجى اختيار الزبون أولاً لعملية الآجل');
            goTo(3);
            return;
        }

        document.getElementById('saleForm').submit();
    }

    function finishStaffConsumption(amount) {
        if (!confirm(`هل تريد تأكيد تسجيل ${amount} ${document.getElementById('i_utype').value === 'weight' ? 'جرام' : document.getElementById('i_utype').value} كتخزينة عمال؟`)) return;
        
        const formData = new FormData();
        formData.append('purchase_id', document.getElementById('i_pid').value);
        formData.append('amount', document.getElementById('i_utype').value === 'weight' ? (amount / 1000) : amount);
        formData.append('unit_type', document.getElementById('i_utype').value);
        formData.append('reason', 'Staff_Consumption');
        formData.append('notes', 'تخزينة عمال من واجهة المبيعات');

        fetch('requests/manual_waste.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('تم تسجيل التخزينة بنجاح');
                location.reload();
            } else {
                alert('خطأ: ' + data.error);
            }
        });
    }

    function goTo(step) {
        document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
        document.getElementById('step' + step).classList.add('active');
        currentStep = step;
    }

    function backStep(targetStep) {
        if (targetStep === 6 && currentStep === '_transfer') {
            goTo(6);
            return;
        }
        const utype = document.getElementById('i_utype').value;
        if (targetStep === 4 && utype !== 'weight') {
            goTo('4u');
            return;
        }
        goTo(targetStep);
    }

    // --- Helpers ---
    function populateProviders(typeId) {
        const grid = document.getElementById('providerGrid');
        grid.innerHTML = '';

        // --- Admin-Only Technique Restriction ---
        const userRole = '<?= $_SESSION['role'] ?>';
        const isAdmin = ['admin', 'super_admin'].includes(userRole);

        const providers = stockData.filter(i => {
            if (i.qat_type_id != typeId) return false;
            if (i.type === 'leftover') return false; // Hide leftovers from main sales page
            // Unit-based products are now visible to all staff
            return true;
        });

        if (providers.length === 0) {
            grid.innerHTML = '<div class="alert alert-warning w-100">لا يوجد مخزون متاح لهذا النوع اليوم</div>';
        } else {
            providers.forEach(p => {
                const btn = document.createElement('button');
                btn.className = 'circle-btn btn-provider';

                // Inventory Check
                let remaining = 0;
                let unitText = '';
                if (p.unit_type === 'weight') {
                    remaining = parseFloat(p.remaining_kg);
                    unitText = ' كجم';
                } else {
                    remaining = parseInt(p.remaining_units);
                    unitText = ' ' + p.unit_type;
                }

                const isSoldOut = remaining <= 0;

                let label = `<b>${p.provider_name}</b>`;
                if (p.status_label) {
                    const badgeClass = p.type === 'leftover' ? 'bg-warning text-dark' : 'bg-light text-dark';
                    label += `<br><span class="badge ${badgeClass} mb-1" style="font-size:0.7rem">${p.status_label}</span>`;
                }

                if (isSoldOut) {
                    label += '<br><small class="text-danger">(نفذ)</small>';
                    btn.style.opacity = '0.6';
                    btn.disabled = true;
                } else {
                    label += `<br><small>${remaining}${unitText}</small>`;
                }

                btn.innerHTML = label;
                btn.type = 'button';

                if (!isSoldOut) {
                    btn.onclick = () => nextStep(2, {
                        id: p.id,
                        type: p.type, // purchase or leftover
                        name: p.provider_name,
                        unit_type: p.unit_type,
                        remaining_units: p.remaining_units || 0,
                        remaining_kg: p.remaining_kg || 0
                    });
                }

                grid.appendChild(btn);
            });
        }
    }

    // Weight Sync Logic
    function syncWeight(source) {
        const grams = document.getElementById('m_grams');
        const kg = document.getElementById('m_kg');

        if (source === 'g') {
            kg.value = (grams.value / 1000).toFixed(3);
        } else {
            grams.value = (kg.value * 1000).toFixed(0);
        }
        
        // Immediate Validation
        const purchaseId = document.getElementById('i_pid').value;
        const leftoverId = document.getElementById('i_leftover').value;
        if (purchaseId || leftoverId) {
            const errBox = document.getElementById('weight_error_msg');
            const diffGrams = grams.value - (window.currentMaxKg * 1000);
            if (diffGrams > 0) {
                 errBox.innerHTML = '⚠️ الكمية غير متاحة! المتاح: <b>' + window.currentMaxKg + ' كجم</b>';
                 errBox.classList.remove('d-none');
                 document.getElementById('btn_confirm_weight').disabled = true;
            } else {
                 errBox.classList.add('d-none');
                 document.getElementById('btn_confirm_weight').disabled = false;
            }
        }
    }

    function confirmManualWeight() {
        const grams = parseFloat(document.getElementById('m_grams').value);
        if (!grams || grams <= 0) {
            alert('الرجاء إدخال الوزن');
            return;
        }
        
        if (document.getElementById('btn_confirm_weight').disabled) {
            return;
        }

        // Inventory check BEFORE going to step 5
        const purchaseId = document.getElementById('i_pid').value;
        const leftoverId = document.getElementById('i_leftover').value;
        const errBox = document.getElementById('weight_error_msg');
        errBox.classList.add('d-none');

        if (purchaseId || leftoverId) {
            // Check available stock via AJAX
            const idParam = purchaseId ? 'purchase_id=' + purchaseId : 'leftover_id=' + leftoverId;
            fetch('requests/check_stock.php?' + idParam + '&grams=' + grams)
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        errBox.innerHTML = '⚠️ الكمية غير متاحة! المتاح: <b>' + data.available_kg + ' كجم</b> — طلبت: <b>' + (grams / 1000).toFixed(3) + ' كجم</b>';
                        errBox.classList.remove('d-none');
                        errBox.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        // Clear check with visual confirmation
                        if (confirm(`تأكيد الوزن: ${(grams / 1000).toFixed(3)} كجم؟`)) {
                            errBox.classList.add('d-none');
                            nextStep(4, grams);
                        }
                    }
                })
                .catch(() => {
                    if (confirm(`تأكيد الوزن: ${(grams / 1000).toFixed(3)} كجم؟`)) {
                        nextStep(4, grams);
                    }
                });
        } else {
            nextStep(4, grams);
        }
    }

    function syncUnits() {
        const units = parseInt(document.getElementById('m_units_val').value);
        const purchaseId = document.getElementById('i_pid').value;
        const leftoverId = document.getElementById('i_leftover').value;
        if (purchaseId || leftoverId) {
            const errBox = document.getElementById('unit_error_msg');
            const unitType = document.getElementById('i_utype').value;
            if (units > window.currentMaxUnits) {
                 errBox.innerHTML = '⚠️ الكمية غير متاحة! المتاح: <b>' + window.currentMaxUnits + ' ' + unitType + '</b>';
                 errBox.classList.remove('d-none');
                 document.getElementById('btn_confirm_units').disabled = true;
            } else {
                 errBox.classList.add('d-none');
                 document.getElementById('btn_confirm_units').disabled = false;
            }
        }
    }

    // Add listener dynamically for unit input inside DOMContentLoaded or directly
    document.addEventListener('DOMContentLoaded', () => {
        const unitInput = document.getElementById('m_units_val');
        if (unitInput) {
            unitInput.addEventListener('input', syncUnits);
        }
    });

    function confirmManualUnits() {
        const units = parseInt(document.getElementById('m_units_val').value);
        if (!units || units <= 0) {
            alert('الرجاء إدخال العدد');
            return;
        }
        if (document.getElementById('btn_confirm_units').disabled) {
            return;
        }
        const purchaseId = document.getElementById('i_pid').value;
        const leftoverId = document.getElementById('i_leftover').value;
        const unitType = document.getElementById('i_utype').value;
        const errBox = document.getElementById('unit_error_msg');
        errBox.classList.add('d-none');

        if (purchaseId || leftoverId) {
            const idParam = purchaseId ? 'purchase_id=' + purchaseId : 'leftover_id=' + leftoverId;
            fetch('requests/check_stock.php?' + idParam + '&units=' + units + '&unit_type=' + encodeURIComponent(unitType))
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        errBox.innerHTML = '⚠️ الكمية غير متاحة! المتاح: <b>' + data.available_units + ' ' + unitType + '</b> — طلبت: <b>' + units + '</b>';
                        errBox.classList.remove('d-none');
                        document.getElementById('step4u').appendChild(errBox);
                        errBox.scrollIntoView({behavior:'smooth'});
                    } else {
                        errBox.classList.add('d-none');
                        nextStep(4.5, units);
                    }
                })
                .catch(() => nextStep(4.5, units));
        } else {
            nextStep(4.5, units);
        }
    }

    // Confirm manual price
    function confirmManualPrice() {
        const price = parseFloat(document.getElementById('m_price_val').value);
        if (!price || price <= 0) {
            alert('الرجاء إدخال السعر');
            return;
        }
        nextStep(5, price);
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

    document.addEventListener('DOMContentLoaded', function() {
        // Setup focus logic for various forms
        setupFocusNavigation(['new_name', 'new_phone'], 'btn_save_new_cust');
        setupFocusNavigation(['m_grams', 'm_kg'], 'btn_confirm_weight');
        setupFocusNavigation(['m_units_val'], 'btn_confirm_units');
        setupFocusNavigation(['m_price_val'], 'btn_confirm_price');
        setupFocusNavigation(['t_sender', 't_num'], 'btn_finish_transfer');
    });

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

    // Customer UI Helpers
    function showAddCust() {
        document.getElementById('custList').classList.add('d-none');
        const tf = document.getElementById('tayyarForm');
        if (tf) tf.classList.add('d-none');
        document.getElementById('newCustForm').classList.remove('d-none');
        const ni = document.getElementById('new_name');
        if(ni) ni.focus();
    }

    function showCustList() {
        document.getElementById('newCustForm').classList.add('d-none');
        document.getElementById('tayyarForm') && document.getElementById('tayyarForm').classList.add('d-none');
        document.getElementById('custList').classList.remove('d-none');
        const cs = document.getElementById('cSearch');
        if(cs) cs.focus();
        renderCustList(allCustomers);
    }

    // Format price as user types
    document.addEventListener('DOMContentLoaded', function() {
        const priceInput = document.getElementById('m_price_val');
        const pricePreview = document.getElementById('price_preview');
        if (priceInput && pricePreview) {
            priceInput.addEventListener('input', function() {
                const val = parseFloat(this.value);
                pricePreview.textContent = val > 0 ? val.toLocaleString('ar-YE') + ' ريال' : '';
            });
        }
    });

    // Contact Picker API
    async function pickContact(fieldId) {
        if (!('contacts' in navigator && 'ContactsManager' in window)) {
            alert('هذه الميزة غير مدعومة على هذا الجهاز.');
            return;
        }
        try {
            const contacts = await navigator.contacts.select(['tel'], { multiple: false });
            if (contacts && contacts.length > 0 && contacts[0].tel && contacts[0].tel.length > 0) {
                let phone = contacts[0].tel[0].replace(/[^0-9]/g, '');
                document.getElementById(fieldId).value = phone;
            }
        } catch (e) {
            console.log('Contact picker cancelled or failed', e);
        }
    }

    // Customer Logic
    function filterCust() {
        const term = document.getElementById('cSearch').value.toLowerCase();
        const filtered = allCustomers.filter(c => {
            const nameMatch = c.name && c.name.toLowerCase().includes(term);
            const phoneMatch = c.phone && String(c.phone).includes(term);
            return nameMatch || phoneMatch;
        });
        renderCustList(filtered);
    }

    function renderCustList(list) {
        const div = document.getElementById('cListGroup');
        div.innerHTML = '';
        list.forEach(c => {
            const a = document.createElement('a');
            a.className = 'list-group-item list-group-item-action text-end';
            a.style.cursor = 'pointer';
            
            const debtVal = parseFloat(c.total_debt || 0);
            const debtText = debtVal > 0 
                ? `<br><span class="badge bg-danger-subtle text-danger border border-danger-subtle">مديون: ${debtVal.toLocaleString()} ريال</span>` 
                : '';
                
            a.innerHTML = `<b>${c.name}</b> <small class="text-muted">${c.phone || ''}</small>${debtText}`;
            a.onclick = () => nextStep(3, { id: c.id, name: c.name });
            div.appendChild(a);
        });
    }

    function showTayyarPrompt() {
        document.getElementById('custList').classList.add('d-none');
        document.getElementById('newCustForm').classList.add('d-none');
        const tf = document.getElementById('tayyarForm');
        if (tf) tf.classList.remove('d-none');
    }

    function confirmTayyar() {
        const name = document.getElementById('t_name').value;
        if (!name) return alert("الاسم مطلوب");
        document.getElementById('new_name').value = name;
        document.getElementById('new_phone').value = '';
        saveNewCust();
    }

    function saveNewCust() {
        const name = document.getElementById('new_name').value.trim();
        const phone = document.getElementById('new_phone').value.trim();

        if (!name) return alert("الاسم مطلوب");
        if (phone && !/^\d{7,15}$/.test(phone)) return alert("رقم الهاتف غير صحيح - يجب أن يكون أرقاماً فقط (7-15 رقم)");

        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);

        fetch('requests/add_customer_ajax.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    allCustomers.push({ id: data.id, name: name, phone: phone });
                    nextStep(3, { id: data.id, name: name });
                } else {
                    alert("خطأ: " + (data.error || "فشل في إضافة الزبون"));
                }
            });
    }
</script>

<!-- Quick Report Link -->
<div class="text-center mt-4 mb-5 no-print">
    <a href="reports.php?report_type=Daily" class="btn btn-outline-secondary">
        <i class="fas fa-file-invoice me-2"></i> تقرير اليوم المفصل
    </a>
</div>

<?php include 'includes/footer.php'; ?>