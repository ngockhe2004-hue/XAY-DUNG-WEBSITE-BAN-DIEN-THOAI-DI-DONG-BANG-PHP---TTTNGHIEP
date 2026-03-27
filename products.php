<?php
$pageTitle = 'Danh Sách Điện Thoại';
$pageDesc  = 'Tìm kiếm và mua điện thoại chính hãng Apple, Samsung, Xiaomi, OPPO...';
require_once __DIR__ . '/includes/header.php';

// ===== Build Query =====
$where = ['sp.is_active = 1'];
$params = [];

// Search
if (!empty($_GET['q'])) {
    $q = trim($_GET['q']);
    $cleanQ = preg_replace('/[+\-><()~*"]/', ' ', $q);
    $words = array_filter(explode(' ', $cleanQ), function($w) { return strlen(trim($w)) > 0; });
    
    $ftsTerms = [];
    $likeTerms = [];
    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) >= 3) {
            $ftsTerms[] = '+' . $word . '*';
        } else {
            $likeTerms[] = $word;
        }
    }

    if (!empty($ftsTerms)) {
        $where[] = 'MATCH(sp.ten_sanpham, sp.mo_ta_ngan) AGAINST(? IN BOOLEAN MODE)';
        $params[] = implode(' ', $ftsTerms);
    }

    if (!empty($likeTerms)) {
        foreach ($likeTerms as $lt) {
            $where[] = '(sp.ten_sanpham LIKE ? OR sp.mo_ta_ngan LIKE ?)';
            $params[] = "%$lt%";
            $params[] = "%$lt%";
        }
    }
}
// Danh mục
if (!empty($_GET['danhmuc'])) {
    $where[] = 'EXISTS(SELECT 1 FROM sanpham_danhmuc spdm JOIN danhmuc dmx ON spdm.ma_danhmuc = dmx.ma_danhmuc WHERE spdm.ma_sanpham = sp.ma_sanpham AND dmx.slug = ?)';
    $params[] = sanitize($_GET['danhmuc']);
}
// Thương hiệu
if (!empty($_GET['thuonghieu'])) {
    $where[] = 'th.slug = ?';
    $params[] = sanitize($_GET['thuonghieu']);
}
// Giá
if (!empty($_GET['gia_tu'])) {
    $where[] = 'b_min.gia_thap >= ?';
    $params[] = (float)$_GET['gia_tu'];
}
if (!empty($_GET['gia_den'])) {
    $where[] = 'b_min.gia_thap <= ?';
    $params[] = (float)$_GET['gia_den'];
}
// Featured/Sale/New
if (!empty($_GET['featured'])) { $where[] = 'sp.is_noi_bat = 1'; }
if (!empty($_GET['new'])) { $where[] = 'sp.is_hang_moi = 1'; }
if (!empty($_GET['sale'])) { 
    $where[] = 'EXISTS(SELECT 1 FROM bienthe_sanpham bx WHERE bx.ma_sanpham = sp.ma_sanpham AND bx.gia_goc IS NOT NULL AND bx.gia < bx.gia_goc)';
}
// Đánh giá
if (!empty($_GET['danhgia'])) {
    $where[] = 'sp.diem_danh_gia >= ?';
    $params[] = (float)$_GET['danhgia'];
}

// Sort
$sortMap = [
    'moi_nhat'  => 'sp.ngay_lap DESC',
    'gia_tang'  => 'b_min.gia_thap ASC',
    'gia_giam'  => 'b_min.gia_thap DESC',
    'ban_chay'  => 'sp.tong_da_ban DESC',
    'danhgia'   => 'sp.diem_danh_gia DESC',
];
$sort = $sortMap[$_GET['sort'] ?? ''] ?? 'sp.tong_da_ban DESC';

$whereSQL = implode(' AND ', $where);

// Count total
$countSQL = "
    SELECT COUNT(DISTINCT sp.ma_sanpham) as total
    FROM sanpham sp
    JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
    LEFT JOIN (SELECT ma_sanpham, MIN(gia) as gia_thap FROM bienthe_sanpham WHERE is_active=1 GROUP BY ma_sanpham) b_min ON sp.ma_sanpham = b_min.ma_sanpham
    WHERE $whereSQL
";
$total = (int)db()->fetchColumn($countSQL, $params);
$page = max(1, (int)($_GET['page'] ?? 1));
$paging = paginate($total, $page, PRODUCTS_PER_PAGE);

