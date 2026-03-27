<?php
$pageTitle = 'Danh Sách Yêu Thích';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = $_SESSION['user_site']['id'];
$wishedProducts = [];
$products = db()->fetchAll("
    SELECT sp.ma_sanpham, sp.ten_sanpham, sp.diem_danh_gia, sp.tong_da_ban, sp.is_hang_moi, sp.is_noi_bat,
           sp.gia_goc, sp.gia_khuyen_mai,
           th.ten_thuonghieu,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh_chinh,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_thap,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_thap_nhat,
           (SELECT MAX(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_cao_nhat,
           (SELECT MIN(gia_goc) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_goc_thap_nhat,
           (SELECT COUNT(*) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND ton_kho > 0) as so_bienthe_conhang,
           (SELECT COUNT(DISTINCT mau_sac) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham) as so_mau
    FROM dsyeuthich dy
    JOIN sanpham sp ON dy.ma_sanpham = sp.ma_sanpham
    JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
    WHERE dy.ma_user = ? AND sp.is_active = 1
    ORDER BY dy.ngay_them DESC
", [$userId]);
$wishedProducts = array_column($products, 'ma_sanpham');
?>

<div class="container" style="padding:32px 0 60px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
        <div>
            <h1 class="page-title">❤️ Sản Phẩm Yêu Thích</h1>
            <p style="color:var(--text-secondary);"><?= count($products) ?> sản phẩm</p>
        </div>
        <?php if (!empty($products)): ?>
        <button class="btn btn-outline" style="color:var(--danger); border-color:var(--danger);" onclick="clearAllWishlist()">
            🗑️ Xóa tất cả
        </button>
        <?php endif; ?>
    </div>
    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">💔</div>
        <h3 class="empty-title">Chưa có sản phẩm yêu thích</h3>
        <p>Khám phá và lưu những điện thoại bạn yêu thích!</p>
        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary" style="margin-top:20px;">Khám phá ngay →</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $p): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
async function clearAllWishlist() {
    if (!confirm('⚠️ Bạn có chắc chắn muốn xóa toàn bộ danh sách yêu thích?')) return;
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/wishlist.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'clear_all'})
        });
        const data = await res.json();
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('🗑️ Đã xóa toàn bộ danh sách yêu thích!', 'success');
            }
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch(e) {
        console.error(e);
        alert('Lỗi kết nối!');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
