// public/js/site-tour.js

// Loud diagnostic as soon as script loads
console.log("Site Tour: Script starting to load...");

window.startSiteTour = function() {
    console.log("Site Tour: Function startSiteTour triggered.");
    
    // 1. Get Site Config
    const config = window.siteConfig || {};
    console.log("Site Config Data:", config);

    // 2. Locate Library
    let driverLib;
    try {
        // Driver.js 1.0 IIFE exports to window.driver.js.driver
        if (window.driver && window.driver.js && typeof window.driver.js.driver === 'function') {
            driverLib = window.driver.js.driver;
        } else if (window.driver && typeof window.driver.driver === 'function') {
            driverLib = window.driver.driver;
        } else if (typeof window.driver === 'function') {
            driverLib = window.driver;
        }
    } catch(e) {
        console.error("Site Tour: Library access error", e);
    }

    if (!driverLib) {
        console.error("Site Tour: Driver.js not found in global scope.");
        alert("تنبيه: مكتبة التوجيه التعليمي غير متوفرة حالياً. جرب تحديث الصفحة.");
        return;
    }

    // 3. Initialize
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

    // 4. Content Mapping
    const tours = {
        "dashboard.php": {
            "super_admin": [
                { element: ".bg-success", popover: { title: "مبيعات اليوم", description: "مجموع ما تم بيعه اليوم كاش وآجل.", side: "bottom" } },
                { element: ".bg-danger", popover: { title: "إجمالي الديون", description: "ديون العملاء الإجمالية.", side: "bottom" } },
                { element: "a[href='closing.php']", popover: { title: "إغلاق اليوم", description: "اضغط هنا في نهاية الدوام لتصفية الحسابات.", side: "top" } }
            ],
            "admin": [
                { element: ".bg-warning", popover: { title: "مصاريفك", description: "المبالغ التي قمت بصرفها.", side: "bottom" } },
                { element: "a[href='sourcing.php']", popover: { title: "التوريد", description: "شاشة استلام القات من الموردين.", side: "top" } }
            ]
        },
        "sales.php": {
            "super_admin": [
                { element: ".circle-btn", popover: { title: "بدء البيع", description: "اختر نوع القات للبدء.", side: "bottom" } },
                { element: "#cSearch", popover: { title: "بحث الزبائن", description: "ابحث عن زبون دائم هنا.", side: "top" } }
            ]
        },
        "reports.php": {
            "super_admin": [
                { element: "#repType", popover: { title: "نوع التقرير", description: "غيّر بين التقرير اليومي والشهري من هنا.", side: "bottom" } }
            ]
        }
    };

    // 5. Execution
    const page = config.page || "";
    const role = config.role || "";
    const subRole = config.subRole || "";

    console.log("Site Tour: Running for Page:", page, "Role:", role, "SubRole:", subRole);

    const pageTour = tours[page];
    if (pageTour) {
        // Fallback logic: check subRole, then role
        const steps = pageTour[subRole] || pageTour[role];
        if (steps && steps.length > 0) {
            driverObj.setSteps(steps);
            driverObj.drive();
        } else {
            alert("لا تتوفر خطوات تعليمية لهذه الصفحة بصلاحياتك: " + role + "/" + subRole);
        }
    } else {
        alert("نظام المساعدة لا يحتوي على شرح لهذه الصفحة: " + page);
    }
};

console.log("Site Tour: Script fully loaded.");
