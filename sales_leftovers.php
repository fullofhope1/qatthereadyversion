<?php
require_once 'config/db.php';
require_once 'includes/Autoloader.php';
include 'includes/header.php';

// Initialize Repositories
$customerRepo = new CustomerRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$productRepo = new ProductRepository($pdo);

// Initialize Services
$unitSalesService = new UnitSalesService($purchaseRepo, $leftoverRepo, $saleRepo);
$saleService = new SaleService($saleRepo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitSalesService);

$customers = $customerRepo->getAllActive();
$types = $productRepo->getAllActive();

// Unified stock calculation via Service
$leftoverStocks = $saleService->getAvailableLeftoverStock();

$jsonStocks = json_encode($leftoverStocks);
$jsonCustomers = json_encode($customers);
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
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .circle-btn:active {
        transform: scale(0.95);
    }

    .circle-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

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
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="text-center mb-4 mt-3 fw-bold text-dark"><i class="fas fa-recycle text-warning me-2"></i> بيع البقايا</h2>

            <div class="summary-bar" id="summaryBar" dir="rtl">
                <span>النوع: <b id="s_type">-</b></span>
                <span>الرعوي: <b id="s_rawi">-</b></span>
                <span>الزبون: <b id="s_cust">-</b></span>
                <span>الكمية: <b id="s_weight">-</b></span>
                <span>السعر: <b id="s_price">-</b></span>
            </div>

            <div class="text-start mb-3">
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" onclick="location.reload()">إلغاء العملية / جديد (X)</button>
            </div>
        </div>
    </div>

    <form action="requests/process_sale.php" method="POST" id="saleForm">
        <input type="hidden" name="sale_date" value="<?= date('Y-m-d') ?>">
        <input type="hidden" name="qat_type_id" id="i_type">
        <input type="hidden" name="purchase_id" id="i_pid">
        <input type="hidden" name="leftover_id" id="i_lid">
        <input type="hidden" name="qat_status" id="i_status" value="">
        <input type="hidden" name="source_page" value="leftovers">
        <input type="hidden" name="customer_id" id="i_cust">
        <input type="hidden" name="weight_grams" id="i_weight" value="0">
        <input type="hidden" name="quantity_units" id="i_units" value="0">
        <input type="hidden" name="unit_type" id="i_unit_type" value="weight">
        <input type="hidden" name="price" id="i_price">
        <input type="hidden" name="payment_method" id="i_method">
        <input type="hidden" name="debt_type" id="i_dtype">

        <!-- STEP 1: Qat Type -->
        <div id="step1" class="step-container active">
            <h3>اختر النوع</h3>
            <div class="grid-container">
                <?php foreach ($types as $t): ?>
                    <button type="button" class="circle-btn btn-type" onclick="nextStep(1, {id: <?= $t['id'] ?>, name: '<?= addslashes($t['name']) ?>'})">
                        <?= $t['name'] ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- STEP 2: Provider (Providers from leftovers) -->
        <div id="step2" class="step-container">
            <h3>اختر الرعوي / الدفعة</h3>
            <div class="grid-container" id="providerGrid"></div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(1)">عودة</button></div>
        </div>

        <!-- STEP 3: Customer -->
        <div id="step3" class="step-container">
            <h3>من الزبون؟</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-cust" onclick="showCustList()" style="text-align:center;">عميل مستمر</button>
                <button type="button" class="circle-btn btn-cust" style="background: #dc3545; text-align:center;" onclick="showAddCust()">عميل جديد</button>
            </div>
            <div id="custList" class="d-none mt-3 w-50 mx-auto">
                <input type="text" id="cSearch" class="form-control mb-2 p-3 text-end" placeholder="...ابدأ بالكتابة" onkeyup="filterCust()" enterkeyhint="search">
                <div class="list-group text-end" id="cListGroup" style="max-height: 200px; overflow-y:auto;"></div>
            </div>
            <div id="newCustForm" class="d-none mt-3 w-50 mx-auto bg-white p-3 rounded shadow text-end">
                <h5>إضافة زبون جديد</h5>
                <input type="text" id="new_name" class="form-control mb-2 text-end" placeholder="الاسم الكامل" enterkeyhint="next">
                <div class="input-group mb-2">
                    <input type="tel" id="new_phone" class="form-control text-end" placeholder="رقم الهاتف" inputmode="numeric" enterkeyhint="done">
                    <button type="button" class="btn btn-outline-secondary" onclick="pickContact('new_phone')" title="اختيار من جهات الاتصال"><i class="fas fa-address-book"></i></button>
                </div>
                <button type="button" class="btn btn-success w-100" id="btn_save_new_cust" onclick="saveNewCust()">حفظ واختيار</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(2)">عودة</button></div>
        </div>

        <!-- STEP 4: Weight (for weight-based stock) -->
        <div id="step4_weight" class="step-container">
            <h3>الوزن</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-weight" onclick="setWeight(50)">50g</button>
                <button type="button" class="circle-btn btn-weight" onclick="setWeight(100)">100g</button>
                <button type="button" class="circle-btn btn-weight" onclick="setWeight(250)">250g</button>
                <button type="button" class="circle-btn btn-weight" onclick="setWeight(500)">500g</button>
                <button type="button" class="circle-btn btn-weight" onclick="setWeight(1000)">1000g</button>
                <button type="button" class="circle-btn bg-dark text-white" onclick="document.getElementById('manualWeight').classList.remove('d-none')">يدوي</button>
            </div>
            <div id="manualWeight" class="d-none mt-3 w-75 mx-auto">
                <input type="number" id="m_weight_val" class="form-control p-4 text-center fw-bold" style="font-size:2rem; border-radius:12px; border:3px solid #ffc107;" placeholder="جرام" inputmode="numeric" enterkeyhint="done">
                <div id="leftover_weight_error" class="alert alert-danger d-none mt-2 fw-bold text-center"></div>
                <button type="button" class="btn btn-primary w-100 mt-3 p-3 fs-5" id="btn_confirm_weight" onclick="confirmLeftoverWeight()">تأكيد الوزن ✓</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(3)">عودة</button></div>
        </div>

        <!-- STEP 4 (Units): for Qabdah / Qartas stocks -->
        <div id="step4_units" class="step-container">
            <h3 id="step4_units_title">العدد</h3>
            <p id="step4_units_max" class="text-muted"></p>
            <div class="grid-container" id="unitBtnsGrid"></div>
            <div id="manualUnits" class="d-none mt-3 w-75 mx-auto">
                <input type="number" id="m_units_val" class="form-control p-4 text-center fw-bold" style="font-size:2rem; border-radius:12px;" min="1" placeholder="العدد" inputmode="numeric" enterkeyhint="done">
                <div id="leftover_units_error" class="alert alert-danger d-none mt-2 fw-bold text-center"></div>
                <button type="button" class="btn btn-primary w-100 mt-3 p-3 fs-5" id="btn_confirm_units" onclick="confirmLeftoverUnits()">تأكيد العدد ✓</button>
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
                <button type="button" class="circle-btn bg-dark text-white" onclick="document.getElementById('manualPrice').classList.remove('d-none')">يدوي</button>
            </div>
            <div id="manualPrice" class="d-none mt-3 w-75 mx-auto">
                <input type="number" id="m_price_val" class="form-control p-4 text-center fw-bold" style="font-size:2rem; letter-spacing:2px; border-radius:16px; border:3px solid #fd7e14;" placeholder="0" inputmode="numeric" enterkeyhint="done">
                <div id="lo_price_preview" class="text-center mt-2 fw-bold text-primary fs-5"></div>
                <button type="button" class="btn btn-primary w-100 mt-3 p-3 fs-5" id="btn_confirm_price" onclick="confirmLeftoverPrice()">تأكيد السعر ✓</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(4)">عودة</button></div>
        </div>

        <!-- STEP 6: Payment -->
        <div id="step6" class="step-container">
            <h3>طريقة الدفع</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-pay" onclick="finishSale('Cash', null)">نقد</button>
                <button type="button" class="circle-btn btn-pay" style="background:#0d6efd;" onclick="goTo('_lo_transfer')">حوالة</button>
                <button type="button" class="circle-btn btn-pay" style="background: #dc3545;" onclick="nextStep(6, 'Debt')">آجل</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(5)">عودة</button></div>
        </div>

        <!-- STEP 6T: Transfer Details (Leftover) -->
        <div id="step_lo_transfer" class="step-container">
            <h3>تفاصيل التحويل</h3>
            <div class="w-75 mx-auto text-end">
                <div class="mb-3">
                    <label class="form-label fw-bold">المستلم</label>
                    <select id="lo_receiver_val" class="form-select text-end p-3" onchange="toggleLoOtherReceiver(this)">
                        <option value="محمد القادري">محمد القادري</option>
                        <option value="ابن اخيه">ابن اخيه</option>
                        <option value="أخرى">أخرى</option>
                    </select>
                    <input type="text" id="lo_receiver_other" class="form-control mt-2 d-none" placeholder="اكتب اسم المستلم" enterkeyhint="next">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">شركة التحويل</label>
                    <select id="lo_company_val" class="form-select text-end p-3" onchange="toggleLoOtherCompany(this)">
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
                    <input type="text" id="lo_company_other" class="form-control mt-2 d-none" placeholder="اكتب اسم الشركة" enterkeyhint="next">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">اسم المرسل</label>
                    <input type="text" id="lo_sender_val" class="form-control text-end p-3" enterkeyhint="next">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الحوالة <span class="text-danger">*</span></label>
                    <input type="number" id="lo_num_val" class="form-control text-end p-3" placeholder="إلزامي" inputmode="numeric" enterkeyhint="done">
                </div>
                <button type="button" class="btn btn-success w-100 p-3 fs-5" id="btn_finish_transfer" onclick="finishLoTransfer()">حفظ التحويل ✓</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(6)">عودة</button></div>
        </div>

        <!-- STEP 7: Debt -->
        <div id="step7" class="step-container">
            <h3>نوع الدين</h3>
            <div class="grid-container">
                <button type="button" class="circle-btn btn-pay" style="background: #dc3545;" onclick="finishSale('Debt', 'Daily')">يومي</button>
                <button type="button" class="circle-btn btn-pay" style="background: #fd7e14;" onclick="finishSale('Debt', 'Monthly')">شهري</button>
            </div>
            <div class="mt-4"><button type="button" class="btn btn-secondary" onclick="backStep(6)">عودة</button></div>
        </div>
    </form>
