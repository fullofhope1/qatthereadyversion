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
            p.unit_type,
            SUM(p.quantity_kg) as total_kg,
            SUM(p.received_units) as total_units,
            MAX(p.received_at) as last_received
        FROM purchases p
        JOIN qat_types qt ON p.qat_type_id = qt.id
        WHERE p.purchase_date = ? AND p.status = ? AND p.is_received = 1 AND qt.is_deleted = 0
        GROUP BY qt.id, p.unit_type
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

<!-- ==============================================
     1. SPLASH SCREEN (PRELOADER)
=============================================== -->
<script>
    document.body.classList.add('loading');
</script>
<style>
    /* Prevent animations while loading */
    body.loading .animate__animated {
        animation-play-state: paused !important;
    }

    body.loading {
        overflow: hidden;
    }

    #splash-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: linear-gradient(135deg, #091216, #16262e, #1c3642);
        z-index: 99999;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        transition: opacity 0.8s cubic-bezier(0.25, 0.8, 0.25, 1), visibility 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    #splash-screen.fade-out {
        opacity: 0;
        visibility: hidden;
        transform: scale(1.05);
    }

    .splash-logo-container {
        position: relative;
        margin-bottom: 2.5rem;
    }

    .splash-logo {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #ffd700;
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        animation: pulseLogo 2s infinite ease-in-out;
        position: relative;
        z-index: 2;
    }

    .splash-spinner {
        position: absolute;
        top: -15px;
        left: -15px;
        width: 180px;
        height: 180px;
        border-color: #ffd700 transparent #ffd700 transparent;
        animation: spin 1.5s linear infinite;
        z-index: 1;
        opacity: 0.8;
    }

    .splash-title {
        color: #ffffff;
        font-family: 'Tajawal', sans-serif;
        font-weight: 900;
        font-size: 2.5rem;
        letter-spacing: 2px;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        margin-bottom: 0.5rem;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUpText 0.8s forwards 0.3s;
    }

    .splash-subtitle {
        color: #ffd700;
        font-family: 'Tajawal', sans-serif;
        font-weight: bold;
        font-size: 1.3rem;
        letter-spacing: 1px;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUpText 0.8s forwards 0.6s;
    }

    @keyframes pulseLogo {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.6);
        }

        50% {
            transform: scale(1.02);
            box-shadow: 0 0 25px 15px rgba(255, 215, 0, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 215, 0, 0);
        }
    }

    @keyframes fadeInUpText {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div id="splash-screen">
    <div class="splash-logo-container">
        <div class="spinner-border splash-spinner" role="status"></div>
        <img src="logo.jpg" alt="القادري لأجود أنواع القات" class="splash-logo">
    </div>
    <h1 class="splash-title">القادري ومـاجـد</h1>
    <div class="splash-subtitle">لأجود أنواع القات</div>
</div>

<script>
    window.addEventListener('load', function() {
        // Enforce a minimum display time of 2.2 seconds for the cinematic effect
        setTimeout(function() {
            const splash = document.getElementById('splash-screen');
            if (splash) {
                splash.classList.add('fade-out');
                // Remove the 'loading' class to kick off the entry animations
                document.body.classList.remove('loading');

                // Cleanup DOM after transition finishes
                setTimeout(() => splash.remove(), 800);
            }
        }, 2200);
    });
</script>
<!-- ============================================== -->

<div class="row mb-4">
    <div class="col-12 text-center py-5">
        <div class="mb-4 animate__animated animate__zoomIn">
            <img src="logo.jpg" alt="Logo" class="rounded-circle shadow-lg border border-4 border-warning" style="width: 150px; height: 150px; object-fit: cover;">
        </div>
        <h1 class="fw-black display-3 mb-2 animate__animated animate__fadeInDown">
            <span class="brand-text">القادري و ماجد</span>
        </h1>
        <h3 class="text-dark fw-bold mb-3">لأجود أنواع القات</h3>
        <style>
            .premium-quote {
                font-family: 'Tajawal', sans-serif;
                color: #2c3e50;
                background: rgba(255, 255, 255, 0.9);
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.7);
                padding: 1.5rem 3rem;
                display: inline-block;
                position: relative;
            }

            .contact-card {
                border-radius: 24px;
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
                overflow: hidden;
            }

            .contact-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
            }

            .social-btn {
                display: inline-flex;
                align-items: center;
                padding: 14px 28px;
                border-radius: 50px;
                color: white !important;
                text-decoration: none;
                font-family: 'Tajawal', sans-serif;
                font-weight: bold;
                font-size: 1.15rem;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                border: 2px solid transparent;
            }

            .social-btn i {
                font-size: 1.6rem;
                margin-left: 12px;
            }

            .social-btn:hover {
                transform: translateY(-6px) scale(1.03);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
            }

            .social-btn.facebook {
                background: linear-gradient(135deg, #1877f2, #0d5bb5);
            }

            .social-btn.snapchat {
                background: linear-gradient(135deg, #FFFC00, #e6e200);
                color: #111 !important;
                text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5);
            }

            .social-btn.instagram {
                background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            }

            .social-btn.youtube {
                background: linear-gradient(135deg, #ff0000, #cc0000);
            }
        </style>

        <!-- Slogan -->
        <div class="mb-5 animate__animated animate__zoomIn" style="animation-delay: 0.1s;">
            <h4 class="fw-bold premium-quote mt-2">
                <i class="fas fa-quote-right position-absolute" style="color: #ffd700; right: -15px; top: -15px; font-size: 2rem; text-shadow: 0 5px 15px rgba(255,193,7,0.4);"></i>
                لا نخاف من بورة ولا نفرح بحظا
                <i class="fas fa-quote-left position-absolute" style="color: #ffd700; left: -15px; bottom: -15px; font-size: 2rem; text-shadow: 0 5px 15px rgba(255,193,7,0.4);"></i>
            </h4>
        </div>

        <div class="row g-4 justify-content-center mb-5 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
            <!-- WhatsApp Call to Action -->
            <div class="col-md-6 col-xl-4">
                <div class="contact-card p-4 shadow-lg h-100 position-relative text-white d-flex flex-column" style="background: linear-gradient(135deg, #128C7E 0%, #25D366 100%);">
                    <div class="position-absolute" style="width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; top: -60px; right: -60px;"></div>

                    <div class="d-flex align-items-center mb-4 position-relative z-1">
                        <div class="bg-white text-success rounded-circle d-flex justify-content-center align-items-center shadow-lg me-3 position-relative" style="width: 65px; height: 65px; min-width: 65px;">
                            <i class="fab fa-whatsapp" style="font-size: 2.3rem;"></i>
                            <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle shadow animate__animated animate__pulse animate__infinite"></span>
                        </div>
                        <h5 class="fw-bold mb-0">تواصل معنا</h5>
                    </div>

                    <div class="position-relative z-1 text-start mt-auto d-flex flex-column gap-2">
                        <a href="tel:+967775065459" class="d-flex justify-content-between align-items-center bg-white bg-opacity-10 p-2 px-3 rounded border border-light border-opacity-25 text-white text-decoration-none transition-hover">
                            <span class="fw-bold"><i class="fas fa-phone-alt me-2 opacity-75"></i> إتصال</span>
                            <span class="fw-bold fs-5" dir="ltr" style="letter-spacing: 1px;">+967 775065459</span>
                        </a>
                        <a href="https://wa.me/967774456261" target="_blank" class="d-flex justify-content-between align-items-center bg-white bg-opacity-10 p-2 px-3 rounded border border-light border-opacity-25 text-white text-decoration-none transition-hover">
                            <span class="fw-bold"><i class="fab fa-whatsapp me-2 opacity-75"></i> واتساب</span>
                            <span class="fw-bold fs-5" dir="ltr" style="letter-spacing: 1px;">+967 774456261</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="col-md-6 col-xl-4">
                <div class="contact-card p-4 shadow-lg h-100 position-relative text-white d-flex flex-column" style="background: linear-gradient(135deg, #2b32b2 0%, #1488cc 100%);">
                    <div class="position-absolute" style="width: 200px; height: 200px; background: rgba(255,255,255,0.06); border-radius: 50%; bottom: -60px; left: -60px;"></div>

                    <div class="d-flex align-items-center mb-4 position-relative z-1">
                        <div class="bg-white text-primary rounded-circle d-flex justify-content-center align-items-center shadow-lg me-3" style="width: 65px; height: 65px; min-width: 65px;">
                            <i class="fas fa-map-marked-alt" style="font-size: 2.2rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-0">موقعنا</h5>
                    </div>

                    <div class="position-relative z-1 text-center mt-auto mb-3">
                        <a href="https://www.google.com/maps/place/%D9%85%D8%A7%D8%AC%D8%AF+%D9%88+%D8%A7%D9%84%D9%82%D8%A7%D8%AF%D8%B1%D9%8A+%D9%84%D9%84%D9%82%D8%A7%D8%AF%D8%B1%D9%8A+%D9%84%D9%84%D9%82%D8%AAT%E2%80%AD/@13.9591085,44.166979,16z/data=!4m10!1m2!2m1!1z2KfZhNmK2YXZhiDYp9ioINiz2YjZgiDYp9mE2LPZhNin2YUg2YXYrdmEINin2YTZgtin2K_YsdmK!3m6!1s0x161ceb6ce6360fdf:0xd257666c370d57ce!8m2!3d13.9587983!4d44.1716561!15sCjnYp9mE2YrZhdmGINin2Kgg2LPZiNmCINin2YTYs9mE2KfZhSDZhdit2YQg2KfZhNmC2KfYr9ix2YqSAQZtYXJrZXTgAQA!16s%2Fg%2F11rgbn4lj5?entry=ttu&g_ep=EgoyMDI2MDUwNi4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="text-white text-decoration-none transition-hover d-block">
                            <div class="fw-bold mb-3" style="font-size: 1.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">إب - شارع الدائري - سوق السلام</div>
                            <div class="bg-white bg-opacity-10 py-2 rounded-pill border border-light border-opacity-25 small fw-bold">
                                <i class="fas fa-directions me-1"></i> عرض على الخريطة
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Accounts -->
            <div class="col-md-6 col-xl-4">
                <div class="contact-card p-4 shadow-lg h-100 position-relative text-white d-flex flex-column" style="background: linear-gradient(135deg, #aa076b 0%, #61045f 100%);">
                    <div class="position-absolute" style="width: 150px; height: 150px; background: rgba(255,255,255,0.08); border-radius: 50%; top: -40px; right: -40px;"></div>

                    <div class="d-flex align-items-center mb-3 position-relative z-1">
                        <div class="bg-white text-danger rounded-circle d-flex justify-content-center align-items-center shadow-lg me-3" style="width: 65px; height: 65px; min-width: 65px;">
                            <i class="fas fa-wallet" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-0">أرقام حساباتنا</h5>
                    </div>

                    <div class="position-relative z-1 text-start d-flex flex-column gap-2 mt-auto">
                        <div class="d-flex justify-content-between align-items-center bg-white bg-opacity-10 p-2 px-3 rounded border border-light border-opacity-25">
                            <div class="d-flex align-items-center gap-2">
                                <img src="public/logos/jeeb.png" alt="Jeeb" class="rounded shadow-sm" style="width: 30px; height: 30px; object-fit: contain; background: white; padding: 2px;">
                                <span class="fw-bold">جيب</span>
                            </div>
                            <span class="fw-bold fs-5" dir="ltr" style="letter-spacing: 1px;">774456261</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center bg-white bg-opacity-10 p-2 px-3 rounded border border-light border-opacity-25">
                            <div class="d-flex align-items-center gap-2">
                                <img src="public/logos/jawali.png" alt="Jawali" class="rounded shadow-sm" style="width: 30px; height: 30px; object-fit: cover; background: white; padding: 2px;">
                                <span class="fw-bold">جوالي</span>
                            </div>
                            <span class="fw-bold fs-5" dir="ltr" style="letter-spacing: 1px;">774456261</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center bg-white bg-opacity-10 p-2 px-3 rounded border border-light border-opacity-25">
                            <div class="d-flex align-items-center gap-2">
                                <img src="public/logos/kuraimi.png" alt="Kuraimi" class="rounded shadow-sm" style="width: 30px; height: 30px; object-fit: contain; background: white; padding: 2px;">
                                <span class="fw-bold">كريمي</span>
                            </div>
                            <span class="fw-bold fs-5" dir="ltr" style="letter-spacing: 1px;">121940835</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Row -->
        <div class="text-center animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
            <p class="fw-bold text-muted mb-4 fs-5"><i class="fas fa-globe me-2 text-secondary"></i> لا تفوّت جديدنا، تابعنا على الشبكات الاجتماعية:</p>
            <div class="d-flex justify-content-center flex-wrap gap-4 pb-3">
                <a href="https://www.facebook.com/share/1D4qehndSj/" target="_blank" class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                    <span>فيسبوك</span>
                </a>
                <a href="https://www.instagram.com/alqadri.2025?igsh=MW94N2NvOW85eGpveg==" target="_blank" class="social-btn instagram">
                    <i class="fab fa-instagram"></i>
                    <span>إنستغرام</span>
                </a>
            </div>
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
                                            <?php if (($p['unit_type'] ?? 'weight') === 'weight'): ?>
                                                <span class="badge bg-success text-white rounded-pill px-3 py-2">
                                                    <?= number_format($p['total_kg'], 2) ?> كجم
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success text-white rounded-pill px-3 py-2">
                                                    <?= number_format($p['total_units']) ?> <?= htmlspecialchars($p['unit_type'] ?: 'حبة') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted x-small mb-3">
                                            <i class="fas fa-clock me-1"></i> وصل: <?= date('h:i A', strtotime($p['last_received'])) ?>
                                        </div>
                                        <div class="mt-auto">
                                            <a href="https://wa.me/967774456261?text=<?= urlencode('أريد استفسار عن قات ' . $p['type_name']) ?>" 
                                               target="_blank" 
                                               class="btn btn-success rounded-pill w-100 fw-bold shadow-sm py-2">
                                                <i class="fab fa-whatsapp me-2"></i> اطلب عبر واتساب
                                            </a>
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