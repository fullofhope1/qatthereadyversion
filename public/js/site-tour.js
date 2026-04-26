// public/js/site-tour.js

console.log("Site Tour: Initializing...");

window.startSiteTour = function() {
    const config = window.siteConfig || {};
    
    if (typeof Driver === 'undefined') {
        alert("مكتبة الشرح غير جاهزة بعد، يرجى الانتظار ثانية.");
        return;
    }

    const driver = new Driver({
        allowClose: true,
        overlayOpacity: 0.75,
        doneBtnText: 'انتهى',
        closeBtnText: 'إغلاق',
        nextBtnText: 'التالي',
        prevBtnText: 'السابق',
    });

    const allPotentialTours = {
        // --- 1. Dashboard & Main Workflows ---
        "dashboard.php": [
            { element: ".card.bg-success", popover: { title: "مبيعات اليوم", description: "إجمالي قيمة ما تم بيعه وتأكيده هذا اليوم.", position: "bottom" } },
            { element: ".card.bg-danger", popover: { title: "ديون متراكمة", description: "إجمالي المديونيات غير المسددة للعملاء.", position: "bottom" } },
            { element: ".card.bg-warning", popover: { title: "تكاليف ومصاريف", description: "إجمالي المسحوبات والمصروفات المسجلة لليوم.", position: "bottom" } },
            { element: ".card.bg-primary", popover: { title: "مشتريات اليوم", description: "قيمة القات الذي تم توريده اليوم ومطابقته.", position: "bottom" } },
            { element: ".btn-success.btn-lg", popover: { title: "اختصار: مبيعات جديدة", description: "انقر هنا لفتح فاتورة بيع مباشرة.", position: "top" } },
            { element: "a[href='closing.php']", popover: { title: "إغلاق اليومية", description: "الإجراء الأخير والمهم جداً كل ليلة لإقفال الحسابات.", position: "top" } }
        ],
        "sales.php": [
            { element: "#summaryBar", popover: { title: "شريط الملخص", description: "يتحدث فورياً لإظهار نوع القات وسعره والوزن أثناء الاختيار.", position: "bottom" } },
            { element: ".circle-btn", popover: { title: "اختيار القات", description: "حدد النوع المطلوب (قات بلدي، مريسي، إلخ) ثم اسم المورد.", position: "bottom" } },
            { element: "button.btn-cust, button[data-bs-target='#custListModal']", popover: { title: "تسجيل الزبون", description: "اربط البيعة بزبون دائم أو زبون سفري.", position: "top" } },
            { element: ".btn-weight, #manualWeight", popover: { title: "الوزن", description: "اختر الأوزان المجهزة مسبقاً، أو اكتب الوزن بالجرام.", position: "top" } },
            { element: ".btn-price, #manualPrice", popover: { title: "السعر", description: "حدد السعر المتفق عليه لهذا الوزن.", position: "top" } },
            { element: ".btn-pay", popover: { title: "تنفيذ الفاتورة", description: "حدد نوع الدفع: كاش، آجل، أو بحوالة واضغط حفظ.", position: "top" } }
        ],
        "purchases.php": [
            { element: "form", popover: { title: "استلام المشتريات المستوردة", description: "هنا يتم وزن الكمية الواصلة من المورد لإثباتها في النظام.", position: "bottom" } },
            { element: "input[name='net_weight']", popover: { title: "الوزن الصافي", description: "الوزن الفعلي الخالص للقات.", position: "bottom" } },
            { element: "input[name='agreed_price']", popover: { title: "تكلفة الشراء", description: "القيمة الكلية المتفق عليها مع الرعوي.", position: "top" } },
            { element: ".table-responsive", popover: { title: "سجل الاستلام", description: "القائمة السفلية تعرض الشحنات المستلمة سابقاً المعتمدة.", position: "top" } }
        ],
        
        // --- 2. Finance & Admin ---
        "expenses.php": [
            { element: "#categorySelect", popover: { title: "نوع المصروف", description: "حدد إذا كان المصروف تشغيلي (إيجار) أم سُلفة لراتب موظف.", position: "bottom" } },
            { element: "input[name='amount']", popover: { title: "المبلغ المطلـوب", description: "كمية الريالات المستحقة للدفع.", position: "bottom" } },
            { element: "form button[type='submit']", popover: { title: "إتمام الصرف", description: "اضغط هنا لخصم المبلغ المُحدد من رصيد الصندوق النقدي.", position: "top" } },
            { element: ".table-responsive", popover: { title: "مراجعة المصروفات اليومية", description: "تظهر هنا لمراجعتها أو معالجة المدخلات الخاطئة.", position: "top" } }
        ],
        "sourcing.php": [
            { element: "form", popover: { title: "نموذج الشراء المبدئي", description: "تأسيس عملية استيراد قات من مورد (الرعوي) وتسجيل الأسعار الأولية.", position: "bottom" } },
            { element: "input[name='initial_cost']", popover: { title: "التكلفة التقديرية", description: "السعر المبدئي للقات قبل وصوله.", position: "top" } },
            { element: ".table", popover: { title: "التوريدات المفتوحة", description: "عمليات التوريد القائمة بانتظار الوزن والتأكيد في صفحة استلام المشتروات.", position: "top" } }
        ],
        "providers.php": [
            { element: "#providerSearch", popover: { title: "مربع البحث", description: "الوصول السريع لبيانات مورد محدد بالاسم أو الرقم.", position: "bottom" } },
            { element: "button[data-bs-target='#addProviderModal']", popover: { title: "إضافة راعي جديد", description: "تسجيل أسماء وأرقام الرعية المتعامل معهم لسهولة اختيارهم في الفواتير.", position: "bottom" } },
            { element: ".table-responsive", popover: { title: "سجل الرعية", description: "عرض أو تعديل أو مسح بيانات الموردين.", position: "top" } }
        ],

        // --- 3. People & Users ---
        "debts.php": [
            { element: "#customerSelect", popover: { title: "اختر العميل المديون", description: "تحديد الزبون المسجل عليه ديون (آجل) لجرد حسابه أو تسديد دفعته.", position: "bottom" } },
            { element: "input[name='amount']", popover: { title: "مبلغ السداد", description: "قيمة الدفعة التي يدفعها العميل الآن كاش.", position: "bottom" } },
            { element: "form button[type='submit']", popover: { title: "تأكيد الدفعة", description: "يتم إيداع الدفعة في الصندوق وتقليل إجمالي المديونية.", position: "top" } },
            { element: ".table-responsive", popover: { title: "كشف التسديدات", description: "جدول الدفعات المؤكدة السابقة كمرجع تفصيلي للعميل وإثبات لتسديداته.", position: "top" } }
        ],
        "customers.php": [
            { element: "#customerForm", popover: { title: "نظام العملاء", description: "أضف ببيانات الزبائن بشكل مسبق هنا ليسهل استخدام أسمائهم في فاتورة المبيعات ولتسجيل مشترياتهم الآجلة.", position: "bottom" } },
            { element: ".table", popover: { title: "قائمة الزبائن", description: "مجاميع الديون، وطرق التواصل، وأزرار للدخول لملف الديون وسدادها بالكامل.", position: "top" } }
        ],
        "staff.php": [
            { element: "#staffForm", popover: { title: "توظيف وعمال", description: "حفظ بيانات الموظف والراتب المتفق عليه ليسهل صرف المسحوبات (السلَف).", position: "bottom" } },
            { element: ".table", popover: { title: "قائمة الكادر", description: "يستعرض إجمالي رواتبهم وما تم سحبه والأرصدة المتبقية لكل موظف.", position: "top" } }
        ],

        // --- 4. Special Operations ---
        "refunds.php": [
            { element: ".step-wizard", popover: { title: "التعويضات خطوة بخطوة", description: "يوضح هذا الشريط المرحلة التي تقف فيها.", position: "bottom" } },
            { element: "#custSearchInput", popover: { title: "اختيار زبون", description: "أي زبون تعرض للغبن أو يطالب باسترجاع نقدي/حسم من مديونيته يجب تحديد اسمه أولاً.", position: "bottom" } },
            { element: "#card_debt, #card_cash", popover: { title: "نوع التعويض", description: "هل هو استرجاع كاش من الصندوق، أو خصم من دين الزبون؟", position: "top" } },
            { element: ".alert-warning, .card-header.bg-dark", popover: { title: "سجل العمليات", description: "مراجعة العمليات السابقة لمكافحة التلاعب.", position: "top" } }
        ],
        "returns.php": [
            { element: "#returnsForm", popover: { title: "المرتجعات العينية", description: "لإرجاع كمية قات (بالوزن أو الحبة) من العميل لتعود متوفرة في مخزون المحل.", position: "bottom" } },
            { element: ".table-responsive", popover: { title: "سجل المرتجعات", description: "مراجعة وحذف أخطاء المرتجعات إن وجدت.", position: "top" } }
        ],
        "unknown_transfers.php": [
            { element: "#addTransferBtn", popover: { title: "الحوالات المجهولة", description: "إذا استلم المحل حوالة ولا يُعرف لمن تعود، أو لتسجيل حوالات معلقة لحين تأكيد هويتها.", position: "bottom" } },
            { element: ".table-responsive", popover: { title: "الكشف المجهول", description: "يمكنك لاحقاً مطابقتها وتحويلها لحساب مبيعات أو ديون عند معرفة مُرسلها.", position: "top" } }
        ],
        "sales_leftovers_1.php": [
            { element: ".card", popover: { title: "بقايا البيع الأول", description: "يتم استخدام هذه الصفحة للتعامل مع بقايا القات التي لم تُبع كأوزان كاملة ولكنها قابلة للتخفيض والدمج.", position: "bottom" } }
        ],
        "sales_leftovers_2.php": [
            { element: ".card", popover: { title: "بقايا البيع الثاني", description: "في نهاية المطاف يتم دمج وتخفيض أسعار البقايا بشكل نهائي في هذه الصفحة.", position: "bottom" } }
        ],
        "whatsapp_statements.php": [
            { element: "#whatsappForm", popover: { title: "نظام رسائل كشف الحساب", description: "أداة رائعة تتيح إرسال كشوفات الحساب والديون للعملاء المحددين عن طريق تطبيق الواتساب بشكل فوري.", position: "bottom" } }
        ],
        "manage_products.php": [
            { element: "#productForm", popover: { title: "إدارة المنتجات والأصناف", description: "لوحة تحكم كاملة بإضافة صور وبيانات للأصناف التي تُعرض وتُباع.", position: "bottom" } }
        ],
        "manage_ads.php": [
            { element: "#adsForm", popover: { title: "إعلانات الصفحة الرئيسية", description: "رفع وإدارة اللافتات الإعلانية والصور المتحركة لواجهة النظام.", position: "bottom" } }
        ],

        // --- 5. Reports (15 Unique Tabs Extracted from reports.php) ---
        "reports.php_Summary": [
            { element: ".report-nav-pills", popover: { title: "شريط التنقل المالي", description: "يحتوي على كافة أشكال وتفريعات التقارير المحاسبية للنظام.", position: "bottom" } },
            { element: ".filter-pill-container", popover: { title: "الفلاتر والأوقات", description: "التقارير تتأثر بالزمن. اختَر تقرير (يومي، شهري، سنوي) وحدد التاريخ وسيَتحدث كل شيء بالأسفل.", position: "bottom" } },
            { element: ".row.g-3 .col-md-3:nth-child(1)", popover: { title: "الدخل الكلي والمبيعات", description: "مجاميع المبيعات الإجمالية بما في ذلك المدفوع كاش، بحوالات، والديون المتبقية.", position: "top" } },
            { element: ".bg-dark.text-white, .cash-summary-card", popover: { title: "صافي صندوق الكاش المحصّل", description: "هذا هو النقد (الكاش) الصافي المتواجد حالياً في الدرج الفعلي بناءً على حسابات البيع ناقصاً المصاريف.", position: "top" } }
        ],
        "reports.php_Sales": [
            { element: ".report-nav-pills", popover: { title: "شريط التقارير", description: "أنت تشاهد حالياً تقرير (المبيعات) للفرز الدقيق للمبيعات السابقة وفواتيرها.", position: "bottom" } },
            { element: "select[name='provider_id']", popover: { title: "تصفية حسب نوع القات أثنا المبيعات", description: "شريط التصفية يسمح بفرز الجدول لاستخراج مبيع مورد/رعوي معين دون غيره.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "جدول السجلات المفصلة", description: "يعرض الفواتير بدقة شديدة: اسم العميل، المبلغ، الوقت، وطريقة الدفع (حوالة، آجل، كاش..). زر (عرض الفاتورة) متاح هنا أيضاً.", position: "top" } }
        ],
        "reports.php_Receiving": [
            { element: ".report-nav-pills", popover: { title: "تبويبة الاستلامات والمشتروات", description: "المشتريات السابقة تجدها هنا مجدولة بحسب الزمن للرجوع لاي بيانات مورد سابق.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "قائمة المشتروات الموثّقة", description: "جدول بكل ما تم اعتماده واستلامه وأوزانه مع تكلفته الأولية.", position: "top" } }
        ],
        "reports.php_Expenses": [
            { element: ".filter-pill-container", popover: { title: "فلاتر المصروفات", description: "استخدم الفلتر لتعرف تكاليف ومصاريف أسبوع أو شهر معين، لمقارنتها بأرباحك.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "تفاصيل الصرف", description: "أين تذهب أموال الصندوق؟ المصاريف تسجل هنا بدقة وعناية.", position: "top" } }
        ],
        "reports.php_Debts": [
            { element: ".report-nav-pills", popover: { title: "تقارير الديون والإحصاءات", description: "بديلاً عن نظام الديون العادي، التقارير تساعدك بفهم متى كان العميل شديد الاستدانة ومتى تم سداد دفعاته على شكل فترات زمنية.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "مديونيات الزمن المستهدف", description: "أحركة الديون للفترة التي حددتها في الفلتر.", position: "top" } }
        ],
        "reports.php_Staff": [
            { element: ".report-table-card", popover: { title: "تقارير الرواتب", description: "التقارير الخاصة بحسابات المسحوبات للموظفين وحركة مرتباتهم.", position: "top" } }
        ],
        "reports.php_Customers": [
            { element: ".report-table-card", popover: { title: "حركة العملاء", description: "تقارير بحركة إدخال عملاء جدد وحجم التفاعل اليومي لهم.", position: "top" } }
        ],
        "reports.php_Compensations": [
            { element: ".report-table-card", popover: { title: "سجل التعويضات المعقدة", description: "يحتوي هذا الجدول على التعويضات المالية النقدية وحسومات المديونيات.", position: "top" } }
        ],
        "reports.php_Returns": [
            { element: ".report-table-card", popover: { title: "حركة الإرجاع العيني", description: "الأصناف التي تم استعادتها بالوزن للثلاجة تظهر في هذا السجل.", position: "top" } }
        ],
        "reports.php_unknown_transfers": [
            { element: ".report-table-card", popover: { title: "حوالات مبهمة", description: "إذا استقبل حساب الشركة مبالغ غير معرفة، هذا الجدول يعتبر مرجع لها حتى تسويتها.", position: "top" } }
        ],
        "reports.php_Leftovers_1": [
            { element: ".report-table-card", popover: { title: "مردودات البواقي 1", description: "تقرير عن المبيعات المهدرة المخفضة التي تم اعتبارها للبيع الأول.", position: "top" } }
        ],
        "reports.php_Leftovers_2": [
            { element: ".report-table-card", popover: { title: "مردودات البواقي 2", description: "عائد البقايا النهائية المعالجة.", position: "top" } }
        ],
        "reports.php_Damaged": [
            { element: ".report-table-card", popover: { title: "الخسائر المهدورة (التوالف)", description: "مخصص للأصناف التي تم التخلص منها ولا تصلح للبيع ليتم حسابها في نظام الخسائر.", position: "top" } }
        ],
        "reports.php_Dashboard": [
            { element: ".card", popover: { title: "التحليل العميق (Analytics)", description: "يقدم لك هذا القسم مؤشرات أرباحك الصافية وإجمالي أصولك التي تراكمت في الديون، المخزون المتوفر وغيرها.", position: "top" } }
        ],
        "reports.php_Printable": [
            { element: "button.btn-lg, .btn-primary", popover: { title: "أداة الطباعة (جاهز ورقياً)", description: "جهزنا التقرير ليكون أبيض وأسود ومنسق لطابعتك. فقط اضغط زر الطباعة.", position: "top" } }
        ],
        "reports.php_General": [
            { element: ".report-nav-pills", popover: { title: "التنقل السلس", description: "أنت داخل تبويبات الشجرة المحاسبية في التقارير. يمكنك النقر على أي منها.", position: "bottom" } },
            { element: ".filter-pill-container", popover: { title: "فلتر العرض", description: "خصص الزمن والسنة المناسبة للتوليد التلقائي للبيانات.", position: "bottom" } },
            { element: ".btn-update-report", popover: { title: "تحديث", description: "حتى تتأكد أن جدول هذا القسم حديث، انقر للتحديث.", position: "top" } },
            { element: ".report-table-card", popover: { title: "قاعدة بيانات التبويب", description: "يتم تحميل كافة البيانات المرتبطة بتبويبك الحالي في هذا الجدول السفلي.", position: "top" } }
        ]
    };

    let page = config.page;
    let rawSteps = [];

    // Dynamically handle views in reports.php
    if (page === 'reports.php') {
        const urlParams = new URLSearchParams(window.location.search);
        let viewTab = urlParams.get('view') || 'Summary';
        let customKey = "reports.php_" + viewTab;
        
        // If we have a custom tour for this specific tab, use it. Otherwise use General reports tour.
        if (allPotentialTours[customKey]) {
            rawSteps = allPotentialTours[customKey];
        } else {
            rawSteps = allPotentialTours["reports.php_General"];
        }
    } else {
        rawSteps = allPotentialTours[page] || [];
    }

    if (rawSteps.length > 0) {
        // CRITICAL: Filter steps to only include existing elements on the page
        const validSteps = rawSteps.filter(s => {
            // Split up multiple selectors if we use commas e.g., ".card.bg-success, .card.bg-primary"
            const selectors = s.element.split(',').map(sel => sel.trim());
            for (let sel of selectors) {
                const el = document.querySelector(sel);
                if (el) {
                    s.element = sel; // use the first matching one
                    return true;
                }
            }
            console.warn("Site Tour: Element not found, skipping:", s.element);
            return false;
        });

        if (validSteps.length > 0) {
            console.log("Site Tour: Starting with", validSteps.length, "valid steps.");
            driver.defineSteps(validSteps);
            driver.start();
        } else {
            alert("لا توجد عناصر واضحة للشرح في هذه الصفحة حالياً.");
        }
    } else {
        alert("لا توجد جولة تعليمية مخصصة لهذه الصفحة بعد.");
    }
};
