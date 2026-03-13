<?php
$pageTitle = 'Phonestore - Điện Thoại Chính Hãng, Giá Tốt';
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
<style>
/* ===== HERO BANNER REDESIGN ===== */
.hero-banner {
    width: 100%;
    background: #111;
    overflow: hidden;
    padding: 12px 0;
}
.hero-banner .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}
.hero-inner {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 12px;
    height: 380px;
    border-radius: 12px;
    overflow: hidden;
}

/* ---- MAIN SLIDE ---- */
.hero-main-slider {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
}
.hero-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transform: translateX(30px);
    transition: opacity 0.7s ease, transform 0.7s ease;
    pointer-events: none;
}
.hero-slide.active {
    opacity: 1;
    transform: translateX(0);
    pointer-events: auto;
}
.hero-slide.exit {
    opacity: 0;
    transform: translateX(-30px);
}
.hero-slide-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(0,0,0,0.72) 38%, rgba(0,0,0,0.10) 100%);
}
.hero-slide-content {
    position: absolute;
    left: 48px;
    bottom: 48px;
    color: #fff;
}
.hero-slide-content .badge {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 10px;
}
.hero-slide-content h2 {
    font-size: 36px;
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 12px;
    text-shadow: 0 2px 12px rgba(0,0,0,0.5);
}
.hero-slide-content p {
    font-size: 16px;
    color: rgba(255,255,255,0.85);
    margin: 0 0 24px;
    max-width: 360px;
    line-height: 1.5;
}
.btn-hero {
    display: inline-block;
    background: #e85d04;
    color: #fff;
    font-weight: 700;
    font-size: 16px;
    padding: 12px 32px;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.2s, transform 0.2s;
    box-shadow: 0 4px 15px rgba(232,93,4,0.45);
}
.btn-hero:hover {
    background: #f77f3a;
    transform: translateY(-2px);
    text-decoration: none;
    color: #fff;
}

/* Dots */
.hero-dots {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 10;
}
.hero-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.45);
    cursor: pointer;
    transition: background 0.3s, width 0.3s;
    border: none;
    padding: 0;
}
.hero-dot.active {
    background: #fff;
    width: 24px;
    border-radius: 4px;
}

/* ---- SIDE THUMBNAILS ---- */
.hero-side {
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-radius: 12px;
    overflow: hidden;
}
.hero-thumb {
    flex: 1;
    position: relative;
    overflow: hidden;
    border-radius: 10px;
    cursor: pointer;
    background-size: cover;
    background-position: center;
    transition: transform 0.3s ease;
}
.hero-thumb:hover {
    transform: scale(1.02);
}
.hero-thumb-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(0deg, rgba(0,0,0,0.55) 0%, transparent 60%);
}
.hero-thumb-label {
    position: absolute;
    bottom: 10px;
    left: 12px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.3;
    text-shadow: 0 1px 6px rgba(0,0,0,0.6);
}
.hero-thumb-label small {
    display: block;
    font-weight: 400;
    font-size: 10px;
    opacity: 0.85;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-inner {
        grid-template-columns: 1fr;
        height: auto;
    }
    .hero-main-slider { height: 260px; position: relative; border-radius: 12px; }
    .hero-side { flex-direction: row; height: 120px; }
}
</style>

<section class="hero-banner">
    <div class="container">
        <div class="hero-inner">
            <!-- Main slider -->
            <div class="hero-main-slider" id="heroMainSlider">

                <!-- Slide 1: iPhone 16 Pro Max -->
                <div class="hero-slide active"
                     style="background-image: url('https://images.unsplash.com/photo-1632661674596-df8be070a5c5?q=80&w=1200');"
                     data-link="<?= BASE_URL ?>/products.php?thuonghieu=apple">
                    <div class="hero-slide-overlay"></div>
                    <div class="hero-slide-content">
                        <div class="badge">Mới nhất 2024</div>
                        <h2>iPhone 16 Pro Max</h2>
                        <p>Nâng tầm trải nghiệm với chip A18 Pro mạnh mẽ nhất.</p>
                        <a href="<?= BASE_URL ?>/products.php?thuonghieu=apple" class="btn-hero">Mua ngay</a>
                    </div>
                </div>

                <!-- Slide 2: Samsung S24 Ultra -->
                <div class="hero-slide"
                     style="background-image: url('https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?q=80&w=1200');"
                     data-link="<?= BASE_URL ?>/products.php?thuonghieu=samsung">
                    <div class="hero-slide-overlay"></div>
                    <div class="hero-slide-content">
                        <div class="badge">Galaxy Series</div>
                        <h2>Samsung Galaxy S24 Ultra</h2>
                        <p>Bút S Pen thông minh, camera 200MP vượt trội.</p>
                        <a href="<?= BASE_URL ?>/products.php?thuonghieu=samsung" class="btn-hero">Khám phá</a>
                    </div>
                </div>

                <!-- Slide 3: Xiaomi -->
                <div class="hero-slide"
                     style="background-image: url('https://images.unsplash.com/photo-1598327105666-5b89351aff97?q=80&w=1200');"
                     data-link="<?= BASE_URL ?>/products.php?thuonghieu=xiaomi">
                    <div class="hero-slide-overlay"></div>
                    <div class="hero-slide-content">
                        <div class="badge">Giá tốt nhất</div>
                        <h2>Xiaomi 14 Series</h2>
                        <p>Hiệu năng Snapdragon 8 Gen 3, sạc siêu nhanh 90W.</p>
                        <a href="<?= BASE_URL ?>/products.php?thuonghieu=xiaomi" class="btn-hero">Xem ngay</a>
                    </div>
                </div>

                <!-- Dots -->
                <div class="hero-dots" id="heroDots">
                    <button class="hero-dot active" data-index="0"></button>
                    <button class="hero-dot" data-index="1"></button>
                    <button class="hero-dot" data-index="2"></button>
                </div>
            </div>

            <!-- Side thumbnails -->
            <div class="hero-side">
                <div class="hero-thumb"
                     style="background-image: url('https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?q=80&w=600');"
                     onclick="location.href='<?= BASE_URL ?>/products.php?thuonghieu=samsung'">
                    <div class="hero-thumb-overlay"></div>
                    <div class="hero-thumb-label">
                        Samsung S24 Ultra
                        <small>Titan violet mới</small>
                    </div>
                </div>
                <div class="hero-thumb"
                     style="background-image: url('https://images.unsplash.com/photo-1598327105666-5b89351aff97?q=80&w=600');"
                     onclick="location.href='<?= BASE_URL ?>/products.php?thuonghieu=xiaomi'">
                    <div class="hero-thumb-overlay"></div>
                    <div class="hero-thumb-label">
                        Xiaomi 14 Pro
                        <small>Leica optics</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const slides = document.querySelectorAll('#heroMainSlider .hero-slide');
    const dots   = document.querySelectorAll('#heroDots .hero-dot');
    let current  = 0;
    let timer;

    function goTo(idx) {
        slides[current].classList.remove('active');
        slides[current].classList.add('exit');
        dots[current].classList.remove('active');

        const prev = current;
        current = (idx + slides.length) % slides.length;

        slides[current].classList.add('active');
        dots[current].classList.add('active');

        // Remove exit class after animation
        setTimeout(() => { slides[prev].classList.remove('exit'); }, 750);
    }

    function next() { goTo(current + 1); }

    function startAuto() {
        clearInterval(timer);
        timer = setInterval(next, 5000);
    }

    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            goTo(parseInt(dot.dataset.index));
            startAuto();
        });
    });

    startAuto();
})();
</script>

