</div> <!-- End Container -->

<!-- Floating Help Button -->
<?php if (isset($_SESSION['user_id'])): ?>
<a href="javascript:void(0)" id="help_trigger" title="دليل الاستخدام">
    <i class="fas fa-question"></i>
</a>
<?php endif; ?>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>

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