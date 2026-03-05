<?php
$pageTitle = 'PhoneStore - Điện Thoại Chính Hãng, Giá Tốt';
$pageDesc  = 'Mua điện thoại chính hãng Apple, Samsung, Xiaomi, OPPO - Giá tốt nhất, bảo hành uy tín, giao hàng nhanh';
require_once __DIR__ . '/includes/header.php';

// Lấy dữ liệu trang chủ
$featuredProducts = db()->fetchAll("
    SELECT v.*, 
           th.ten_thuonghieu,
           sp.gia_goc, sp.gia_khuyen_mai, sp.man_hinh_size, sp.man_hinh_dophangiai
    FROM v_sanpham_tongquan v
    JOIN sanpham sp ON v.ma_sanpham = sp.ma_sanpham
    JOIN thuonghieu th ON v.ten_thuonghieu = th.ten_thuonghieu
    WHERE v.is_active = 1 AND v.is_noi_bat = 1
    ORDER BY v.tong_da_ban DESC
    LIMIT 8
");
$newProducts = db()->fetchAll("
    SELECT v.*, th.ten_thuonghieu, 
           sp.gia_goc, sp.gia_khuyen_mai, sp.man_hinh_size, sp.man_hinh_dophangiai
    FROM v_sanpham_tongquan v
    JOIN sanpham sp ON v.ma_sanpham = sp.ma_sanpham
    JOIN thuonghieu th ON v.ten_thuonghieu = th.ten_thuonghieu
    WHERE v.is_active = 1 AND v.is_hang_moi = 1
    ORDER BY v.ngay_lap DESC
    LIMIT 8
");
$hotProducts = db()->fetchAll("
    SELECT v.*, th.ten_thuonghieu,
           sp.gia_goc, sp.gia_khuyen_mai, sp.man_hinh_size, sp.man_hinh_dophangiai
    FROM v_sanpham_tongquan v
    JOIN sanpham sp ON v.ma_sanpham = sp.ma_sanpham
    JOIN thuonghieu th ON v.ten_thuonghieu = th.ten_thuonghieu
    WHERE v.is_active = 1
    ORDER BY v.tong_da_ban DESC LIMIT 8
");
$brands = db()->fetchAll("SELECT * FROM thuonghieu WHERE is_active = 1 LIMIT 10");
$categories = db()->fetchAll("SELECT dm.*, (SELECT COUNT(*) FROM sanpham_danhmuc spdm JOIN sanpham sp ON spdm.ma_sanpham = sp.ma_sanpham WHERE spdm.ma_danhmuc = dm.ma_danhmuc AND sp.is_active = 1) as so_sp FROM danhmuc dm WHERE dm.is_active = 1 ORDER BY dm.thu_tu LIMIT 8");

// Wishlist của user
$wishedProducts = [];
if (isLoggedIn()) {
    $wished = db()->fetchAll("SELECT ma_sanpham FROM dsyeuthich WHERE ma_user = ?", [$_SESSION['user_site']['id']]);
    $wishedProducts = array_column($wished, 'ma_sanpham');
}

$categoryIcons = ['iPhone'=>'🍎','Samsung Galaxy'=>'📱','Điện thoại Xiaomi'=>'🔴','OPPO'=>'🟢','Điện thoại phổ thông'=>'📟','Flagship'=>'👑','Tầm trung'=>'⚡','Gaming Phone'=>'🎮'];
?>

<!-- HERO SLIDER SECTION -->
<section class="hero-slider-v2">
    <div class="container">
        <div class="slider-wrapper">
            <div class="main-slider">
                <div class="slide-item active" style="background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.1)), url('https://images.unsplash.com/photo-1616348436168-de43ad0db179?q=80&w=1200') center/cover;">
                    <div class="slide-content">
                        <h2>iPhone 16 Pro Max</h2>
                        <p>Nâng tầm trải nghiệm với chip A18 Pro mãnh mẽ nhất.</p>
                        <a href="#" class="btn-slide">Mua ngay</a>
                    </div>
                </div>
            </div>
            <div class="sub-banners">
                <div class="sub-item" style="background: #fff3f3 url('https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?q=80&w=400') center/cover;"></div>
                <div class="sub-item" style="background: #f0f7ff url('https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?q=80&w=400') center/cover;"></div>
            </div>
        </div>
    </div>
</section>

<!-- BRAND LOGOS (Bubble Style) -->
<section class="brands-section-v2">
    <div class="container">
        <div class="brands-flex">
            <?php foreach ($brands as $brand): 
                $brandIcons = ['Apple'=>'🍎','Samsung'=>'💙','Xiaomi'=>'🔴','OPPO'=>'🟢','Vivo'=>'💜','Realme'=>'🟡','OnePlus'=>'🔴','Nokia'=>'🔵','Motorola'=>'🤝','Google'=>'🌈'];
                $icon = $brandIcons[$brand['ten_thuonghieu']] ?? '📱';
            ?>
            <a href="<?= BASE_URL ?>/products.php?thuonghieu=<?= $brand['slug'] ?>" class="brand-item-v2">
                <span class="b-icon"><?= $icon ?></span>
                <span class="b-name"><?= sanitize($brand['ten_thuonghieu']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FLASH SALE SECTION -->
<section class="flash-sale-v2">
    <div class="container">
        <div class="fs-header">
            <div class="fs-title">
                <span class="fs-icon">⚡</span>
                <h2>FLASH SALE</h2>
                <div class="fs-timer">00 : 45 : 12</div>
            </div>
            <a href="#" class="fs-more">Xem tất cả ></a>
        </div>
        <div class="fs-grid">
            <?php 
            $saleProducts = array_slice($featuredProducts, 0, 5);
            foreach ($saleProducts as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CATEGORY GRID -->
<section class="category-section-v2">
    <div class="container">
        <h2 class="section-title-v2">DANH MỤC NỔI BẬT</h2>
        <div class="category-grid-v2">
            <?php foreach ($categories as $cat):
                $icon = $categoryIcons[$cat['ten_danhmuc']] ?? '📱';
            ?>
            <a href="<?= BASE_URL ?>/products.php?danhmuc=<?= $cat['slug'] ?>" class="cat-item-v2">
                <div class="cat-icon-v2"><?= $icon ?></div>
                <div class="cat-name-v2"><?= sanitize($cat['ten_danhmuc']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PROMO BANNER -->
<section style="padding:40px 0;">
    <div class="container">
        <div style="background:var(--gradient-main);border-radius:var(--radius-xl);padding:48px;display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;">
            <div>
                <h2 style="font-size:28px;font-weight:800;color:#fff;margin-bottom:8px;">🎉 Flash Sale Hàng Ngày</h2>
                <p style="color:rgba(255,255,255,0.85);font-size:16px;margin-bottom:20px;">Giảm đến 50% điện thoại các hãng. Số lượng có hạn!</p>
                <a href="<?= BASE_URL ?>/products.php?sale=1" class="btn" style="background:#fff;color:var(--accent);font-weight:700;">Xem Ngay 🔥</a>
            </div>
            <div style="font-size:100px;opacity:0.5;">⚡</div>
        </div>
    </div>
</section>

<!-- NEW PRODUCTS -->
<?php if ($newProducts): ?>
<section class="section">
    <div class="container">
        <div class="section-header-row">
            <div>
                <span class="section-tag">Mới Về</span>
                <h2>Hàng Mới Nhất</h2>
            </div>
            <a href="<?= BASE_URL ?>/products.php?new=1" class="btn btn-outline">Xem tất cả →</a>
        </div>
        <div class="products-grid">
            <?php foreach ($newProducts as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- HOT PRODUCTS -->
<section class="section" style="background:var(--bg-secondary);border-top:1px solid var(--border);">
    <div class="container">
        <div class="section-header-row">
            <div>
                <span class="section-tag" style="background:rgba(239,68,68,0.15);color:var(--danger)">🔥 Bán Chạy</span>
                <h2>Điện Thoại Bán Chạy</h2>
            </div>
            <a href="<?= BASE_URL ?>/products.php?sort=ban_chay" class="btn btn-outline">Xem tất cả →</a>
        </div>
        <div class="products-grid">
            <?php foreach ($hotProducts as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Cam Kết</span>
            <h2 class="section-title">Tại Sao Chọn PhoneStore?</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;">
            <?php
            $features = [
                ['🏆','Hàng Chính Hãng','100% sản phẩm chính hãng, tem bảo hành rõ ràng'],
                ['🚚','Giao Hàng Nhanh','Giao trong 2-4 giờ nội thành, 1-3 ngày toàn quốc'],
                ['🔄','Đổi Trả 30 Ngày','Đổi trả miễn phí trong 30 ngày nếu lỗi nhà sản xuất'],
                ['💰','Giá Tốt Nhất','Cam kết giá tốt nhất thị trường, hoàn tiền chênh lệch'],
            ];
            foreach ($features as $f):
            ?>
            <div class="card" style="text-align:center;padding:32px 24px;">
                <div style="font-size:48px;margin-bottom:16px;"><?= $f[0] ?></div>
                <h3 style="font-size:17px;font-weight:700;margin-bottom:10px;"><?= $f[1] ?></h3>
                <p style="color:var(--text-muted);font-size:14px;line-height:1.6;"><?= $f[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
