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
            { element: ".card.bg-success, .card.bg-primary", popover: { title: "مبيعات وإحصائيات", description: "هنا يظهر إجمالي المبيعات، الإيرادات، والمخزون المتاح.", position: "bottom" } },
            { element: ".card.bg-danger", popover: { title: "الديون الإجمالية", description: "إجمالي ديون العملاء التي لم تُدفع بعد.", position: "bottom" } },
            { element: "a[href='closing.php']", popover: { title: "إغلاق اليومية", description: "ضروري جداً لإقفال الحسابات آخر اليوم والبدء بيوم جديد.", position: "top" } }
        ],
        "sales.php": [
            { element: ".circle-btn", popover: { title: "متوفر للبيع", description: "اضغط على أي مورد أو نوع من القات المتاح لبدء إنشاء فاتورة بيع.", position: "bottom" } },
            { element: "button[data-bs-target='#custListModal']", popover: { title: "اختيار زبون", description: "اربط الفاتورة بزبون مسجل لسهولة متابعة ديونه ومرتجعاته.", position: "top" } },
            { element: "#payment_method", popover: { title: "طريقة الدفع", description: "اختر نقداً، آجل (دين)، أو حوالة بنكية.", position: "top" } },
            { element: "#salesForm button[type='submit']", popover: { title: "اعتماد البيع", description: "بعد إدخال السعر والكمية، اضغط هنا لحفظ الفاتورة مباشرة.", position: "top" } }
        ],
        "purchases.php": [
            { element: "#sourcingForm, form", popover: { title: "استلام المشتريات", description: "قم بتسجيل الوزن الناقص/الصافي والسعر المتفق عليه لاستلام القات من المورد.", position: "bottom" } },
            { element: ".table-responsive", popover: { title: "سجل المشتريات المستلمة", description: "الفواتير السابقة والحالية تظهر هنا بصورة مفصلة.", position: "top" } }
        ],
        "expenses.php": [
            { element: "#categorySelect", popover: { title: "نوع المنصرف", description: "حدد إذا كان منصرف تشغيلي، مسحوب موظف، أو مصروف آخر.", position: "bottom" } },
            { element: "form button[type='submit']", popover: { title: "حفظ المصروف", description: "اعتماد الصرف. سيتم خصم المبلغ من الصندوق العام لليوم.", position: "top" } }
        ],
        "reports.php": [
            { element: ".report-nav-pills", popover: { title: "أقسام التقارير", description: "تنقل بين الخلاصة، تفاصيل المبيعات، المشتريات، المرتجعات، المصاريف وغيرها.", position: "bottom" } },
            { element: ".filter-pill-container", popover: { title: "الفلاتر الزمنية", description: "اختر التقرير اليومي، الشهري، أو السنوي وحدد التاريخ لعرض النتائج.", position: "top" } },
            { element: ".btn-update-report", popover: { title: "تحديث التقرير", description: "اضغط هنا لجلب التقرير وفقاً للفلاتر.", position: "top" } }
        ],
        "sourcing.php": [
            { element: "form", popover: { title: "التوريد والشراء", description: "هنا يتم تأسيس توريد جديد لمورد وتسجيل التكلفة المبدئية قبل وصوله للمحل.", position: "bottom" } }
        ],
        "debts.php": [
            { element: "#customerSelect", popover: { title: "اختيار العميل", description: "اختر العميل المديون لعرض أو تسديد دفعاته.", position: "bottom" } },
            { element: ".table-responsive", popover: { title: "الديون المسجلة", description: "فواتير الديون تظهر هنا، مع أدوات السداد وكشوفات الحساب.", position: "top" } }
        ]
    };

    const page = config.page;
    let rawSteps = allPotentialTours[page] || [];

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
