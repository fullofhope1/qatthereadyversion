<?php
require 'config/db.php';
// Include header but handle the navigation differently for guests/users
include 'includes/header.php';

// Fetch Advertisements
$stmtAds = $pdo->query("SELECT * FROM advertisements WHERE status = 'Active' ORDER BY created_at DESC");
$ads = $stmtAds->fetchAll();

// Smart Date Selection: Show today's, but if it has nothing, show the latest available
$today = date('Y-m-d');

// Helper to get latest products if today is closed/empty
function getProducts($pdo, $status, $date)
{
    $stmt = $pdo->prepare("
        SELECT 
            qt.id as type_id,
            qt.name as type_name, 
            qt.description as type_desc, 
            COALESCE(MAX(p.media_path), qt.media_path) as display_media,
            SUM(p.quantity_kg) as total_kg,
            MAX(p.received_at) as last_received
        FROM purchases p
        JOIN qat_types qt ON p.qat_type_id = qt.id
        WHERE p.purchase_date = ? AND p.status = ? AND p.is_received = 1 AND qt.is_deleted = 0
        GROUP BY qt.id
        ORDER BY last_received DESC
    ");
    $stmt->execute([$date, $status]);
    return $stmt->fetchAll();
}

$freshProducts = getProducts($pdo, 'Fresh', $today);
if (!empty($freshProducts)) {
    $activeTab = 'products';
} else {
    $activeTab = 'ads';
}


?>


<div class="row mb-4">
    <div class="col-12 text-center py-5">
        <div class="mb-4 animate__animated animate__zoomIn">
            <img src="logo.jpg" alt="Logo" class="rounded-circle shadow-lg border border-4 border-warning" style="width: 150px; height: 150px; object-fit: cover;">
        </div>
        <h1 class="fw-black display-3 mb-2 animate__animated animate__fadeInDown">
            <span class="brand-text">القادري و ماجد</span>
        </h1>
        <h3 class="text-dark fw-bold mb-3">لأجود أنواع القات</h3>
        <div class="mb-3">
            <span class="badge bg-dark rounded-pill px-3 py-2">
                <i class="fas fa-phone-alt me-2 text-warning"></i> 775065459 - 774456261
            </span>
        </div>
        <p class="text-secondary lead">تميز في التوريد، دقة في الإعلانات، وضمان الجودة 🌿</p>
    </div>
</div>

<!-- Tabs Section -->
<div class="row">
    <div class="col-12 px-md-5">
        <ul class="nav nav-pills nav-justified mb-4 bg-white p-2 rounded-pill shadow-sm border" id="portalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'ads' ? 'active' : '' ?> rounded-pill fw-bold py-3" id="ads-tab" data-bs-toggle="pill" data-bs-target="#ads" type="button" role="tab">
                    <i class="fas fa-ad me-2"></i> الإعلانات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'products' ? 'active' : '' ?> rounded-pill fw-bold py-3" id="products-tab" data-bs-toggle="pill" data-bs-target="#products" type="button" role="tab">
                    <i class="fas fa-leaf me-2"></i> القات الجديد
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold py-3" id="auth-tab" data-bs-toggle="pill" data-bs-target="#auth" type="button" role="tab">
                    <i class="fas fa-user-circle me-2"></i> دخول / تسجيل
                </button>
            </li>
        </ul>

        <div class="tab-content pb-5" id="portalTabsContent">
            <!-- Tab 1: Advertisements -->
            <div class="tab-pane fade <?= $activeTab === 'ads' ? 'show active' : '' ?>" id="ads" role="tabpanel">
                <div class="row g-4 justify-content-center">
                    <?php if (empty($ads)): ?>
                        <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                            <i class="fas fa-bullhorn fa-4x text-muted mb-3 opacity-25"></i>
                            <h4 class="text-muted">لا توجد إعلانات نشطة في الوقت الحالي.</h4>
                            <p class="text-secondary">كن أول من يعلن هنا!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ads as $ad): ?>
                            <div class="col-md-4">
                                <div class="card h-100 shadow-sm border-0 rounded-4 transition-hover overflow-hidden">
                                    <?php if ($ad['media_path']): ?>
                                        <?php if (str_ends_with(strtolower($ad['media_path']), '.mp4') || str_ends_with(strtolower($ad['media_path']), '.mov')): ?>
                                            <video src="<?= htmlspecialchars($ad['media_path']) ?>" class="card-img-top" controls style="height: 200px; object-fit: cover;"></video>
                                        <?php else: ?>
                                            <img src="<?= htmlspecialchars($ad['media_path']) ?>" class="card-img-top" alt="Ad Image" style="height: 200px; object-fit: cover;">
                                        <?php endif; ?>
                                    <?php elseif ($ad['image_url']): ?>
                                        <img src="<?= htmlspecialchars($ad['image_url']) ?>" class="card-img-top" alt="Ad Image" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-warning-subtle text-warning py-5 text-center">
                                            <i class="fas fa-image fa-3x opacity-25"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($ad['client_name']) ?></h5>
                                        </div>
                                        <h6 class="card-title fw-bold text-secondary small mb-3"><?= htmlspecialchars($ad['title']) ?></h6>
                                        <p class="card-text text-muted small mb-4"><?= htmlspecialchars($ad['description']) ?></p>
                                        <?php if ($ad['link_url']): ?>
                                            <a href="<?= htmlspecialchars($ad['link_url']) ?>" target="_blank" class="btn btn-warning w-100 rounded-pill fw-bold shadow-sm">استكشف الآن <i class="fas fa-external-link-alt ms-1"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab 2: Our Product (Fresh) -->
            <div class="tab-pane fade <?= $activeTab === 'products' ? 'show active' : '' ?>" id="products" role="tabpanel">
                <div class="row g-4">
                    <?php if (empty($freshProducts)): ?>
                        <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                            <i class="fas fa-truck-loading fa-4x text-muted mb-3 opacity-25"></i>
                            <h4 class="text-muted">لم يتم استلام أي شحنات جديدة اليوم حتى الآن.</h4>
                            <p class="text-secondary">يرجى العودة لاحقاً للحصول على القات الطازج!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($freshProducts as $p): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 rounded-4 text-center p-4 transition-hover">
                                    <div class="mb-3">
                                        <?php $displayPhoto = $p['display_media']; ?>
                                        <?php if ($displayPhoto): ?>
                                            <?php if (str_ends_with(strtolower($displayPhoto), '.mp4') || str_ends_with(strtolower($displayPhoto), '.mov')): ?>
                                                <video src="<?= htmlspecialchars($displayPhoto) ?>" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;" muted autoplay loop></video>
                                            <?php else: ?>
                                                <img src="<?= htmlspecialchars($displayPhoto) ?>" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="bg-success-subtle text-success p-4 rounded-circle d-inline-block">
                                                <i class="fas fa-leaf fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="fw-bold text-dark"><?= htmlspecialchars($p['type_name']) ?></h5>
                                    <p class="small text-secondary mb-2"><?= htmlspecialchars($p['type_desc'] ?: 'قات طازج ممتاز قطاف اليوم.') ?></p>
                                    <div class="mb-3">
                                        <span class="badge bg-success text-white rounded-pill px-3 py-2">
                                            <?= number_format($p['total_kg'], 2) ?> كجم
                                        </span>
                                    </div>
                                    <div class="text-muted x-small">
                                        <i class="fas fa-clock me-1"></i> وصل: <?= date('h:i A', strtotime($p['last_received'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Tab 3: Login / Sign Up -->
            <div class="tab-pane fade" id="auth" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-dark text-white text-center py-3">
                                <h5 class="mb-0 fw-bold" id="authTitle">تسجيل الدخول إلى البوابة</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <div class="text-center py-4">
                                        <div class="bg-success-subtle text-success p-3 rounded-circle d-inline-block mb-3">
                                            <i class="fas fa-user-check fa-2x"></i>
                                        </div>
                                        <h4>مرحباً بعودتك، <?= htmlspecialchars($_SESSION['username']) ?>!</h4>
                                        <p class="text-secondary">أنت مسجل كـ <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>.</p>
                                        <div class="d-grid gap-2 mt-4">
                                            <?php if ($_SESSION['role'] !== 'user'): ?>
                                                <a href="dashboard.php" class="btn btn-warning fw-bold rounded-pill shadow-sm">دخول نظام الإدارة <i class="fas fa-arrow-left ms-1"></i></a>
                                            <?php endif; ?>
                                            <a href="logout.php" class="btn btn-outline-danger fw-bold rounded-pill">تسجيل الخروج</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Alert messages in Tab -->
                                    <?php if (isset($_GET['signup_success'])): ?>
                                        <div class="alert alert-success py-2 small shadow-sm mb-4"><i class="fas fa-check-circle me-1"></i> تم إنشاء الحساب! يرجى تسجيل الدخول.</div>
                                    <?php endif; ?>
                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger py-2 small shadow-sm mb-4">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            <?= ($_GET['error'] == 'exists') ? 'اسم المستخدم مأخوذ.' : (($_GET['error'] == 'mismatch') ? 'كلمات المرور غير متطابقة.' : 'بيانات الاعتماد غير صالحة.') ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Login Form Section -->
                                    <div id="loginSection">
                                        <form action="requests/process_login.php" method="POST">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">اسم المستخدم</label>
                                                <input type="text" name="username" class="form-control rounded-pill border-light bg-light px-3" required>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label small fw-bold text-secondary">كلمة المرور</label>
                                                <input type="password" name="password" class="form-control rounded-pill border-light bg-light px-3" required>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 shadow-sm">دخول</button>
                                        </form>
                                        <div class="text-center mt-4">
                                            <p class="small text-secondary mb-0">ليس لديك حساب؟ <a href="#" onclick="toggleAuth(true)" class="text-warning fw-bold text-decoration-none">إنشاء حساب</a></p>
                                        </div>
                                    </div>

                                    <!-- Signup Form Section (Hidden by default) -->
                                    <div id="signupSection" style="display: none;">
                                        <form action="requests/process_signup.php" method="POST">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label small fw-bold text-secondary">الاسم الكامل</label>
                                                    <input type="text" name="display_name" class="form-control rounded-pill border-light bg-light px-3" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-secondary">اسم المستخدم</label>
                                                    <input type="text" name="username" class="form-control rounded-pill border-light bg-light px-3" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-secondary">الهاتف</label>
                                                    <input type="text" name="phone" class="form-control rounded-pill border-light bg-light px-3" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-secondary">كلمة المرور</label>
                                                    <input type="password" name="password" class="form-control rounded-pill border-light bg-light px-3" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-secondary">تأكيد كلمة المرور</label>
                                                    <input type="password" name="confirm_password" class="form-control rounded-pill border-light bg-light px-3" required>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 shadow-sm mt-4">تسجيل</button>
                                        </form>
                                        <div class="text-center mt-4">
                                            <p class="small text-secondary mb-0">لديك حساب بالفعل؟ <a href="#" onclick="toggleAuth(false)" class="text-warning fw-bold text-decoration-none">العودة لتسجيل الدخول</a></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAuth(showSignup) {
        document.getElementById('loginSection').style.display = showSignup ? 'none' : 'block';
        document.getElementById('signupSection').style.display = showSignup ? 'block' : 'none';
        document.getElementById('authTitle').innerText = showSignup ? 'إنشاء حساب' : 'تسجيل الدخول إلى البوابة';
    }

    // Auto-switch to auth tab if error, success, or explicit auth param is present
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error') || urlParams.has('signup_success') || urlParams.has('auth')) {
            const authTab = new bootstrap.Tab(document.getElementById('auth-tab'));
            authTab.show();
            if (urlParams.get('error') === 'exists' || urlParams.get('error') === 'mismatch') toggleAuth(true);
        }
    }
</script>

<style>
    .transition-hover:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
    }

    .bg-warning-subtle {
        background-color: #fff3cd;
    }

    .bg-success-subtle {
        background-color: #d1e7dd;
    }

    .nav-pills .nav-link.active {
        background-color: #ffc107;
        color: #000;
    }

    .nav-pills .nav-link {
        color: #6c757d;
    }
</style>

<script>
    // Simple tab persistent logic could go here if needed
</script>

<?php include 'includes/footer.php'; ?>