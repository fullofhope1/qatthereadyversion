// public/js/site-tour.js
document.addEventListener("DOMContentLoaded", function () {
  const helpTrigger = document.getElementById("help_trigger");
  if (!helpTrigger) return;

  const driverConfig = {
    showProgress: true,
    animate: true,
    doneBtnText: "انتهى",
    nextBtnText: "التالي",
    prevBtnText: "السابق",
    progressText: "{{current}} من {{total}}",
  };

  const driverObj = window.driver.js.driver(driverConfig);

  const tours = {
    "dashboard.php": {
      super_admin: [
        {
          element: ".bg-success",
          popover: {
            title: "مبيعات اليوم",
            description: "هنا يظهر إجمالي المبيعات التي تمت اليوم بعد خصم المرتجعات.",
            side: "bottom",
            align: "start",
          },
        },
        {
          element: ".bg-danger",
          popover: {
            title: "إجمالي الديون",
            description: "هذا الرقم يوضح مجموع ديون العملاء المتبقية في ذمتهم.",
            side: "bottom",
            align: "start",
          },
        },
        {
          element: "a[href='closing.php']", // Targeted specifically
          popover: {
            title: "إغلاق اليومية",
            description: "هام جداً! عند انتهاء العمل، اضغط هنا لتصفية الحسابات وترحيل المبالغ لليوم التالي وتحديث أرصدة الديون.",
            side: "top",
            align: "start",
          },
        },
      ],
      admin: [
        {
          element: ".bg-warning",
          popover: {
            title: "مصاريفك اليوم",
            description: "هنا تظهر المصاريف التي قمت بتسجيلها في نوبتك الحالية فقط.",
            side: "bottom",
            align: "start",
          },
        },
        {
          element: "a[href='sourcing.php']",
          popover: {
            title: "التوريد",
            description: "من هنا يمكنك البدء بإدخال الكميات المستلمة من الرعية.",
            side: "top",
            align: "start",
          },
        },
      ],
    },
    "sales.php": {
      super_admin: [
        {
          element: ".circle-btn",
          popover: {
            title: "اختيار الصنف",
            description: "اضغط على الدائرة لاختيار نوع القات (قادري، ماجد، إلخ) للبدء بالبيع.",
            side: "bottom",
            align: "start",
          },
        },
        {
          element: "#cSearch",
          popover: {
            title: "بحث العملاء",
            description: "ابحث عن اسم العميل هنا لاختياره بسرعة في حال كانت العملية بالآجل.",
            side: "top",
            align: "start",
          },
        },
      ],
    },
    "reports.php": {
      super_admin: [
          {
              element: "#repType",
              popover: {
                  title: "نوع التقرير",
                  description: "يمكنك الاختيار بين تقرير يومي، شهري، أو تقرير مبيعات مفصل.",
                  side: "bottom", align: "start"
              }
          },
          {
              element: ".btn-update-report",
              popover: {
                  title: "تحديث البيانات",
                  description: "بعد اختيار التاريخ أو الفلتر، اضغط هنا لتحديث جدول البيانات.",
                  side: "top", align: "start"
              }
          }
      ]
    },
    "staff.php": {
        admin: [
            {
                element: "#addStaffForm",
                popover: {
                    title: "إضافة موظف",
                    description: "قم بإدخال بيانات الموظف وراتبه اليومي وسقف السحب المسموح له هنا.",
                    side: "right", align: "start"
                }
            },
            {
                element: ".btn-outline-light", 
                popover: {
                    title: "الموظفون غير النشطين",
                    description: "يمكنك عرض الموظفين الذين تم إلغاء تنشيطهم من هنا.",
                    side: "bottom", align: "start"
                }
            }
        ]
    }
  };

  helpTrigger.addEventListener("click", function (e) {
    e.preventDefault();
    const config = window.siteConfig;
    const pageTours = tours[config.page];
    if (pageTours) {
      const steps = pageTours[config.subRole] || pageTours[config.role];
      if (steps) {
        driverObj.setSteps(steps);
        driverObj.drive();
      } else {
        alert("عذراً، لا يتوفر شرح تفصيلي لهذه الصفحة حالياً بصلاحياتك.");
      }
    } else {
      alert("عذراً، لا يتوفر شرح تفصيلي لهذه الصفحة حالياً.");
    }
  });
});
