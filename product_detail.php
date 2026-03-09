<?php
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/products.php'); }

// Lấy sản phẩm
$product = db()->fetchOne("
    SELECT sp.*, th.ten_thuonghieu, th.slug as thuonghieu_slug, dm.ten_danhmuc, dm.slug as danhmuc_slug
    FROM sanpham sp
    JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
    JOIN danhmuc dm ON sp.ma_danhmuc = dm.ma_danhmuc
    WHERE sp.ma_sanpham = ? AND sp.is_active = 1
", [$id]);

if (!$product) {
    http_response_code(404);
    echo '<div class="container" style="padding:60px;text-align:center"><h2>Sản phẩm không tồn tại</h2><a href="'.BASE_URL.'/products.php" class="btn btn-primary">Xem sản phẩm khác</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Lấy danh sách danh mục (mới)
$product_categories = db()->fetchAll("
    SELECT dm.ten_danhmuc, dm.slug 
    FROM danhmuc dm
    JOIN sanpham_danhmuc spdm ON dm.ma_danhmuc = spdm.ma_danhmuc
    WHERE spdm.ma_sanpham = ?
", [$id]);

// Tăng lượt xem
db()->execute("UPDATE sanpham SET tong_luot_xem = tong_luot_xem + 1 WHERE ma_sanpham = ?", [$id]);
if (isLoggedIn()) {
    db()->execute("INSERT INTO lichsu_xem_sp (ma_user, ma_sanpham) VALUES (?,?) ON DUPLICATE KEY UPDATE ngay_xem = NOW()", [$_SESSION['user_site']['id'], $id]);
}

// Biến thể
$variants = db()->fetchAll("SELECT * FROM bienthe_sanpham WHERE ma_sanpham = ? AND is_active = 1 ORDER BY ram_gb, rom_gb", [$id]);

// Hình ảnh (Ưu tiên ảnh biến thể lên trước, sau đó đến ảnh chính, cuối cùng là thứ tự thu_tu)
$images = db()->fetchAll("
    SELECT h.*, b.mau_sac as variant_color 
    FROM hinhanh_sanpham h 
    LEFT JOIN bienthe_sanpham b ON h.ma_bienthe = b.ma_bienthe 
    WHERE h.ma_sanpham = ? 
    ORDER BY (h.ma_bienthe IS NULL) ASC, h.la_anh_chinh DESC, h.thu_tu ASC
", [$id]);

// Đánh giá
$reviews = db()->fetchAll("
    SELECT dg.*, u.ten_user, u.hovaten
    FROM danhgia dg JOIN users u ON dg.ma_user = u.ma_user
    WHERE dg.ma_sanpham = ? AND dg.trang_thai = 'da_duyet'
    ORDER BY dg.ngay_lap DESC LIMIT 20
", [$id]);

// Tổng hợp đánh giá
$ratingStats = db()->fetchOne("
    SELECT COUNT(*) as total, AVG(diem) as avg_rating,
           SUM(diem=5) as s5, SUM(diem=4) as s4, SUM(diem=3) as s3, SUM(diem=2) as s2, SUM(diem=1) as s1
    FROM danhgia WHERE ma_sanpham = ? AND trang_thai = 'da_duyet'
", [$id]);

// SP liên quan
$related = db()->fetchAll("
    SELECT sp.ma_sanpham, sp.ten_sanpham, sp.diem_danh_gia, sp.tong_da_ban, sp.is_hang_moi, sp.is_noi_bat,
           sp.gia_goc, sp.gia_khuyen_mai, sp.man_hinh_size, sp.man_hinh_dophangiai,
           th.ten_thuonghieu, 
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh_chinh,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_thap_nhat,
           (SELECT MAX(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_cao_nhat,
           (SELECT MIN(gia_goc) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_goc_thap_nhat,
           (SELECT COUNT(*) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND ton_kho > 0) as so_bienthe_conhang,
           (SELECT COUNT(DISTINCT mau_sac) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham) as so_mau
    FROM sanpham sp JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
    WHERE sp.ma_danhmuc = ? AND sp.ma_sanpham != ? AND sp.is_active = 1
    ORDER BY sp.tong_da_ban DESC LIMIT 4
", [$product['ma_danhmuc'], $id]);

// Wishlist
$wishedProducts = [];
$isWished = false;
if (isLoggedIn()) {
    $wished = db()->fetchAll("SELECT ma_sanpham FROM dsyeuthich WHERE ma_user = ?", [$_SESSION['user_site']['id']]);
    $wishedProducts = array_column($wished, 'ma_sanpham');
    $isWished = in_array($id, $wishedProducts);
}

$pageTitle = 'Chi Tiết Sản Phẩm';
$mainImage = !empty($images) ? BASE_URL . '/uploads/products/' . basename($images[0]['image_url']) : 'https://placehold.co/600x600/1a1a26/6c63ff?text=' . urlencode($product['ten_sanpham']);

// Tạo imageMap: mọi variant map sang danh sách URL ảnh của variant đó
// Key: ma_bienthe, Value: array of image URLs
$imagesByVariant = [];
$imagesShared    = []; // Ảnh chung (không gọn variant nào)
foreach ($images as $img) {
    $url = BASE_URL . '/uploads/products/' . basename($img['image_url']);
    if ($img['ma_bienthe']) {
        $imagesByVariant[$img['ma_bienthe']][] = $url;
    } else {
        $imagesShared[] = $url;
    }
}
// Map: mau_sac => [urls]
$imagesByColor = [];
foreach ($variants as $v) {
    $color = $v['mau_sac'];
    $imgs  = $imagesByVariant[$v['ma_bienthe']] ?? [];
    if ($imgs) {
        $imagesByColor[$color] = array_merge($imagesByColor[$color] ?? [], $imgs);
    }
}
// Nếu màu chưa có ảnh riêng, dùng ảnh chung
foreach ($variants as $v) {
    $color = $v['mau_sac'];
    if (empty($imagesByColor[$color])) $imagesByColor[$color] = $imagesShared;
}
// JSON cho JS
$imagesByColorJson = json_encode($imagesByColor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$allImagesJson     = json_encode(array_map(fn($img) => BASE_URL.'/uploads/products/'.basename($img['image_url']), $images), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<style>
/* Premium Aesthetics Redesign */
.product-detail-grid {
    display: grid;
    grid-template-columns: 4fr 6fr; /* Thu nhỏ cột ảnh, mở rộng cột thông tin */
    gap: 40px;
    align-items: start;
}
@media (max-width: 991px) {
    .product-detail-grid { grid-template-columns: 1fr; gap: 40px; }
}
.gallery-main {
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    padding: 0;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 450px; /* Cố định chiều cao vừa phải */
}
.gallery-main img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain; /* Ảnh luôn vừa vặn không bị cắt xén */
}
.gallery-thumbs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}
.gallery-thumbs .thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    opacity: 0.6;
    background: white;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}
.gallery-thumbs .thumb img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.gallery-thumbs .thumb:hover {
    opacity: 1;
    border-color: #cbd5e1;
}
.gallery-thumbs .thumb.active {
    border-color: #ef4444; /* Màu viền đỏ/cam như chuẩn TMĐT */
    opacity: 1;
}
.product-info {
    padding: 0; /* Bỏ padding thừa như ảnh mẫu */
    background: transparent;
    border-radius: 0;
    box-shadow: none;
    border: none;
}
.product-info h1 {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.3;
    color: #444;
    margin-bottom: 20px;
}
.product-price-section {
    display: flex;
    align-items: flex-end;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
}
.price-current {
    font-size: 28px;
    font-weight: 800;
    color: #d70018; /* Màu đỏ đâm đặc trưng */
}
.price-original {
    font-size: 16px;
    text-decoration: line-through;
    color: #707070;
    margin-bottom: 4px;
}
.price-discount {
    background: #d70018;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
}
.variant-section {
    margin-bottom: 15px;
}
.variant-label {
    font-size: 14px;
    color: #444;
    margin-bottom: 8px;
    display: inline-block;
    width: 80px;
}
.variant-label strong {
    color: #000;
    font-weight: 600;
    display: none; /* Ẩn chữ đậm bên cạnh đi để giống mẫu */
}
.variant-options-container {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.color-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    background: white;
    color: #444;
    font-size: 13px;
    transition: all 0.2s;
    cursor: pointer;
    box-shadow: none;
    height: auto;
    width: auto;
}
.color-btn-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 1px solid #ccc;
}
.color-btn:hover {
    border-color: #d70018;
    transform: none;
}
.color-btn.active {
    border-color: #d70018;
    color: #d70018;
    transform: none;
    box-shadow: none;
    position: relative;
}
.color-btn.active::before {
    content: "✓";
    position: absolute;
    top: -1px;
    right: -1px;
    background: #d70018;
    color: white;
    font-size: 10px;
    padding: 2px 4px;
    border-radius: 0 0 0 4px;
}
.spec-btn {
    padding: 8px 16px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    background: white;
    color: #444;
    font-size: 13px;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.spec-btn:hover {
    border-color: #d70018;
    color: #d70018;
    background: white;
    box-shadow: none;
}
.spec-btn.active {
    background: white;
    color: #d70018;
    border-color: #d70018;
    box-shadow: none;
}
.spec-btn.active::before {
    content: "✓";
    position: absolute;
    top: -1px;
    right: -1px;
    background: #d70018;
    color: white;
    font-size: 10px;
    padding: 2px 4px;
    border-radius: 0 0 0 4px;
}
#btnBuyNow {
    background: linear-gradient(180deg, #f52f32, #e11b1e);
    border: none;
    box-shadow: none;
    font-size: 16px;
    font-weight: 700;
    border-radius: 8px;
    padding: 14px;
    width: 100%;
    color: white;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.2s;
}
#btnBuyNow:hover {
    background: linear-gradient(180deg, #e11b1e, #c71619);
    transform: none;
    box-shadow: none;
}
#btnAddCart {
    background: white;
    border: 1px solid #288ad6;
    color: #288ad6;
    box-shadow: none;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    padding: 14px;
    transition: all 0.2s;
    cursor: pointer;
    text-align: center;
}
#btnAddCart:hover {
    background: #f0f8ff;
    transform: none;
}
#wishlistBtn {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    background: white;
    color: #666;
    transition: all 0.2s;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 16px;
}
#wishlistBtn:hover {
    background: #f8f8f8;
    transform: none;
    box-shadow: none;
}
.product-description-content {
    background: white;
    border-radius: 24px;
    padding: 45px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.03);
    line-height: 1.9;
    color: #334155;
    font-size: 16px;
    border: 1px solid #f1f5f9;
}
.product-description-content img {
    max-width: 100%;
    border-radius: 16px;
    margin: 30px 0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
}
.product-description-content h2, .product-description-content h3 {
    color: #0f172a;
    font-weight: 800;
    margin-top: 35px;
    margin-bottom: 20px;
}
.specs-table {
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,0.04);
    background: white;
    border-collapse: collapse;
    width: 100%;
}
.specs-table tr {
    transition: background 0.3s;
}
.specs-table tr:hover {
    background-color: #f1f5f9;
}
.specs-table tr:nth-child(even) {
    background-color: #f8fafc;
}
.specs-table td {
    padding: 20px 30px;
    border-bottom: 1px solid #f1f5f9;
    color: #475569;
    font-size: 15px;
}
.specs-table td:first-child {
    font-weight: 700;
    color: #1e293b;
    width: 35%;
    background-color: transparent;
}
.reviews-summary {
    background: white !important;
    border-radius: 24px !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.04) !important;
    border: 1px solid #f1f5f9 !important;
    padding: 40px !important;
}
.review-card {
    background: white !important;
    border-radius: 20px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.03) !important;
    border: 1px solid #f1f5f9 !important;
    padding: 30px !important;
    margin-bottom: 24px !important;
    transition: transform 0.3s, box-shadow 0.3s !important;
}
.review-card:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 15px 35px rgba(0,0,0,0.06) !important;
}
.tab-btn {
    font-size: 17px;
    font-weight: 700;
    padding: 16px 36px;
    border-radius: 100px;
    color: #64748b;
    transition: all 0.3s;
    background: transparent;
    border: none;
    cursor: pointer;
}
.tab-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.tab-btn.active {
    color: white;
    background: linear-gradient(135deg, #1e293b, #334155);
    box-shadow: 0 10px 25px rgba(30, 41, 59, 0.25);
}
.badge-hot-new {
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 800;
    font-size: 11px;
    letter-spacing: 0.5px;
    color: white;
    text-transform: uppercase;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.badge-hot { background: linear-gradient(135deg, #f12711, #f5af19); }
.badge-new { background: linear-gradient(135deg, #0cebeb, #20e3b2); color: #0f172a;}
</style>

<div class="container" style="padding-top:24px;padding-bottom:60px;">
    <!-- Breadcrumb -->
    <nav style="font-size:13px;color:var(--text-muted);margin-bottom:24px;">
        <a href="<?= BASE_URL ?>">Trang chủ</a> → 
        <?php foreach ($product_categories as $index => $cat): ?>
            <a href="<?= BASE_URL ?>/products.php?danhmuc=<?= $cat['slug'] ?>"><?= sanitize($cat['ten_danhmuc']) ?></a>
            <?php if ($index < count($product_categories) - 1) echo ', '; ?>
        <?php endforeach; ?>
        → <span><?= sanitize($product['ten_sanpham']) ?></span>
    </nav>

    <!-- ===== PRODUCT DETAIL MAIN ===== -->
    <div class="product-detail-grid">
        <!-- Gallery -->
        <div class="product-gallery">
            <div class="gallery-main" id="mainImgWrapper">
                <img src="<?= $mainImage ?>" alt="<?= sanitize($product['ten_sanpham']) ?>" id="mainImg">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs" id="thumbs">
                <?php foreach ($images as $i => $img):
                    $imgUrl = BASE_URL . '/uploads/products/' . basename($img['image_url']);
                    $varColor = $img['variant_color'] ?? '';
                ?>
                <div class="thumb <?= $i === 0 ? 'active' : '' ?>"
                     onclick="changeMainImg('<?= $imgUrl ?>', this)"
                     id="thumb-<?= $i ?>"
                     data-variant-color="<?= sanitize($varColor) ?>">
                    <img src="<?= $imgUrl ?>" alt="Ảnh <?= $i+1 ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <div class="product-brand-row" style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                <a href="<?= BASE_URL ?>/products.php?thuonghieu=<?= $product['thuonghieu_slug'] ?>" style="font-size:14px;color:var(--accent);font-weight:800;letter-spacing:0.5px;text-transform:uppercase;"><?= sanitize($product['ten_thuonghieu']) ?></a>
                <?php if ($product['is_noi_bat']): ?><span class="badge-hot-new badge-hot">🔥 HOT</span><?php endif; ?>
                <?php if ($product['is_hang_moi']): ?><span class="badge-hot-new badge-new">✨ MỚI</span><?php endif; ?>
            </div>
            
            <h1><?= sanitize($product['ten_sanpham']) ?></h1>
            
            <div class="product-meta">
                <?php if ($ratingStats['total'] > 0): ?>
                <span class="stars"><?= str_repeat('★', round($ratingStats['avg_rating'])) . str_repeat('☆', 5-round($ratingStats['avg_rating'])) ?></span>
                <span style="font-size:14px;color:var(--text-muted)"><?= round($ratingStats['avg_rating'],1) ?> (<?= $ratingStats['total'] ?> đánh giá)</span>
                <span style="color:var(--border)">|</span>
                <?php endif; ?>
                <span style="color:var(--text-muted);font-size:14px;"><?= $product['tong_da_ban'] ?> đã bán</span>
                <?php if ($product['ma_sanpham_code']): ?>
                <span style="color:var(--text-muted);font-size:13px;">SKU: <?= sanitize($product['ma_sanpham_code']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <div class="product-price-section" id="priceSection">
                <?php 
                $minPrice = $variants ? min(array_column($variants,'gia')) : 0;
                $gocPrices = $variants ? array_filter(array_column($variants,'gia_goc'), fn($v)=>$v>0) : [];
                $minGoc  = !empty($gocPrices) ? min($gocPrices) : null;
                ?>
                <?php if ($minGoc && $minGoc > $minPrice): ?>
                <span class="price-original"><?= formatPrice($minGoc) ?></span>
                <span class="price-discount">-<?= round((1-$minPrice/$minGoc)*100) ?>%</span>
                <?php endif; ?>
                <div class="price-current" id="currentPrice"><?= formatPrice($variants[0]['gia'] ?? 0) ?></div>
            </div>

            <?php 
            $defaultVariant = $variants[0] ?? null;
            $defaultColor = $defaultVariant['mau_sac'] ?? '';
            $defaultRam   = $defaultVariant['ram_gb'] ?? 0;
            $defaultRom   = $defaultVariant['rom_gb'] ?? 0;
            ?>

            <!-- Chọn RAM -->
            <?php $rams = array_unique(array_column($variants, 'ram_gb')); sort($rams); ?>
            <?php if ($rams): ?>
            <div class="variant-section" style="display:flex; align-items:flex-start;">
                <div class="variant-label">RAM: </div>
                <div class="variant-options-container">
                    <?php foreach ($rams as $ram): 
                        $isActive = (int)$ram === (int)$defaultRam;
                    ?>
                    <button class="spec-btn <?= $isActive ? 'active' : '' ?>" onclick="selectSpec('ram', <?= $ram ?>, this)" data-ram="<?= $ram ?>"><?= $ram ?>GB</button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Chọn ROM -->
            <?php $roms = array_unique(array_column($variants, 'rom_gb')); sort($roms); ?>
            <?php if ($roms): ?>
            <div class="variant-section" style="display:flex; align-items:flex-start;">
                <div class="variant-label">Dung lượng: </div>
                <div class="variant-options-container">
                    <?php foreach ($roms as $rom): 
                        $isActive = (int)$rom === (int)$defaultRom;
                    ?>
                    <button class="spec-btn <?= $isActive ? 'active' : '' ?>" onclick="selectSpec('rom', <?= $rom ?>, this)" data-rom="<?= $rom ?>"><?= $rom >= 1024 ? ($rom/1024).'TB' : $rom.'GB' ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Chọn màu -->
            <?php $colors = array_unique(array_column($variants, 'mau_sac')); ?>
            <?php if ($colors): ?>
            <div class="variant-section" style="display:flex; align-items:flex-start;">
                <div class="variant-label">Màu sắc: <strong id="selectedColor"><?= sanitize($colors[0]) ?></strong></div>
                <div class="variant-options-container">
                    <?php 
                    $colorMap = [];
                    foreach ($variants as $v) $colorMap[$v['mau_sac']] = $v['ma_hex_mau'];
                    foreach ($colors as $i => $color): 
                        $isActive = ($color === $defaultColor);
                    ?>
                    <button class="color-btn <?= $isActive ? 'active' : '' ?>"
                            title="<?= sanitize($color) ?>"
                            onclick="selectColor('<?= sanitize($color) ?>', this)"
                            data-color="<?= sanitize($color) ?>">
                            <?= sanitize($color) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Số lượng -->
            <div style="display:flex; align-items:center; gap:15px; margin-bottom: 20px;">
                <div style="font-size:14px;color:#444;">Số lượng:</div>
                <div class="qty-selector" style="display:flex; border:1px solid #e0e0e0; border-radius:4px; overflow:hidden; width: 100px;">
                    <button class="qty-btn" onclick="changeQty(-1)" style="flex:1; border:none; background:#f8f9fa; cursor:pointer;">−</button>
                    <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="99" readonly style="flex:1.5; border:none; border-left:1px solid #e0e0e0; border-right:1px solid #e0e0e0; text-align:center; outline:none; font-size:14px;">
                    <button class="qty-btn" onclick="changeQty(1)" style="flex:1; border:none; background:#f8f9fa; cursor:pointer;">+</button>
                </div>
                <div id="stockInfo" style="font-size:13px; color:#28a745;">Còn hàng</div>
            </div>

            <!-- Actions -->
            <div style="margin-bottom: 10px;">
                <button id="btnBuyNow" onclick="buyNow()">
                    <div>MUA NGAY</div>
                    <div style="font-size:12px; font-weight:normal; text-transform:none; margin-top:2px;">Giao tận nơi hoặc nhận tại cửa hàng</div>
                </button>
            </div>
            
            <div style="display:flex; gap:10px; margin-bottom: 20px;">
                <button id="btnAddCart" onclick="addToCartDetail()" style="flex:1;">
                    🛒 Thêm Vào Giỏ
                </button>
                <button id="wishlistBtn" onclick="toggleWishlist(this, <?= $id ?>)">
                    <?= $isWished ? '❤️' : '♡' ?>
                </button>
            </div>

            <!-- Tags -->
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted);">
                <span>🚚 Giao hàng nhanh</span>
                <span>•</span>
                <span>🏆 Chính hãng 100%</span>
                <span>•</span>
                <span>🔄 Đổi trả 30 ngày</span>
                <span>•</span>
                <span>💳 Thanh toán an toàn</span>
            </div>

            <!-- Quick specs -->
            <?php if ($product['chip'] || $product['man_hinh_size'] || $product['pin_dung_luong']): ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <?php if ($product['chip']): ?>
                <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:12px;font-size:13px;">
                    <div style="color:var(--text-muted);margin-bottom:2px;">⚙️ Chip</div>
                    <strong><?= sanitize($product['chip']) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($product['man_hinh_size']): ?>
                <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:12px;font-size:13px;">
                    <div style="color:var(--text-muted);margin-bottom:2px;">📺 Màn hình</div>
                    <strong><?= $product['man_hinh_size'] ?>" <?= sanitize($product['man_hinh_loai'] ?? '') ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($product['pin_dung_luong']): ?>
                <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:12px;font-size:13px;">
                    <div style="color:var(--text-muted);margin-bottom:2px;">🔋 Pin</div>
                    <strong><?= $product['pin_dung_luong'] ?> mAh <?= $product['sac_nhanh'] ? '/ Sạc '.$product['sac_nhanh'].'W' : '' ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($product['camera_sau']): ?>
                <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:12px;font-size:13px;">
                    <div style="color:var(--text-muted);margin-bottom:2px;">📷 Camera</div>
                    <strong><?= sanitize($product['camera_sau']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== SPECS TABS ===== -->
    <div class="specs-tabs" style="margin-top:60px;">
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('mo-ta', this)">Mô tả</button>
            <button class="tab-btn" onclick="showTab('thong-so', this)">Thông số kỹ thuật</button>
            <button class="tab-btn" onclick="showTab('danh-gia', this)">
                Đánh giá (<?= $ratingStats['total'] ?>)
            </button>
        </div>

        <div id="tab-mo-ta" class="tab-content active">
            <div class="product-description-content">
                <?= $product['mo_ta_day_du'] ? $product['mo_ta_day_du'] : nl2br(sanitize($product['mo_ta_ngan'] ?: 'Chưa có mô tả chi tiết.')) ?>
            </div>
        </div>

        <div id="tab-thong-so" class="tab-content">
            <div class="specs-table-wrapper" style="border-radius:24px;overflow:hidden;border:1px solid #f1f5f9;">
                <table class="specs-table">
                    <tbody>
                        <?php
                        $specs = [
                            'Thương hiệu'      => $product['ten_thuonghieu'],
                            'Hệ điều hành'     => $product['he_dieu_hanh'] . ($product['phien_ban_os'] ? ' ' . $product['phien_ban_os'] : ''),
                            'Chip xử lý'       => $product['chip'],
                            'Màn hình'         => $product['man_hinh_size'] ? $product['man_hinh_size'] . '" ' . $product['man_hinh_loai'] : null,
                            'Độ phân giải'     => $product['man_hinh_dophangiai'],
                            'Tần số quét'      => $product['man_hinh_tanso'] ? $product['man_hinh_tanso'] . ' Hz' : null,
                            'Dung lượng pin'   => $product['pin_dung_luong'] ? $product['pin_dung_luong'] . ' mAh' : null,
                            'Sạc nhanh'        => $product['sac_nhanh'] ? $product['sac_nhanh'] . ' W' : null,
                            'Camera sau'       => $product['camera_sau'],
                            'Camera trước'     => $product['camera_truoc'],
                            'Kết nối'          => $product['ket_noi'],
                            'Kháng nước'       => $product['khang_nuoc'],
                            'Kích thước'       => $product['kich_thuoc'],
                            'Trọng lượng'      => $product['trong_luong'] ? $product['trong_luong'] . ' g' : null,
                        ];
                        foreach ($specs as $label => $value):
                            if (!$value) continue;
                        ?>
                        <tr>
                            <td><?= $label ?></td>
                            <td><?= sanitize($value) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($variants as $v): ?>
                        <tr>
                            <td>Biến thể <?= $v['ram_gb'] ?>GB+<?= $v['rom_gb'] ?>GB <?= sanitize($v['mau_sac']) ?></td>
                            <td><?= formatPrice($v['gia']) ?> (Kho: <?= $v['ton_kho'] ?>)</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-danh-gia" class="tab-content">
            <!-- Rating Summary -->
            <?php if ($ratingStats['total'] > 0): ?>
            <div class="reviews-summary" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;margin-bottom:24px;">
                <div class="rating-big">
                    <div class="score"><?= round($ratingStats['avg_rating'],1) ?></div>
                    <div class="stars"><?= str_repeat('★', round($ratingStats['avg_rating'])) . str_repeat('☆', 5-round($ratingStats['avg_rating'])) ?></div>
                    <div class="count"><?= $ratingStats['total'] ?> đánh giá</div>
                </div>
                <div class="rating-bars">
                    <?php foreach ([5,4,3,2,1] as $star): 
                        $count = (int)$ratingStats['s'.$star];
                        $pct = $ratingStats['total'] > 0 ? ($count/$ratingStats['total'])*100 : 0;
                    ?>
                    <div class="rating-bar-row">
                        <span class="label"><?= $star ?>★</span>
                        <div class="rating-bar-bg"><div class="rating-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        <span class="pct"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Review form -->
            <?php if (isCustomer()): ?>
            <div style="background:white;border:1px solid #f1f5f9;border-radius:24px;padding:35px;margin-bottom:30px;box-shadow:0 15px 40px rgba(0,0,0,0.03);">
                <h4 style="margin-bottom:16px;">✍️ Viết Đánh Giá</h4>
                <form onsubmit="submitReview(event, <?= $id ?>)">
                    <div style="margin-bottom:16px;">
                        <div style="font-size:14px;color:var(--text-secondary);margin-bottom:8px;">Đánh giá của bạn:</div>
                        <div style="display:flex;gap:6px;" id="starRating">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" class="star-btn" data-val="<?= $s ?>" onclick="setRating(<?= $s ?>)"
                                    style="background:none;border:none;font-size:28px;cursor:pointer;color:var(--border);transition:color 0.1s;">☆</button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="diem" id="ratingValue" value="0">
                    </div>
                    <input type="text" name="tieu_de" class="form-control" placeholder="Tiêu đề đánh giá..." style="margin-bottom:12px;">
                    <textarea name="noi_dung" class="form-control" rows="4" placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..." style="resize:vertical;"></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:12px;">Gửi Đánh Giá</button>
                </form>
            </div>
            <?php elseif (!isLoggedIn()): ?>
            <div style="text-align:center;padding:24px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:24px;">
                <p style="color:var(--text-secondary);margin-bottom:12px;">Đăng nhập để viết đánh giá</p>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Đăng nhập ngay</a>
            </div>
            <?php endif; ?>

            <!-- Reviews list -->
            <?php if (empty($reviews)): ?>
            <div class="empty-state" style="padding:40px;">
                <div class="empty-icon">💬</div>
                <div class="empty-title">Chưa có đánh giá nào</div>
                <p>Hãy là người đầu tiên đánh giá sản phẩm này!</p>
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $rv): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar"><?= mb_strtoupper(mb_substr($rv['hovaten'] ?: $rv['ten_user'], 0, 1)) ?></div>
                        <div>
                            <div class="reviewer-name"><?= sanitize($rv['hovaten'] ?: $rv['ten_user']) ?></div>
                            <div class="reviewer-date"><?= timeAgo($rv['ngay_lap']) ?></div>
                        </div>
                    </div>
                    <span class="stars"><?= str_repeat('★', $rv['diem']) . str_repeat('☆', 5-$rv['diem']) ?></span>
                </div>
                <?php if ($rv['tieu_de']): ?><div class="review-title"><?= sanitize($rv['tieu_de']) ?></div><?php endif; ?>
                <div class="review-content"><?= sanitize($rv['noi_dung']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- RELATED PRODUCTS -->
    <?php if ($related): ?>
    <div style="margin-top:60px;">
        <h2 style="font-size:24px;font-weight:800;margin-bottom:24px;">Sản Phẩm Liên Quan</h2>
        <div class="products-grid">
            <?php foreach ($related as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Biến thể JSON cho JS -->
<script>
const VARIANTS = <?= json_encode($variants, JSON_UNESCAPED_UNICODE) ?>;
// BASE_URL được dùng từ main.js (không khai báo lại để tránh SyntaxError)
const IMAGES_BY_COLOR = <?= $imagesByColorJson ?>; // Map: mau_sac => [url,url,...]
let selectedColor = '<?= sanitize($defaultColor) ?>';
let selectedRam   = <?= $defaultRam ?>;
let selectedRom   = <?= $defaultRom ?>;

function getVariant() {
    const variant = VARIANTS.find(v => {
        // So sánh màu sắc (trim để tránh khoảng trắng thừa)
        const matchColor = v.mau_sac.toString().trim() == selectedColor.toString().trim();
        // So sánh RAM/ROM (ép kiểu số để chắc chắn)
        const matchRam   = parseInt(v.ram_gb) == parseInt(selectedRam);
        const matchRom   = parseInt(v.rom_gb) == parseInt(selectedRom);
        return matchColor && matchRam && matchRom;
    });
    return variant;
}

function updatePrice() {
    const v = getVariant();
    const priceEl = document.getElementById('currentPrice');
    const stockEl = document.getElementById('stockInfo');
    const btn     = document.getElementById('btnAddCart');
    if (v) {
        priceEl.textContent = new Intl.NumberFormat('vi-VN').format(v.gia) + ' VND';
        const inStock = parseInt(v.ton_kho) > 0;
        stockEl.textContent = inStock ? 'Còn ' + v.ton_kho + ' sản phẩm' : 'Hết hàng';
        stockEl.style.color = inStock ? 'var(--success)' : 'var(--danger)';
        btn.disabled = !inStock;
        document.getElementById('qtyInput').max = v.ton_kho;
    }
}

function selectColor(color, el) {
    selectedColor = color;
    document.getElementById('selectedColor').textContent = color;
    document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    // Tìm variant hợp lệ đầu tiên của màu này và auto-set RAM + ROM
    const firstV = VARIANTS.find(v => v.mau_sac.toString().trim() === color.toString().trim());
    if (firstV) {
        selectedRam = parseInt(firstV.ram_gb);
        const ramEl = document.getElementById('selectedRam');
        if (ramEl) ramEl.textContent = selectedRam + 'GB';
        document.querySelectorAll('[data-ram]').forEach(b =>
            b.classList.toggle('active', parseInt(b.dataset.ram) === selectedRam));

        selectedRom = parseInt(firstV.rom_gb);
        const romEl = document.getElementById('selectedRom');
        if (romEl) romEl.textContent = selectedRom >= 1024 ? (selectedRom/1024)+'TB' : selectedRom+'GB';
        document.querySelectorAll('[data-rom]').forEach(b =>
            b.classList.toggle('active', parseInt(b.dataset.rom) === selectedRom));
    }

    updatePrice();
    updateGalleryForColor(color);
}

function updateGalleryForColor(color) {
    const imgs = IMAGES_BY_COLOR[color];
    if (!imgs || imgs.length === 0) return;
    // Đổi ảnh chính
    document.getElementById('mainImg').src = imgs[0];
    // Cập nhật thumbs: ẩn thumbs không thuộc màu này, hiện thumbs thuộc màu này
    const thumbsEl = document.getElementById('thumbs');
    if (!thumbsEl) return;
    const thumbDivs = thumbsEl.querySelectorAll('.thumb');
    thumbDivs.forEach(t => {
        const tColor = t.dataset.variantColor;
        // Hiện nếu: (1) ảnh chung (tColor=''), hoặc (2) ảnh của màu này
        const show = tColor === '' || tColor === color;
        t.style.display = show ? '' : 'none';
        t.classList.remove('active');
    });
    // Active thumbnail đầu tiên hiện thị
    const firstVisible = thumbsEl.querySelector('.thumb:not([style*="none"])');
    if (firstVisible) firstVisible.classList.add('active');
}

function selectSpec(type, val, el) {
    if (type === 'ram') {
        selectedRam = val;
        const ramEl = document.getElementById('selectedRam');
        if (ramEl) ramEl.textContent = val + 'GB';
        document.querySelectorAll('[data-ram]').forEach(b => b.classList.toggle('active', parseInt(b.dataset.ram) === val));

        // Kiểm tra nếu ROM hiện tại hợp lệ với Màu + RAM mới
        const stillValid = VARIANTS.find(v =>
            v.mau_sac.toString().trim() === selectedColor.toString().trim() &&
            parseInt(v.ram_gb) === parseInt(val) &&
            parseInt(v.rom_gb) === parseInt(selectedRom)
        );
        if (!stillValid) {
            // Reset ROM về giá trị hợp lệ đầu tiên của combo Màu + RAM này
            const firstValid = VARIANTS.find(v =>
                v.mau_sac.toString().trim() === selectedColor.toString().trim() &&
                parseInt(v.ram_gb) === parseInt(val)
            );
            if (firstValid) {
                selectedRom = parseInt(firstValid.rom_gb);
                const romEl = document.getElementById('selectedRom');
                if (romEl) romEl.textContent = selectedRom >= 1024 ? (selectedRom/1024)+'TB' : selectedRom+'GB';
                document.querySelectorAll('[data-rom]').forEach(b =>
                    b.classList.toggle('active', parseInt(b.dataset.rom) === selectedRom));
            }
        }
    } else {
        selectedRom = val;
        const romEl = document.getElementById('selectedRom');
        if (romEl) romEl.textContent = val >= 1024 ? (val/1024)+'TB' : val+'GB';
        document.querySelectorAll('[data-rom]').forEach(b => b.classList.toggle('active', parseInt(b.dataset.rom) === val));

        // KIỂM TRA ĐỐI XỨNG: Nếu RAM hiện tại không hợp lệ với Màu + ROM mới
        const stillValid = VARIANTS.find(v =>
            v.mau_sac.toString().trim() === selectedColor.toString().trim() &&
            parseInt(v.rom_gb) === parseInt(val) &&
            parseInt(v.ram_gb) === parseInt(selectedRam)
        );
        if (!stillValid) {
            // Reset RAM về giá trị hợp lệ đầu tiên của combo Màu + ROM này
            const firstValid = VARIANTS.find(v =>
                v.mau_sac.toString().trim() === selectedColor.toString().trim() &&
                parseInt(v.rom_gb) === parseInt(val)
            );
            if (firstValid) {
                selectedRam = parseInt(firstValid.ram_gb);
                const ramEl = document.getElementById('selectedRam');
                if (ramEl) ramEl.textContent = selectedRam + 'GB';
                document.querySelectorAll('[data-ram]').forEach(b =>
                    b.classList.toggle('active', parseInt(b.dataset.ram) === selectedRam));
            }
        }
    }
    updatePrice();
}

function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    const val = parseInt(input.value) + delta;
    if (val >= 1 && val <= parseInt(input.max || 99)) input.value = val;
}

function changeMainImg(src, thumb) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

function showTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

// Star rating 
let currentRating = 0;
function setRating(val) {
    currentRating = val;
    document.getElementById('ratingValue').value = val;
    document.querySelectorAll('.star-btn').forEach((btn, i) => {
        btn.textContent = i < val ? '★' : '☆';
        btn.style.color = i < val ? 'var(--warning)' : 'var(--border)';
    });
}
document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('mouseenter', () => {
        const v = parseInt(btn.dataset.val);
        document.querySelectorAll('.star-btn').forEach((b, i) => {
            b.textContent = i < v ? '★' : '☆';
            b.style.color = i < v ? 'var(--warning)' : 'var(--border)';
        });
    });
    btn.addEventListener('mouseleave', () => setRating(currentRating));
});

async function submitReview(e, productId) {
    e.preventDefault();
    const form = e.target;
    const rating = document.getElementById('ratingValue').value;
    if (!rating || rating == 0) { alert('Vui lòng chọn số sao!'); return; }
    const data = new FormData(form);
    data.append('ma_sanpham', productId);
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Đang gửi...';
    try {
        const res = await fetch(BASE_URL + '/api/review.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            alert('✅ Đánh giá đã được gửi, chờ admin duyệt!');
            form.reset(); setRating(0);
        } else {
            alert('❌ ' + (json.message || 'Gửi thất bại'));
        }
    } catch(err) { alert('Lỗi kết nối!'); }
    btn.disabled = false; btn.textContent = 'Gửi Đánh Giá';
}

async function addToCartDetail() {
    const v = getVariant();
    if (!v) { alert('Vui lòng chọn đúng biến thể!'); return; }
    const qty = parseInt(document.getElementById('qtyInput').value);
    await addToCart(v.ma_bienthe, qty);
}

async function buyNow() {
    <?php if (!isLoggedIn()): ?>
    // Chưa đăng nhập → redirect login
    window.location.href = BASE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
    <?php endif; ?>

    const v = getVariant();
    if (!v) { alert('Vui lòng chọn biến thể (màu sắc, RAM, ROM)!'); return; }
    if (parseInt(v.ton_kho) <= 0) { alert('Sản phẩm này đã hết hàng!'); return; }

    const btn = document.getElementById('btnBuyNow');
    btn.disabled = true;
    btn.textContent = '⏳ Đang xử lý...';

    const qty = parseInt(document.getElementById('qtyInput').value) || 1;
    try {
        // Thêm vào giỏ hàng trước
        const res = await fetch(BASE_URL + '/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ma_bienthe: v.ma_bienthe, so_luong: qty })
        });
        const json = await res.json();
        if (json.success || json.message?.includes('đã có')) {
            // Redirect thẳng sang checkout
            window.location.href = BASE_URL + '/checkout.php';
        } else {
            alert('❌ ' + (json.message || 'Không thể thêm vào giỏ'));
            btn.disabled = false;
            btn.textContent = '⚡ Mua Ngay';
        }
    } catch(err) {
        alert('Lỗi kết nối, vui lòng thử lại!');
        btn.disabled = false;
        btn.textContent = '⚡ Mua Ngay';
    }
}

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