<!-- BRAND LOGOS (Bubble Style) -->
        <div class="brand-filter-container">
            <?php 
            $brandLogoConfig = [
                'Apple'    => ['icon' => 'apple',    'color' => 'dc2626'],
                'Samsung'  => ['icon' => 'samsung',  'color' => '1428A0'],
                'Xiaomi'   => ['icon' => 'xiaomi',   'color' => 'FF6900'],
                'OPPO'     => ['icon' => 'oppo',     'color' => '007A5E'],
                'Vivo'     => ['icon' => 'vivo',     'color' => '415FFF'],
                'Realme'   => ['icon' => 'realme',   'color' => 'FFC915'],
                'OnePlus'  => ['icon' => 'oneplus',  'color' => 'F5010C'],
                'Nokia'    => ['icon' => 'nokia',    'color' => '124191'],
                'Motorola' => ['icon' => 'motorola', 'color' => '5F6368'],
                'Google'   => ['icon' => 'google',   'color' => '4285F4'],
            ];
            
            foreach ($brands as $brand): 
                $config = $brandLogoConfig[$brand['ten_thuonghieu']] ?? null;
                $logoSrc = $config ? "https://cdn.simpleicons.org/{$config['icon']}/{$config['color']}" : BASE_URL . "/assets/images/brands/default.png";
                
                // Trường hợp đặc biệt cho Realme vì không có trên Simple Icons
                if ($brand['ten_thuonghieu'] === 'Realme') {
                    $logoSrc = BASE_URL . "/assets/images/brands/realme.png";
                }

                $isActive = (isset($_GET['thuonghieu']) && $_GET['thuonghieu'] == $brand['slug']);
            ?>
            <a href="<?= BASE_URL ?>/products.php?thuonghieu=<?= $brand['slug'] ?>" class="brand-btn <?= $isActive ? 'active' : '' ?>">
                <img src="<?= $logoSrc ?>" alt="<?= sanitize($brand['ten_thuonghieu']) ?> Logo" class="brand-logo" onerror="this.src='https://placehold.co/20x20?text=<?= substr($brand['ten_thuonghieu'], 0, 1) ?>'">
                <span><?= strtoupper(sanitize($brand['ten_thuonghieu'])) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

<!-- FLASH SALE SECTION -->
<section class="flash-sale-v2">
    <div class="container">
        <div class="fs-header">
            <div class="fs-title">
                <span class="fs-icon">⚡</span>
                <h2>FLASH SALE</h2>
                <div class="fs-timer" id="flashSaleTimer">00 : 00 : 00</div>
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


<script>
function updateFlashSaleTimer() {
    const timerElement = document.getElementById('flashSaleTimer');
    if (!timerElement) return;

    // Thiết lập thời điểm kết thúc là 23:59:59 của ngày hôm nay
    const now = new Date();
    const target = new Date();
    target.setHours(23, 59, 59, 0);

    // Nếu hiện tại đã qua thời điểm mục tiêu (hiếm khi xảy ra trong ngày), 
    // có thể set sang ngày hôm sau nếu muốn, nhưng ở đây mặc định theo ngày.
    let diff = target - now;
    
    if (diff <= 0) {
        timerElement.innerHTML = "00 : 00 : 00";
        return;
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    const format = (num) => num.toString().padStart(2, '0');

    timerElement.innerHTML = `${format(hours)} : ${format(minutes)} : ${format(seconds)}`;
}

// Khởi chạy ngay lập tức và cập nhật mỗi giây
updateFlashSaleTimer();
setInterval(updateFlashSaleTimer, 1000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
