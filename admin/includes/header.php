<?php
// Admin Header - Include sau khi require auth_admin
$adminUser = getCurrentUser();
$pendingOrders  = db()->fetchColumn("SELECT COUNT(*) FROM donhang WHERE trang_thai = 'cho_xac_nhan'");
$pendingReviews = db()->fetchColumn("SELECT COUNT(*) FROM danhgia WHERE trang_thai = 'cho_duyet'");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | Admin' : 'Admin Panel - PhoneStore' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="admin-main">

<!-- New Top Navigation -->
<header class="admin-topbar-nav">
    <a href="<?= BASE_URL ?>/admin/reports_stats.php" class="top-nav-logo" style="text-decoration:none;">
        <div class="icon logo-animate-pulse">📱</div>
        <div class="logo-text">
            <div class="name logo-shimmer-text">PHONESTORE</div>
            <div style="font-size: 10px; opacity: 0.8; letter-spacing: 1px; color: #fff;">SYSTEM MANAGEMENT</div>
        </div>
    </a>

    <nav class="top-nav-links">
        <a href="<?= BASE_URL ?>/admin/reports_stats.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'reports_stats.php' || basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
            📊 <span>Báo Cáo, Thống Kê</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/product_settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'product_settings.php' ? 'active' : '' ?>">
            ⚙️ <span>Cấu Hình Sản Phẩm</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/sales_management.php" class="<?= basename($_SERVER['PHP_SELF']) == 'sales_management.php' ? 'active' : '' ?>">
            🛒 <span>Quản Lý Đơn Hàng</span>
            <?php if ($pendingOrders > 0): ?><span class="badge badge-danger" style="margin-left:5px;"><?= $pendingOrders ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/customer_management.php" class="<?= basename($_SERVER['PHP_SELF']) == 'customer_management.php' ? 'active' : '' ?>">
            👥 <span>Quản Lý Khách Hàng</span>
            <?php if ($pendingReviews > 0): ?><span class="badge badge-danger" style="margin-left:5px;"><?= $pendingReviews ?></span><?php endif; ?>
        </a>
    </nav>

    <div class="top-nav-user" onclick="location.href='<?= BASE_URL ?>/admin/logout.php'">
        <div class="avatar"><?= mb_strtoupper(mb_substr($adminUser['hovaten'] ?? 'A', 0, 1)) ?></div>
        <div style="text-align: left;">
            <div style="font-size: 13px; font-weight: 700;"><?= sanitize($adminUser['hovaten'] ?? 'Admin') ?></div>
            <div style="font-size: 10px; opacity: 0.8; font-weight: 600;">ADMIN ▼</div>
        </div>
    </div>
</header>

<div class="welcome-banner">
    ✨ 👍 Chào mừng trở lại, Quản trị viên! ⭐️ ✨
</div>

<div class="admin-topbar">
    <div class="topbar-left">
        <div class="page-breadcrumb">
            ADMIN / <strong style="text-transform: uppercase;"><?= isset($pageTitle) ? sanitize($pageTitle) : 'DASHBOARD' ?></strong>
        </div>
    </div>
    <div class="topbar-right">
         <div style="background: #fff; padding: 10px 15px; border-radius: 12px; display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow);">
            <span style="font-size: 20px;">📅</span>
            <div style="text-align: left;">
                <div style="font-size: 10px; color: var(--txt3); font-weight: 700;">CẬP NHẬT HÔM NAY</div>
                <div style="font-size: 14px; font-weight: 800; color: var(--accent);"><?= date('j/n/Y') ?></div>
            </div>
         </div>
    </div>
</div>

<div class="admin-content">
<?php if ($flash = getFlash()): ?>
<div style="padding:12px 16px;border-radius:var(--r);margin-bottom:20px;font-size:14px;
    background:<?= $flash['type']=='success'?'rgba(34,197,94,0.1)':'rgba(239,68,68,0.1)' ?>;
    border:1px solid <?= $flash['type']=='success'?'rgba(34,197,94,0.3)':'rgba(239,68,68,0.3)' ?>;
    color:<?= $flash['type']=='success'?'var(--success)':'var(--danger)' ?>;">
    <?= sanitize($flash['message']) ?>
</div>
<?php endif; ?>
