<?php
ob_start();
require_once __DIR__ . '/includes/auth_admin.php';

// Xác định tab hiện tại
$tab = sanitize($_GET['tab'] ?? 'products');
$allowed_tabs = ['products', 'categories', 'brands'];
if (!in_array($tab, $allowed_tabs)) $tab = 'products';

// Xử lý POST request từ các tab file trước khi gửi HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_included_mode = true;
    include __DIR__ . '/' . $tab . '.php';
    exit();
}

// Đặt tiêu đề trang dựa trên tab
$pageTitle = 'Cấu hình Sản phẩm';
if ($tab === 'categories') $pageTitle = 'Quản lý Danh mục';
if ($tab === 'brands') $pageTitle = 'Quản lý Thương hiệu';

require_once __DIR__ . '/includes/header.php';
?>

<div class="product-settings-container">
    <div class="page-header" style="margin-bottom: 20px;">
        <h1 class="page-title">🛠️ Cấu Hình Hệ Thống Sản Phẩm</h1>
    </div>

    <!-- Tab Navigation -->
    <div class="settings-tabs" style="display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <a href="?tab=products" class="tab-item <?= $tab === 'products' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; color: <?= $tab === 'products' ? 'var(--purple)' : 'var(--txt2)' ?>; background: <?= $tab === 'products' ? 'var(--purple-light)' : 'transparent' ?>;">
            📦 Sản phẩm
        </a>
        <a href="?tab=categories" class="tab-item <?= $tab === 'categories' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; color: <?= $tab === 'categories' ? 'var(--purple)' : 'var(--txt2)' ?>; background: <?= $tab === 'categories' ? 'var(--purple-light)' : 'transparent' ?>;">
            🗂️ Danh mục
        </a>
        <a href="?tab=brands" class="tab-item <?= $tab === 'brands' ? 'active' : '' ?>" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; color: <?= $tab === 'brands' ? 'var(--purple)' : 'var(--txt2)' ?>; background: <?= $tab === 'brands' ? 'var(--purple-light)' : 'transparent' ?>;">
            🏷️ Thương hiệu
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
    background: var(--purple-light) !important;
    color: var(--purple) !important;
}
.tab-item.active::after {
    content: '';
    position: absolute;
    bottom: -11px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--purple);
    border-radius: 10px 10px 0 0;
}
.tab-item { position: relative; transition: 0.3s; }
</style>
