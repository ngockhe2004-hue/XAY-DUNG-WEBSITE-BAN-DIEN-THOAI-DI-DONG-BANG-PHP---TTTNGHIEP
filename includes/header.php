<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$cartCount = isLoggedIn() ? getCartCount() : 0;
$categories = db()->fetchAll("SELECT * FROM danhmuc WHERE is_active = 1 ORDER BY thu_tu ASC LIMIT 8");
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' . SITE_NAME : SITE_NAME ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? sanitize($pageDesc) : 'Mua điện thoại chính hãng, giá tốt nhất, bảo hành uy tín' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
</head>
<body>

<!-- Top Promotion Bar -->
<div class="top-promo-bar">
    <div class="container">
        <div class="promo-content">
            <span class="promo-item">🚀 Miễn phí vận chuyển đơn từ 500K</span>
            <span class="promo-divider">|</span>
            <span class="promo-item">💎 Bảo hành chính hãng 12 tháng</span>
            <span class="promo-divider">|</span>
            <span class="promo-item">📞 Hotline: 1800 6789</span>
        </div>
    </div>
</div>

<!-- Main Header Area -->
<header class="main-header-v2">
    <div class="container">
        <div class="header-wrapper">
            <!-- Logo Section -->
            <a href="<?= BASE_URL ?>/index.php" class="logo-v2">
                <div class="logo-icon-wrap logo-animate-pulse">📱</div>
                <div class="logo-text-wrap">
                    <span class="logo-name logo-shimmer-text">PhoneStore</span>
                    <span class="logo-slogan">CHẤT LƯỢNG THẬT - GIÁ TRỊ THẬT</span>
                </div>
            </a>

            <!-- Search Section -->
            <div class="header-search">
                <form action="<?= BASE_URL ?>/products.php" method="GET" class="search-form-v2">
                    <input type="text" name="q" placeholder="Bạn cần tìm gì hôm nay?..." value="<?= sanitize($_GET['q'] ?? '') ?>">
                    <button type="submit">🔍</button>
                </form>
            </div>

            <!-- Actions Section -->
            <div class="header-actions-v2">
                <a href="<?= BASE_URL ?>/cart.php" class="action-item cart-trigger">
                    <div class="icon-box">
                        <span class="icon">🛒</span>
                        <span class="badge" id="cartCount"><?= $cartCount ?></span>
                    </div>
                    <span class="label">Giỏ hàng</span>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/profile.php" class="action-item">
                        <div class="icon-box">👤</div>
                        <span class="label">Tài khoản</span>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="action-item">
                        <div class="icon-box">👤</div>
                        <span class="label">Đăng nhập</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Navigation -->
<nav class="main-nav">
    <div class="container">
        <ul class="nav-list">
            <li><a href="<?= BASE_URL ?>/index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Trang chủ</a></li>
            <?php foreach ($categories as $cat): ?>
            <li>
                <a href="<?= BASE_URL ?>/products.php?danhmuc=<?= $cat['slug'] ?>" 
                   <?= (isset($_GET['danhmuc']) && $_GET['danhmuc'] == $cat['slug']) ? 'class="active"' : '' ?>>
                    <?= sanitize($cat['ten_danhmuc']) ?>
                </a>
            </li>
            <?php endforeach; ?>
            <li><a href="<?= BASE_URL ?>/products.php?sale=1" class="sale-link">🔥 Sale</a></li>
            <li><a href="<?= BASE_URL ?>/gioi-thieu.php" <?= basename($_SERVER['PHP_SELF']) == 'gioi-thieu.php' ? 'class="active"' : '' ?>>Giới thiệu</a></li>
            <li><a href="<?= BASE_URL ?>/lien-he.php" <?= basename($_SERVER['PHP_SELF']) == 'lien-he.php' ? 'class="active"' : '' ?>>Liên hệ</a></li>
        </ul>
    </div>
</nav>

<!-- Flash Messages -->
<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
    <div class="container">
        <span><?= sanitize($flash['message']) ?></span>
        <button onclick="this.parentElement.parentElement.remove()">✕</button>
    </div>
</div>
<?php endif; ?>
