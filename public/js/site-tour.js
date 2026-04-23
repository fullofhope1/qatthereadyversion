// public/js/site-tour.js
(function() {
    document.addEventListener("DOMContentLoaded", function () {
        console.log("Site Tour: Initializing...");
        
        const helpTrigger = document.getElementById("help_trigger");
        if (!helpTrigger) {
            console.log("Site Tour: No help trigger found.");
            return;
        }

        // Initialize Driver.js 1.0+
        let driverObj;
        try {
            const driverLib = window.driver.js.driver;
            if (!driverLib) throw new Error("Driver.js library not loaded correctly.");
            
            driverObj = driverLib({
                showProgress: true,
                animate: true,
                doneBtnText: "انتهى",
                nextBtnText: "التالي",
                prevBtnText: "السابق",
                progressText: "{{current}} من {{total}}",
                allowClose: true,
                overlayOpacity: 0.75,
            });
            console.log("Site Tour: Driver initialized.");
        } catch (e) {
            console.error("Site Tour Error:", e);
            return;
        }

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
                ],
                seller: [
                    { element: ".circle-btn", popover: { title: "بدء البيع", description: "اختر النوع للبدء.", side: "bottom", align: "start" } }
                ]
            },
            "reports.php": {
                super_admin: [
                    { element: "#repType", popover: { title: "تغيير التقرير", description: "اختر بين تقرير يومي أو شهري.", side: "bottom", align: "start" } },
                    { element: ".btn-update-report", popover: { title: "تحديث", description: "اضغط هنا لتطبيق الفلتر وتحديث البيانات.", side: "top", align: "start" } }
                ]
            },
            "staff.php": {
                admin: [
                    { element: "#addStaffForm", popover: { title: "إضافة موظف", description: "أدخل بيانات الموظف وراتبه هنا.", side: "right", align: "start" } }
                ]
            }
        };

        helpTrigger.addEventListener("click", function (e) {
            e.preventDefault();
            console.log("Site Tour: Help clicked.");
            
            const config = window.siteConfig;
            if (!config) {
                console.error("Site Tour: siteConfig missing.");
                return;
            }
            
            console.log("Site Tour: Page:", config.page, "Role:", config.role);

            const pageTours = tours[config.page];
            if (pageTours) {
                // Try subRole first, then role
                const steps = pageTours[config.subRole] || pageTours[config.role];
                if (steps && steps.length > 0) {
                    console.log("Site Tour: Starting steps:", steps.length);
                    driverObj.setSteps(steps);
                    driverObj.drive();
                } else {
                    console.log("Site Tour: No steps found for this role/page combination.");
                    alert("عذراً، لا يتوفر شرح تفصيلي لهذه الصفحة حالياً بصلاحياتك.");
                }
            } else {
                console.log("Site Tour: No tours defined for page:", config.page);
                alert("عذراً، لا يتوفر شرح تفصيلي لهذه الصفحة حالياً.");
            }
        });
    });
})();