</div>

<script>
    const allStocks = <?= $jsonStocks ?>;
    const allCustomers = <?= $jsonCustomers ?>;
    let currentStep = 1;
    let selectedStock = null; // Holds the selected provider/stock object

    function nextStep(step, data) {
        if (step === 1) { // Type selected
            document.getElementById('i_type').value = data.id;
            document.getElementById('s_type').innerText = data.name;
            populateProviders(data.id);
        } else if (step === 2) { // Provider/stock selected (Consolidated: Everything from leftovers table)
            document.getElementById('i_lid').value = data.id;
            document.getElementById('i_pid').value = ''; // Surplus is in leftovers table now, not purchases
            document.getElementById('i_status').value = 'Leftover';
            document.getElementById('s_rawi').innerText = data.name;
            selectedStock = data; // Save full stock object
        } else if (step === 3) { // Customer selected
            document.getElementById('i_cust').value = data.id;
            document.getElementById('s_cust').innerText = data.name;
        } else if (step === 5) { // Price
            document.getElementById('i_price').value = data;
            document.getElementById('s_price').innerText = data;
        } else if (step === 6) { // Payment Init
            document.getElementById('i_method').value = data;
            if (data === 'Debt') {
                goTo(7);
                return;
            }
        }
        goTo(step + 1);
    }

    function setWeight(grams) {
        document.getElementById('i_weight').value = grams;
        document.getElementById('i_units').value = 0;
        document.getElementById('i_unit_type').value = 'weight';
        document.getElementById('s_weight').innerText = grams + ' جرام';
        goTo(5);
    }

    function setUnits(count) {
        count = parseInt(count);
        if (!count || count < 1) return;
        const ut = selectedStock ? (selectedStock.unit_type || 'وحدة') : 'وحدة';
        document.getElementById('i_units').value = count;
        document.getElementById('i_weight').value = 0;
        document.getElementById('i_unit_type').value = ut;
        document.getElementById('s_weight').innerText = ut + ' × ' + count;
        goTo(5);
    }

    function populateProviders(typeId) {
        const grid = document.getElementById('providerGrid');
        grid.innerHTML = '';
        const providers = allStocks.filter(s => s.qat_type_id == typeId);

        if (providers.length === 0) {
            grid.innerHTML = '<div class="alert alert-warning w-100">لا توجد بقايا لهذا النوع حالياً</div>';
        } else {
            providers.forEach(p => {
                const btn = document.createElement('button');
                btn.className = 'circle-btn btn-provider';
                btn.type = 'button';

                // Show unit count for unit-based stocks, weight for weight-based
                const ut = p.unit_type || 'weight';
                let qtyLabel;
                if (ut !== 'weight') {
                    qtyLabel = `<small>${ut} × ${p.remaining_units}</small>`;
                } else {
                    qtyLabel = `<small>${p.remaining_kg} كجم</small>`;
                }

                btn.innerHTML = `<span>${p.provider_name}</span><br><small class="badge bg-light text-dark text-wrap">${p.sale_date || p.source_date}</small><br>${qtyLabel}`;
                btn.onclick = () => nextStep(2, {
                    id: p.id,
                    name: p.provider_name,
                    type: p.type,
                    unit_type: ut,
                    remaining_units: p.remaining_units,
                    remaining_kg: p.remaining_kg
                });
                grid.appendChild(btn);
            });
        }
    }

    function goTo(step) {
        // When going to step 4, decide which step4 variant to show
        if (step === 4) {
            const ut = selectedStock ? (selectedStock.unit_type || 'weight') : 'weight';
            const isUnits = (ut !== 'weight');

            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));

            if (isUnits) {
                // Build unit buttons dynamically based on remaining stock
                const maxU = selectedStock.remaining_units || 10;
                const grid = document.getElementById('unitBtnsGrid');
                grid.innerHTML = '';
                const btnCount = Math.min(maxU, 6);
                for (let i = 1; i <= btnCount; i++) {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'circle-btn btn-weight';
                    b.textContent = i;
                    b.onclick = () => setUnits(i);
                    grid.appendChild(b);
                }
                // Add manual entry button
                const manBtn = document.createElement('button');
                manBtn.type = 'button';
                manBtn.className = 'circle-btn bg-dark text-white';
                manBtn.textContent = 'يدوي';
                manBtn.onclick = () => document.getElementById('manualUnits').classList.remove('d-none');
                grid.appendChild(manBtn);

                document.getElementById('step4_units_title').innerText = 'كم ' + ut + '؟';
                document.getElementById('step4_units_max').innerText = 'المتاح: ' + maxU + ' ' + ut;
                document.getElementById('step4_units').classList.add('active');
                currentStep = 4;
            } else {
                document.getElementById('step4_weight').classList.add('active');
                currentStep = 4;
            }
            return;
        }

        document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
        const stepId = typeof step === 'string' ? 'step' + step : 'step' + step;
        const target = document.getElementById(stepId);
        if (target) target.classList.add('active');
        currentStep = step;
    }

    function backStep(from) {
        // If backing from step5, go back to the correct step4 variant
        if (from === 4) {
            goTo(4);
        } else {
            goTo(from);
        }
    }

    function finishSale(method, debtType) {
        if (method) document.getElementById('i_method').value = method;
        if (debtType) document.getElementById('i_dtype').value = debtType;
        document.getElementById('saleForm').submit();
    }

    // Customer Helpers
    function showCustList() {
        document.getElementById('custList').classList.remove('d-none');
        document.getElementById('newCustForm').classList.add('d-none');
        renderCustList(allCustomers);
    }

    function filterCust() {
        const term = document.getElementById('cSearch').value.toLowerCase();
        const filtered = allCustomers.filter(c => c.name.toLowerCase().includes(term) || (c.phone && c.phone.includes(term)));
        renderCustList(filtered);
    }

    function renderCustList(list) {
        const div = document.getElementById('cListGroup');
        div.innerHTML = '';
        list.forEach(c => {
            const a = document.createElement('a');
            a.className = 'list-group-item list-group-item-action text-end';
            a.innerHTML = `<b>${c.name}</b> ${c.phone ? '<small>('+c.phone+')</small>' : ''}`;
            a.onclick = () => nextStep(3, {
                id: c.id,
                name: c.name
            });
            a.style = "cursor:pointer";
            div.appendChild(a);
        });
    }

    function showTayyarPrompt() {
        document.getElementById('custList').classList.add('d-none');
        document.getElementById('newCustForm').classList.add('d-none');
        document.getElementById('tayyarForm').classList.remove('d-none');
    }

    function confirmTayyar() {
        const name = document.getElementById('t_name').value;
        if (!name) return alert("الاسم مطلوب");
        document.getElementById('new_name').value = name;
        document.getElementById('new_phone').value = '';
        saveNewCust();
    }

    function showAddCust() {
        document.getElementById('custList').classList.add('d-none');
        document.getElementById('newCustForm').classList.remove('d-none');
    }

    function saveNewCust() {
        const name = document.getElementById('new_name').value.trim();
        const phone = document.getElementById('new_phone').value.trim();
        if (!name) return alert("الاسم مطلوب (Name is required)");
        if (!phone) return alert("رقم الهاتف مطلوب (Phone is required)");
        if (!/^\d{7,15}$/.test(phone)) return alert("رقم الهاتف غير صحيح - يجب أن يكون أرقاماً فقط (7-15 رقم)");
        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);
        fetch('requests/add_customer_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allCustomers.push({
                        id: data.id,
                        name: name,
                        phone: phone
                    });
                    nextStep(3, {
                        id: data.id,
                        name: name
                    });
                } else {
                    alert("خطأ: " + (data.error || "فشل في إضافة الزبون"));
                }
            });
    }
