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
        "dashboard.php": [
            { element: ".card.bg-success, .card.bg-success", popover: { title: "مبيعات اليوم", description: "هنا يظهر إجمالي المبيعات التي تمت هذا اليوم.", position: "bottom" } },
            { element: ".card.bg-danger", popover: { title: "الديون الإجمالية", description: "إجمالي ديون العملاء وتراكمات الآجل التي لم تُدفع بعد.", position: "bottom" } },
            { element: ".card.bg-warning", popover: { title: "مصاريف اليوم", description: "إجمالي ما تم سحبه كمصاريف تشغيلية أو شخصية.", position: "bottom" } },
            { element: ".card.bg-primary", popover: { title: "المشتريات", description: "إجمالي قيمة القات المورد اليوم.", position: "bottom" } },
            { element: "a[href='sales.php'].btn-lg", popover: { title: "إجراء سريع", description: "اختصار لفتح شاشة مبيعات جديدة مباشرة.", position: "top" } },
            { element: "a[href='closing.php']", popover: { title: "إغلاق اليومية", description: "النقطة الأهم نهاية اليوم! لإقفال وتصفية الصندوق والترحيل للمحاسبة.", position: "top" } }
        ],
        "sales.php": [
            { element: "#summaryBar", popover: { title: "شريط الملخص", description: "شريط يتحدث مباشرة ليعكس اختياراتك أثناء تكوين الفاتورة.", position: "bottom" } },
            { element: ".circle-btn", popover: { title: "أنواع القات المتوفرة", description: "اضغط على النوع لبدء خطوات الفاتورة.", position: "bottom" } },
            { element: "button[onclick*='showCustList'], button.btn-cust", popover: { title: "تحديد الزبون", description: "اربط الفاتورة بزبون مسجل لديك أو اضف عميلاً جديداً.", position: "top" } },
            { element: "button.btn-weight, #manualWeight", popover: { title: "تحديد الوزن", description: "اختر الأوزان المجهزة (ثمن، ربع) أو أدخلها يدوياً بالجرام.", position: "top" } },
            { element: "button.btn-price, #manualPrice", popover: { title: "تحديد السعر", description: "حدد السعر المتفق عليه لهذا الوزن.", position: "top" } },
            { element: "button.btn-pay", popover: { title: "إنهاء المبيعة", description: "اختر كيف سيدفع العميل (نقداً، آجل، أو بحوالة بنكية).", position: "top" } }
        ],
        "purchases.php": [
            { element: "form", popover: { title: "استلام المشتريات", description: "هنا يتم وزن الصنف الواصل من المورد وإدخال سعره الصافي الذي يُعتمد في النظام.", position: "bottom" } },
            { element: ".table-responsive", popover: { title: "سجل الاستلام", description: "يستعرض لك البضاعة التي استلمتها مسبقاً وتفاصيل كل شحنة.", position: "top" } }
        ],
        "expenses.php": [
            { element: "#categorySelect", popover: { title: "تصنيف المصروف", description: "هل هو مصروف تشغيلي (إيجار، عمال)، أم سحب لراتب موظف؟ تحديد النوع مهم للتقارير.", position: "bottom" } },
            { element: "input[name='amount']", popover: { title: "المبلغ المطلـوب", description: "أدخل كمية الريالات المصروفة بدقة.", position: "bottom" } },
            { element: "form button[type='submit']", popover: { title: "صرف", description: "المبلغ هنا سيُقتطع من صندوق الكاش اليومي بشكل مباشر.", position: "top" } },
            { element: ".table-responsive", popover: { title: "مراجعة المصروفات", description: "أي مصروف تدخله بالغلط يظهر هنا ويمكن حذفه أو تعديله بيومه.", position: "top" } }
        ],
        "reports.php_Summary": [
            { element: ".report-nav-pills", popover: { title: "شريط التبويبات", description: "تبويبات التنقل العلوية تنقلك بين كافة أنواع التقارير.", position: "bottom" } },
            { element: ".filter-pill-container", popover: { title: "الفلترة الزمنية", description: "تحديد النطاق الزمني وسنة التقرير.", position: "bottom" } },
            { element: ".bg-dark.text-white", popover: { title: "صافي الصندوق", description: "هذا هو النقد الموجود في درج الكاشير الفعلي بعد خصم المصاريف.", position: "bottom" } },
            { element: ".row.g-3 .col-md-3:nth-child(1)", popover: { title: "المبيعات والدخل", description: "إجمالي مجاميع المبيعات كقيمة نقدية وإجمالي ما تم استلامه.", position: "top" } }
        ],
        "reports.php_Sales": [
            { element: ".report-nav-pills", popover: { title: "شريط التبويبات", description: "اختيار القسم الفرعي (تتواجد في تبويبة المبيعات حالياً).", position: "bottom" } },
            { element: "select[name='provider_id']", popover: { title: "تصفية الموردين", description: "يمكنك عرض مبيعات قات ورد محدد فقط عبر هذا الفلتر.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "سجل المبيعات", description: "كل بيعة تمت مع رقم واسم الزبون وطريقة دفعه واسم الرعوي للقات المُباع.", position: "top" } }
        ],
        "reports.php_Receiving": [
            { element: ".report-nav-pills", popover: { title: "التبويبات", description: "أنت الآن في تبويب المشتريات.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "القات المورد", description: "تفاصيل ما تم توريده بأسماء الرعية (الموردين) وأسعاره وكمياته.", position: "top" } }
        ],
        "reports.php_Expenses": [
            { element: ".report-nav-pills", popover: { title: "التبويبات", description: "قسم المصاريف.", position: "bottom" } },
            { element: ".report-table-card", popover: { title: "تفاصيل المصاريف", description: "تظهر المصروفات هنا مفصلة حسب الصنف (تشغيلية، رواتب، متفرقات).", position: "top" } }
        ],
        "reports.php_Debts": [
            { element: ".report-table-card", popover: { title: "حركة المديونية", description: "يظهر هنا كل من دفع أو أخذ بالآجل.", position: "top" } }
        ],
        "reports.php_General": [
            { element: ".report-nav-pills", popover: { title: "التنقل بين الأقسام", description: "انقر على أي قسم للانتقال لسجله التفصيلي المتعلق بهذه الصفحة.", position: "bottom" } },
            { element: ".filter-pill-container", popover: { title: "فلاتر العرض الزمنية", description: "يسمح لك باستخراج تقرير خاص بيوم محدد، شهر، أو سنة كاملة.", position: "bottom" } },
            { element: ".btn-update-report", popover: { title: "تطبيق الفرز", description: "بعد إعداد خياراتك، اضغط هنا لتنعكس التقارير بنسختها الجديدة.", position: "top" } },
            { element: ".report-table-card, .table-responsive", popover: { title: "سجل البيانات", description: "البيانات التفصيلية لهذا القسم يتم عرضها هنا بالأسفل.", position: "top" } }
        ],
        "sourcing.php": [
            { element: "form", popover: { title: "تأسيس توريد/شراء", description: "يسجل المشرف هنا أسماء الرعية وتكلفة الشراء الأولية حتى قبل وصول البضاعة للمحل.", position: "bottom" } },
            { element: ".table", popover: { title: "التوريدات المعلقة", description: "التوريدات التي تم اعتمادها بانتظار استلامها في الفرع تظهر هنا.", position: "top" } }
        ],
        "debts.php": [
            { element: "#customerSelect", popover: { title: "البحث عن عميل مديون", description: "اختر العميل لعرض إجمالي ديونه بالتفصيل.", position: "bottom" } },
            { element: "form button[type='submit']", popover: { title: "تسديد دين", description: "أدخل المبلغ المستلم وسيتم تنزيله من رصيد العميل واحتسابه في الصندوق.", position: "top" } },
            { element: ".table-responsive", popover: { title: "كشف حساب وتسديدات", description: "السجل لكافة المبالغ المسددة والمقيدة على العميل لمراجعتها.", position: "top" } }
        ],
        "customers.php": [
            { element: "#customerForm", popover: { title: "إضافة عميل جديد", description: "الاحتفاظ ببيانات العملاء هنا يمكّنك من ربط فواتير الآجل بأسمائهم لاحقاً.", position: "bottom" } },
            { element: ".table", popover: { title: "سجل العملاء الدائمين", description: "عرض لأرقام ومجاميع ديون كل العملاء، مع أزرار للسداد السريع.", position: "top" } }
        ],
        "staff.php": [
            { element: "#staffForm", popover: { title: "بطاقة موظف", description: "قيد الموظفين هنا يسمح بربط مصاريفهم وسحبياتهم بحساباتهم مباشرة.", position: "bottom" } },
            { element: ".table", popover: { title: "كشف العمال", description: "تفاصيل الرصيد لكل عامل وزر للتسديد أو عرض كشف مفصل لكافة مسحوباته.", position: "top" } }
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
