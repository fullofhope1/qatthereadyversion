</div> <!-- End Container -->

<!-- Floating Help Button -->
<?php if (isset($_SESSION['user_id'])): ?>
<a href="javascript:void(0)" id="help_trigger" onclick="if(typeof startSiteTour === 'function') startSiteTour(); else alert('جاري تحميل نظام المساعدة... يرجى المحاولة بعد قليل');" title="دليل الاستخدام">
    <i class="fas fa-question"></i>
</a>
<?php endif; ?>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/driver.js@0.9.8/dist/driver.min.js"></script>

<!-- Global Site Data for Tour -->
<script>
    window.siteConfig = {
        role: "<?= $_SESSION['role'] ?? 'user' ?>",
        subRole: "<?= $_SESSION['sub_role'] ?? 'full' ?>",
        page: "<?= basename($_SERVER['PHP_SELF']) ?>"
    };
</script>

<script src="public/js/site-tour.js?v=<?= time() ?>"></script>
<script src="public/js/main.js"></script>
</body>

</html>