<?php
ob_start();
require_once __DIR__ . '/includes/auth_admin.php';

// Xác định tab hiện tại
$tab = sanitize($_GET['tab'] ?? 'orders');
$allowed_tabs = ['orders', 'payments', 'coupons'];
if (!in_array($tab, $allowed_tabs)) $tab = 'orders';

// Xử lý POST request từ các tab file trước khi gửi HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_included_mode = true;
    include __DIR__ . '/' . $tab . '.php';
    exit();
}

// Đặt tiêu đề trang dựa trên tab
$pageTitle = 'Quản lý Bán hàng';
if ($tab === 'payments') $pageTitle = 'Quản lý Thanh toán';
if ($tab === 'coupons') $pageTitle = 'Quản lý Mã Khuyến Mãi';

require_once __DIR__ . '/includes/header.php';
?>

<div class="sales-management-container">
    <div class="page-header" style="margin-bottom: 20px;">
        <h1 class="page-title">💰 Hệ Thống Quản Lý Bán Hàng</h1>
    </div>

    <!-- Tab Navigation -->
    <div class="settings-tabs" style="display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <a href="?tab=orders" class="tab-item <?= $tab === 'orders' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; color: <?= $tab === 'orders' ? 'var(--accent)' : 'var(--txt2)' ?>; background: <?= $tab === 'orders' ? 'rgba(108, 99, 255, 0.1)' : 'transparent' ?>;">
            🛒 Đơn hàng
        </a>
        <a href="?tab=payments" class="tab-item <?= $tab === 'payments' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; color: <?= $tab === 'payments' ? 'var(--accent)' : 'var(--txt2)' ?>; background: <?= $tab === 'payments' ? 'rgba(108, 99, 255, 0.1)' : 'transparent' ?>;">
            💳 Thanh toán
        </a>
        <a href="?tab=coupons" class="tab-item <?= $tab === 'coupons' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; color: <?= $tab === 'coupons' ? 'var(--accent)' : 'var(--txt2)' ?>; background: <?= $tab === 'coupons' ? 'rgba(108, 99, 255, 0.1)' : 'transparent' ?>;">
            🎟️ Mã KM
        </a>
    </div>

    <div class="tab-content-wrapper">
        <?php
        $target_file = $tab . '.php';
        $is_included_mode = true;
        
        include __DIR__ . '/' . $target_file;
        ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

<style>
.tab-item:hover {
    background: rgba(108, 99, 255, 0.05) !important;
}
.tab-item.active::after {
    content: '';
    position: absolute;
    bottom: -11px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--accent);
    border-radius: 10px 10px 0 0;
}
.tab-item { position: relative; }
</style>
<?php ob_end_flush(); ?>