// Main query
$productsSQL = "
    SELECT sp.ma_sanpham, sp.ten_sanpham, sp.slug, sp.diem_danh_gia, sp.tong_da_ban,
           sp.is_noi_bat, sp.is_hang_moi, sp.ngay_lap,
           sp.gia_goc, sp.gia_khuyen_mai, sp.man_hinh_size, sp.man_hinh_dophangiai,
           th.ten_thuonghieu, dm.ten_danhmuc,
           b_min.gia_thap,
           (SELECT MAX(bt.gia) FROM bienthe_sanpham bt WHERE bt.ma_sanpham = sp.ma_sanpham AND bt.is_active=1) as gia_cao_nhat,
           (SELECT MIN(bt.gia_goc) FROM bienthe_sanpham bt WHERE bt.ma_sanpham = sp.ma_sanpham AND bt.is_active=1) as gia_goc_thap_nhat,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh_chinh,
           (SELECT COUNT(*) FROM bienthe_sanpham bc WHERE bc.ma_sanpham = sp.ma_sanpham AND bc.ton_kho > 0) as so_bienthe_conhang,
           (SELECT COUNT(DISTINCT mau_sac) FROM bienthe_sanpham bm WHERE bm.ma_sanpham = sp.ma_sanpham) as so_mau
    FROM sanpham sp
    JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
    LEFT JOIN (SELECT ma_sanpham, MIN(gia) as gia_thap FROM bienthe_sanpham WHERE is_active=1 GROUP BY ma_sanpham) b_min ON sp.ma_sanpham = b_min.ma_sanpham
    LEFT JOIN danhmuc dm ON sp.ma_danhmuc = dm.ma_danhmuc
    WHERE $whereSQL
    ORDER BY $sort
    LIMIT {$paging['per_page']} OFFSET {$paging['offset']}
";
$products = db()->fetchAll($productsSQL, $params);

// Sidebar filter data
$allBrands = db()->fetchAll("SELECT th.*, COUNT(sp.ma_sanpham) as so_sp FROM thuonghieu th LEFT JOIN sanpham sp ON th.ma_thuonghieu = sp.ma_thuonghieu AND sp.is_active = 1 WHERE th.is_active = 1 GROUP BY th.ma_thuonghieu ORDER BY so_sp DESC");
$allCategories = db()->fetchAll("SELECT dm.*, (SELECT COUNT(*) FROM sanpham_danhmuc spdm JOIN sanpham sp ON spdm.ma_sanpham = sp.ma_sanpham WHERE spdm.ma_danhmuc = dm.ma_danhmuc AND sp.is_active = 1) as so_sp FROM danhmuc dm WHERE dm.is_active = 1 ORDER BY dm.thu_tu");

// Wishlist
$wishedProducts = [];
if (isLoggedIn()) {
    $wished = db()->fetchAll("SELECT ma_sanpham FROM dsyeuthich WHERE ma_user = ?", [$_SESSION['user_site']['id']]);
    $wishedProducts = array_column($wished, 'ma_sanpham');
}
?>

