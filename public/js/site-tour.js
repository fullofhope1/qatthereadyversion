// public/js/site-tour.js

window.startSiteTour = function() {
    console.log("Site Tour: startSiteTour called.");
    
    // 1. Initialize Driver if needed
    const driverLib = (window.driver && window.driver.js && window.driver.js.driver) 
                   || (window.driver && window.driver.driver)
                   || window.driver;

    if (typeof driverLib !== 'function') {
        console.error("Driver.js library not found.");
        alert("عذراً، مكتبة الشرح لم تتحمل بشكل كامل بعد. الرجاء تحديث الصفحة.");
        return;
    }

    const driverObj = driverLib({
        showProgress: true,
        animate: true,
        doneBtnText: "انتهى",
        nextBtnText: "التالي",
        prevBtnText: "السابق",
        progressText: "{{current}} من {{total}}",
        allowClose: true,
        overlayOpacity: 0.75,
    });

    // 2. Define Content
    const config = window.siteConfig || {};
    const tours = {
        "dashboard.php": {
            super_admin: [
                { element: ".bg-success", popover: { title: "مبيعات اليوم", description: "إجمالي المبيعات اليوم بعد الخصومات المرتجعة.", side: "bottom", align: "start" } },
                { element: ".bg-danger", popover: { title: "إجمالي الديون", description: "ديون العملاء المتبقية حالياً.", side: "bottom", align: "start" } },
                { element: "a[href='closing.php']", popover: { title: "إغلاق اليومية", description: "هام! اضغط هنا لإقفال حسابات اليوم وترحيلها.", side: "top", align: "start" } }
            ],
            admin: [
                { element: ".bg-warning", popover: { title: "مصاريف نوبتك", description: "المبالغ التي صرفتها في ورديتك الحالية.", side: "bottom", align: "start" } },
                { element: "a[href='sourcing.php']", popover: { title: "التوريد", description: "ابدأ بإدخال الكميات الموردة من هنا.", side: "top", align: "start" } }
            ]
        },
        "sales.php": {
            super_admin: [
                { element: ".circle-btn", popover: { title: "بدء البيع", description: "اختر نوع القات للبدء بعملية بيع جديدة.", side: "bottom", align: "start" } },
                { element: "#cSearch", popover: { title: "البحث عن عميل", description: "ابحث هنا لاختيار عميل موجود مسبقاً.", side: "top", align: "start" } }
            ]
        },
        "reports.php": {
            super_admin: [
                { element: "#repType", popover: { title: "تغيير التقرير", description: "اختر بين تقرير يومي أو شهري.", side: "bottom", align: "start" } },
                { element: ".btn-update-report", popover: { title: "تحديث", description: "اضغط هنا لتطبيق الفلتر وتحديث البيانات.", side: "top", align: "start" } }
            ]
        }
    };

    // 3. Match and Run
    const pageTours = tours[config.page];
    if (pageTours) {
        const steps = pageTours[config.subRole] || pageTours[config.role];
        if (steps && steps.length > 0) {
            driverObj.setSteps(steps);
            driverObj.drive();
        } else {
            alert("لا يوجد شرح متوفر لصلاحياتك في هذه الصفحة.");
        }
    } else {
        alert("لا يوجد شرح متوفر لهذه الصفحة حالياً.");
    }
};

console.log("Site Tour: Script loaded and ready.");
