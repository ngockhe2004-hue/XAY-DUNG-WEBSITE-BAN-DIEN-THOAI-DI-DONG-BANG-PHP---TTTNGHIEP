<?php
ob_start();
require_once __DIR__ . '/includes/auth_admin.php';

// Xác định tab hiện tại
$tab = sanitize($_GET['tab'] ?? 'users');
$allowed_tabs = ['users', 'reviews'];
if (!in_array($tab, $allowed_tabs)) $tab = 'users';

// Xử lý POST request từ các tab file trước khi gửi HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_included_mode = true;
    include __DIR__ . '/' . $tab . '.php';
    exit();
}

// Đặt tiêu đề trang dựa trên tab
$pageTitle = 'Quản lý Khách hàng';
if ($tab === 'reviews') $pageTitle = 'Duyệt Đánh Giá';

require_once __DIR__ . '/includes/header.php';

// Lấy số lượng đánh giá chờ duyệt để hiển thị badge trên tab
$pendingReviewsCount = db()->fetchColumn("SELECT COUNT(*) FROM danhgia WHERE trang_thai = 'cho_duyet'");
?>

<div class="customer-management-container">
    <div class="page-header" style="margin-bottom: 20px;">
        <h1 class="page-title">👥 Hệ Thống Quản Lý Khách Hàng</h1>
    </div>

    <!-- Tab Navigation -->
    <div class="settings-tabs" style="display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <a href="?tab=users" class="tab-item <?= $tab === 'users' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; color: <?= $tab === 'users' ? 'var(--accent)' : 'var(--txt2)' ?>; background: <?= $tab === 'users' ? 'rgba(108, 99, 255, 0.1)' : 'transparent' ?>;">
            👥 Người dùng
        </a>
        <a href="?tab=reviews" class="tab-item <?= $tab === 'reviews' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; color: <?= $tab === 'reviews' ? 'var(--accent)' : 'var(--txt2)' ?>; background: <?= $tab === 'reviews' ? 'rgba(108, 99, 255, 0.1)' : 'transparent' ?>; display: flex; align-items: center; gap: 8px;">
            ⭐ Đánh giá
            <?php if ($pendingReviewsCount > 0): ?>
                <span class="badge badge-danger" style="font-size: 10px; padding: 2px 6px;"><?= $pendingReviewsCount ?></span>
            <?php endif; ?>
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
ob_end_flush();
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
