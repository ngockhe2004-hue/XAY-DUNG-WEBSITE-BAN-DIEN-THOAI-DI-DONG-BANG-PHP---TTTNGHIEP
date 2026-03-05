<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
$maOrder = (int)($_GET['id'] ?? 0);
$order = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang = ? AND ma_user = ?", [$maOrder, $_SESSION['user_site']['id']]);
if (!$order) redirect(BASE_URL . '/orders.php');
$pageTitle = 'Đặt Hàng Thành Công';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container success-page">
    <div class="success-card">
        <div class="success-icon">🎉</div>
        <h1 class="success-title">Đặt Hàng Thành Công!</h1>
        <p style="color:var(--text-secondary);">Cảm ơn bạn đã tin tưởng PhoneStore.<br>Đơn hàng của bạn đang được xử lý.</p>
        <div class="success-code"><?= sanitize($order['ma_donhang_code']) ?></div>
        <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:16px;margin:16px 0;text-align:left;font-size:14px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:var(--text-muted)">Tổng tiền:</span>
                <strong style="color:var(--accent)"><?= formatPrice($order['tong_thanh_toan']) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:var(--text-muted)">Thanh toán:</span>
                <span><?= strtoupper($order['phuong_thuc_TT']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted)">Trạng thái:</span>
                <span class="order-status-badge status-cho_xac_nhan">Chờ xác nhận</span>
            </div>
        </div>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/order_detail.php?id=<?= $maOrder ?>" class="btn btn-primary">📋 Xem đơn hàng</a>
            <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline">🛒 Tiếp tục mua sắm</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