<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <!-- Breadcrumb -->
    <nav style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
        <a href="<?= BASE_URL ?>">Trang chủ</a> → 
        <?php if (!empty($_GET['danhmuc'])): ?>
            <span><?= sanitize($_GET['danhmuc']) ?></span>
        <?php elseif (!empty($_GET['q'])): ?>
            <span>Tìm kiếm: "<?= sanitize($_GET['q']) ?>"</span>
        <?php else: ?>
            <span>Tất cả điện thoại</span>
        <?php endif; ?>
    </nav>

    <div class="shop-layout">
        <!-- ===== SIDEBAR FILTER ===== -->
        <aside class="filter-sidebar" id="filterSidebar">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 class="filter-title" style="margin:0">🔧 Bộ Lọc</h3>
                <a href="<?= BASE_URL ?>/products.php" style="font-size:13px;color:var(--danger)">Xóa tất cả</a>
            </div>

            <form method="GET" id="filterForm">
                <?php if (!empty($_GET['q'])): ?>
                <input type="hidden" name="q" value="<?= sanitize($_GET['q']) ?>">
                <?php endif; ?>

                <!-- Danh mục -->
                <div class="filter-group">
                    <div class="filter-group-title">Danh Mục</div>
                    <div class="filter-options">
                        <?php foreach ($allCategories as $cat): ?>
                        <label class="filter-option">
                            <input type="radio" name="danhmuc" value="<?= $cat['slug'] ?>" <?= (($_GET['danhmuc'] ?? '') === $cat['slug']) ? 'checked' : '' ?>>
                            <span><?= sanitize($cat['ten_danhmuc']) ?></span>
                            <span style="margin-left:auto;font-size:12px;color:var(--text-muted)">(<?= $cat['so_sp'] ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Thương hiệu -->
                <div class="filter-group">
                    <div class="filter-group-title">Thương Hiệu</div>
                    <div class="filter-options">
                        <?php foreach ($allBrands as $brand): ?>
                        <label class="filter-option">
                            <input type="radio" name="thuonghieu" value="<?= $brand['slug'] ?>" <?= (($_GET['thuonghieu'] ?? '') === $brand['slug']) ? 'checked' : '' ?>>
                            <span><?= sanitize($brand['ten_thuonghieu']) ?></span>
                            <span style="margin-left:auto;font-size:12px;color:var(--text-muted)">(<?= $brand['so_sp'] ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Khoảng giá -->
                <div class="filter-group">
                    <div class="filter-group-title">Khoảng Giá</div>
                    <div class="price-range">
                        <input type="number" name="gia_tu" placeholder="Từ" value="<?= sanitize($_GET['gia_tu'] ?? '') ?>" min="0">
                        <input type="number" name="gia_den" placeholder="Đến" value="<?= sanitize($_GET['gia_den'] ?? '') ?>" min="0">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px;">
                        <?php
                        $priceRanges = [
                            ['label'=>'Dưới 3 triệu','tu'=>0,'den'=>3000000],
                            ['label'=>'3-7 triệu','tu'=>3000000,'den'=>7000000],
                            ['label'=>'7-15 triệu','tu'=>7000000,'den'=>15000000],
                            ['label'=>'Trên 15 triệu','tu'=>15000000,'den'=>99999999],
                        ];
                        foreach ($priceRanges as $range): ?>
                        <button type="button" class="btn btn-outline btn-sm" onclick="setPriceRange(<?= $range['tu'] ?>, <?= $range['den'] ?>)"><?= $range['label'] ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Đánh giá -->
                <div class="filter-group">
                    <div class="filter-group-title">Đánh Giá Tối Thiểu</div>
                    <div class="filter-options">
                        <?php foreach ([5,4,3] as $star): ?>
                        <label class="filter-option">
                            <input type="radio" name="danhgia" value="<?= $star ?>" <?= (($_GET['danhgia'] ?? '') == $star) ? 'checked' : '' ?>>
                            <span><?= str_repeat('★', $star) ?> trở lên</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Áp Dụng Bộ Lọc</button>
            </form>
        </aside>

        <!-- ===== PRODUCT GRID ===== -->
        <div>
            <!-- Toolbar -->
            <div class="products-toolbar">
                <div class="products-count">
                    Tìm thấy <strong><?= $total ?></strong> điện thoại
                    <?php if (!empty($_GET['q'])): ?>
                    cho "<em><?= sanitize($_GET['q']) ?></em>"
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button class="btn btn-outline btn-sm" onclick="document.getElementById('filterSidebar').classList.toggle('open')" style="display:none;<?php /* mobile filter btn */ ?>">⚙️ Lọc</button>
                    <select class="sort-select" onchange="changeSort(this.value)">
                        <option value="ban_chay"  <?= ($_GET['sort']??'')==='ban_chay'  ?'selected':'' ?>>Bán chạy nhất</option>
                        <option value="moi_nhat"  <?= ($_GET['sort']??'')==='moi_nhat'  ?'selected':'' ?>>Mới nhất</option>
                        <option value="gia_tang"  <?= ($_GET['sort']??'')==='gia_tang'  ?'selected':'' ?>>Giá tăng dần</option>
                        <option value="gia_giam"  <?= ($_GET['sort']??'')==='gia_giam'  ?'selected':'' ?>>Giá giảm dần</option>
                        <option value="danhgia"   <?= ($_GET['sort']??'')==='danhgia'   ?'selected':'' ?>>Đánh giá cao</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3 class="empty-title">Không tìm thấy sản phẩm</h3>
                <p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
                <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary" style="margin-top:16px;">Xem tất cả sản phẩm</a>
            </div>
            <?php else: ?>

            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($paging['total_pages'] > 1): ?>
            <div class="pagination">
                <?php
                $currentQuery = $_GET;
                if ($page > 1):
                    $currentQuery['page'] = $page - 1;
                ?>
                <a href="?<?= http_build_query($currentQuery) ?>" class="page-btn">‹</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page-2); $i <= min($paging['total_pages'], $page+2); $i++):
                    $currentQuery['page'] = $i;
                ?>
                <a href="?<?= http_build_query($currentQuery) ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $paging['total_pages']):
                    $currentQuery['page'] = $page + 1;
                ?>
                <a href="?<?= http_build_query($currentQuery) ?>" class="page-btn">›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeSort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
function setPriceRange(tu, den) {
    document.querySelector('input[name=gia_tu]').value = tu;
    document.querySelector('input[name=gia_den]').value = den;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