</script>

<script>
    // New helper functions for sales_leftovers

    // Confirm weight with inventory check (leftover)
    function confirmLeftoverWeight() {
        const grams = parseFloat(document.getElementById('m_weight_val').value);
        if (!grams || grams <= 0) { alert('الرجاء إدخال الوزن'); return; }
        const leftoverId = document.getElementById('i_lid').value;
        const errBox = document.getElementById('leftover_weight_error');
        errBox.classList.add('d-none');
        if (leftoverId) {
            fetch('requests/check_stock.php?leftover_id=' + leftoverId + '&grams=' + grams)
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        errBox.innerHTML = '⚠️ الكمية غير متاحة! المتاح: <b>' + data.available_kg + ' كجم</b>';
                        errBox.classList.remove('d-none');
                    } else { setWeight(grams); }
                })
                .catch(() => setWeight(grams));
        } else { setWeight(grams); }
    }

    // Confirm units with inventory check (leftover)
    function confirmLeftoverUnits() {
        const units = parseInt(document.getElementById('m_units_val').value);
        if (!units || units <= 0) { alert('الرجاء إدخال العدد'); return; }
        const errBox = document.getElementById('leftover_units_error');
        errBox.classList.add('d-none');
        setUnits(units);
    }

    // Confirm price
    function confirmLeftoverPrice() {
        const price = parseFloat(document.getElementById('m_price_val').value);
        if (!price || price <= 0) { alert('الرجاء إدخال السعر'); return; }
        nextStep(5, price);
    }

    // Price preview for lo manual price
    document.addEventListener('DOMContentLoaded', function() {
        const priceInput = document.getElementById('m_price_val');
        const pricePreview = document.getElementById('lo_price_preview');
        if (priceInput && pricePreview) {
            priceInput.addEventListener('input', function() {
                const val = parseFloat(this.value);
                pricePreview.textContent = val > 0 ? val.toLocaleString() + ' ريال' : '';
            });
        }
    });

    // Contact Picker
    async function pickContact(fieldId) {
        if (!('contacts' in navigator && 'ContactsManager' in window)) {
            alert('هذه الميزة غير مدعومة على هذا الجهاز.');
            return;
        }
        try {
            const contacts = await navigator.contacts.select(['tel'], { multiple: false });
            if (contacts && contacts.length > 0 && contacts[0].tel && contacts[0].tel.length > 0) {
                document.getElementById(fieldId).value = contacts[0].tel[0].replace(/[^0-9]/g, '');
            }
        } catch (e) { console.log('Contact picker cancelled', e); }
    }

    // Transfer helpers for leftovers
    function toggleLoOtherReceiver(sel) {
        const o = document.getElementById('lo_receiver_other');
        if (o) { o.classList.toggle('d-none', sel.value !== 'أخرى'); if (sel.value !== 'أخرى') o.value = ''; }
    }
    function toggleLoOtherCompany(sel) {
        const o = document.getElementById('lo_company_other');
        if (o) { o.classList.toggle('d-none', sel.value !== 'أخرى'); if (sel.value !== 'أخرى') o.value = ''; }
    }

    function finishLoTransfer() {
        let receiver = document.getElementById('lo_receiver_val').value;
        if (receiver === 'أخرى') {
            const o = document.getElementById('lo_receiver_other');
            receiver = o ? o.value.trim() : '';
            if (!receiver) return alert('يرجى كتابة اسم المستلم');
        }
        let company = document.getElementById('lo_company_val').value;
        if (company === 'أخرى') {
            const o = document.getElementById('lo_company_other');
            company = o ? o.value.trim() : '';
            if (!company) return alert('يرجى كتابة اسم الشركة');
        }
        const sender = document.getElementById('lo_sender_val').value;
        const num    = document.getElementById('lo_num_val').value;
        if (!sender) return alert('اسم المرسل مطلوب');
        if (!num)    return alert('رقم الحوالة إلزامي');

        // Inject hidden fields
        let fSender   = document.getElementById('i_tsender_lo');
        let fReceiver = document.getElementById('i_treceiver_lo');
        let fNum      = document.getElementById('i_tnum_lo');
        let fCompany  = document.getElementById('i_tcompany_lo');
        if (!fSender) {
            const form = document.getElementById('saleForm');
            fSender = Object.assign(document.createElement('input'), {type:'hidden', name:'transfer_sender', id:'i_tsender_lo'});
            fReceiver = Object.assign(document.createElement('input'), {type:'hidden', name:'transfer_receiver', id:'i_treceiver_lo'});
            fNum = Object.assign(document.createElement('input'), {type:'hidden', name:'transfer_number', id:'i_tnum_lo'});
            fCompany = Object.assign(document.createElement('input'), {type:'hidden', name:'transfer_company', id:'i_tcompany_lo'});
            form.appendChild(fSender); form.appendChild(fReceiver); form.appendChild(fNum); form.appendChild(fCompany);
        }
        fSender.value = sender; fReceiver.value = receiver; fNum.value = num; fCompany.value = company;
        finishSale('Internal Transfer', null);
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
        setupFocusNavigation(['new_name', 'new_phone'], 'btn_save_new_cust');
        setupFocusNavigation(['m_weight_val'], 'btn_confirm_weight');
        setupFocusNavigation(['m_units_val'], 'btn_confirm_units');
        setupFocusNavigation(['m_price_val'], 'btn_confirm_price');
        setupFocusNavigation(['lo_receiver_other', 'lo_company_other', 'lo_sender_val', 'lo_num_val'], 'btn_finish_transfer');
    });
</script>

<?php include 'includes/footer.php'; ?>