<?php
require_once __DIR__ . '/includes/auth_admin.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['status'];
        db()->execute("UPDATE sanpham SET is_active = ? WHERE ma_sanpham = ?", [$status, $id]);
        setFlash('success', 'Đã cập nhật trạng thái sản phẩm');
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Kiểm tra xem sản phẩm có trong đơn hàng nào không
        $hasOrders = db()->fetchColumn("
            SELECT COUNT(*) FROM chitiet_donhang ctdh 
            JOIN bienthe_sanpham b ON ctdh.ma_bienthe = b.ma_bienthe 
            WHERE b.ma_sanpham = ?", [$id]);
            
        if ($hasOrders > 0) {
            // Nếu có đơn hàng, chỉ ẩn sản phẩm (xóa mềm)
            db()->execute("UPDATE sanpham SET is_active = -1 WHERE ma_sanpham = ?", [$id]);
            setFlash('success', 'Sản phẩm có đơn hàng liên quan nên đã được ẩn khỏi danh sách.');
        } else {
            // Nếu không có đơn hàng, tiến hành xóa cứng (xóa an toàn theo thứ tự)
            
            // 1. Xóa ảnh đang liên kết (vì đã có file vật lý cần xóa tay, bảng có thể tự xóa qua CASCADE, nhưng tốt nhất cứ check)
            $images = db()->fetchAll("SELECT image_url FROM hinhanh_sanpham WHERE ma_sanpham = ?", [$id]);
            foreach ($images as $img) {
                @unlink(UPLOAD_DIR . $img['image_url']);
            }
            
            // 2. Lấy các mã biến thể thuộc sản phẩm này
            $bienthes = db()->fetchAll("SELECT ma_bienthe FROM bienthe_sanpham WHERE ma_sanpham = ?", [$id]);
            foreach ($bienthes as $bt) {
                // Xóa chi tiết giỏ hàng đang tham chiếu tới biến thể (Làm rõ lỗi 1451 Cannot delete or update a parent row)
                db()->execute("DELETE FROM chitiet_giohang WHERE ma_bienthe = ?", [$bt['ma_bienthe']]);
			}
            
            // 3. Xóa các biến thể
            db()->execute("DELETE FROM bienthe_sanpham WHERE ma_sanpham = ?", [$id]);
            
            // 4. Xóa liên kết danh mục
            db()->execute("DELETE FROM sanpham_danhmuc WHERE ma_sanpham = ?", [$id]);

            // 5. Cuối cùng, xóa sản phẩm gốc
            db()->execute("DELETE FROM sanpham WHERE ma_sanpham = ?", [$id]);
            setFlash('success', 'Đã xóa hoàn toàn sản phẩm.');
        }
    }
    redirect(BASE_URL . '/admin/product_settings.php?tab=products');
}

$pageTitle = 'Quản Lý Sản Phẩm';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}


// Query
$q = sanitize($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$page = max(1,(int)($_GET['page']??1));
$where = ['sp.is_active >= 0'];
$params = [];
if ($q) { $where[] = 'sp.ten_sanpham LIKE ?'; $params[] = "%$q%"; }
if ($catFilter) { $where[] = 'sp.ma_danhmuc = ?'; $params[] = $catFilter; }
$whereSQL = implode(' AND ', $where);

$total = (int)db()->fetchColumn("SELECT COUNT(*) FROM sanpham sp WHERE $whereSQL", $params);
$paging = paginate($total, $page, ADMIN_PER_PAGE);

$products = db()->fetchAll("
    SELECT sp.*, dm.ten_danhmuc, th.ten_thuonghieu,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_min,
           (SELECT SUM(ton_kho) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as ton_kho_total,
           (SELECT COUNT(*) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham) as so_bienthe,
           (SELECT COALESCE(SUM(ct.so_luong), 0) 
            FROM chitiet_donhang ct 
            JOIN bienthe_sanpham b ON ct.ma_bienthe = b.ma_bienthe 
            JOIN donhang dh ON ct.ma_donhang = dh.ma_donhang 
            WHERE b.ma_sanpham = sp.ma_sanpham 
            AND dh.trang_thai NOT IN ('da_huy', 'da_tra_hang')) as tong_da_ban
    FROM sanpham sp
    LEFT JOIN danhmuc dm ON sp.ma_danhmuc = dm.ma_danhmuc
    JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
    WHERE $whereSQL ORDER BY sp.ngay_lap DESC
    LIMIT {$paging['per_page']} OFFSET {$paging['offset']}
", $params);

$categories = db()->fetchAll("SELECT * FROM danhmuc WHERE is_active = 1 ORDER BY thu_tu");
$brands     = db()->fetchAll("SELECT * FROM thuonghieu WHERE is_active = 1 ORDER BY ten_thuonghieu");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📦 QUẢN LÝ SẢN PHẨM</h1>
        <p class="page-desc">Hệ thống đang lưu trữ và phân phối <strong><?= $total ?></strong> sản phẩm</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/product_form.php" class="btn btn-primary">
        <span class="icon">➕</span> THÊM SẢN PHẨM MỚI
    </a>
</div>

<!-- Modern Filter Bar -->
<div class="animate-fade-up" style="margin-bottom: 30px;">
    <form method="GET" class="filter-bar">
        <div class="search-group" style="flex: 3;">
            <span class="icon">🔍</span>
            <input type="text" name="q" class="form-control" placeholder="Tìm kiếm theo tên sản phẩm..." value="<?= sanitize($q) ?>">
        </div>
        <div class="filter-select-group">
            <select name="cat" class="form-control">
                <option value="">📁 Tất cả danh mục</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['ma_danhmuc'] ?>" <?= $catFilter == $c['ma_danhmuc'] ? 'selected' : '' ?>><?= sanitize($c['ten_danhmuc']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary btn-filter">
                <span>LỌC DỮ LIỆU</span>
            </button>
            <a href="<?= BASE_URL ?>/admin/product_settings.php?tab=products" class="btn btn-outline btn-filter" style="background: #fff;">
                <span>RESET</span>
            </a>
        </div>
    </form>
</div>

<!-- Modern Product Table -->
<div class="section-card">
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="text-align:left; padding-left: 30px;">THÔNG TIN SẢN PHẨM</th>
                    <th>DANH MỤC / HÃNG</th>
                    <th>GIÁ NIÊM YẾT</th>
                    <th>KHO HÀNG</th>
                    <th>DOANH SỐ</th>
                    <th>TRẠNG THÁI</th>
                    <th style="padding-right: 30px;">HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): 
                    $img = $p['anh'] ? BASE_URL . '/uploads/products/' . basename($p['anh']) : 'https://placehold.co/44x44/f8fafc/8b5cf6?text=SP';
                ?>
                <tr>
                    <td style="text-align:left; padding-left: 30px;">
                        <div class="product-cell" style="justify-content:flex-start;">
                            <img src="<?= $img ?>" class="table-img" style="width: 54px; height: 54px; border: 1px solid var(--border); background: #fff;">
                            <div class="product-cell-info">
                                <div class="name" style="font-size: 14px; font-weight: 800; color: var(--txt);"><?= sanitize($p['ten_sanpham']) ?></div>
                                <div class="sku" style="font-weight: 700; color: var(--accent);"><?= $p['so_bienthe'] ?> BIẾN THỂ</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 800; color: var(--txt);"><?= !empty($p['ten_danhmuc']) ? sanitize($p['ten_danhmuc']) : '<span style="color:var(--txt3); font-style:italic;">Chưa phân loại</span>' ?></div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--txt3);"><?= sanitize($p['ten_thuonghieu']) ?></div>
                    </td>
                    <td style="font-weight: 900; color: var(--accent); font-size: 15px;">
                        <?= $p['gia_min'] ? number_format($p['gia_min'],0,',','.') . ' VND' : '---' ?>
                    </td>
                    <td>
                        <?php if ($p['ton_kho_total'] <= 0): ?>
                        <span class="badge badge-danger">HẾT HÀNG</span>
                        <?php elseif ($p['ton_kho_total'] <= 10): ?>
                        <span class="badge badge-warning">CÒN <?= $p['ton_kho_total'] ?></span>
                        <?php else: ?>
                        <span class="badge badge-success"><?= $p['ton_kho_total'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight: 800;"><?= number_format($p['tong_da_ban']) ?></span>
                        <div style="font-size: 10px; font-weight: 700; color: var(--txt3);">ĐÃ BÁN</div>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $p['ma_sanpham'] ?>">
                            <select name="status" class="badge <?= $p['is_active'] == 1 ? 'badge-success' : 'badge-danger' ?>" 
                                    style="cursor: pointer; background: none; font-family: inherit; appearance: none; -webkit-appearance: none; text-align: center;"
                                    onchange="this.form.submit()">
                                <option value="1" <?= $p['is_active'] == 1 ? 'selected' : '' ?>>ĐANG BÁN</option>
                                <option value="0" <?= $p['is_active'] == 0 ? 'selected' : '' ?>>ĐÃ ẨN</option>
                            </select>
                        </form>
                    </td>
                    <td style="padding-right: 30px;">
                        <div style="display:flex; gap:10px; justify-content: center;">
                            <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $p['ma_sanpham'] ?>" target="_blank" class="btn-icon btn-outline" title="Xem ngoài shop" style="display: flex; align-items: center; justify-content: center; border-radius: 10px;">👁️</a>
                            <a href="<?= BASE_URL ?>/admin/product_form.php?id=<?= $p['ma_sanpham'] ?>" class="btn-icon btn-outline" title="Chỉnh sửa nội dung" style="display: flex; align-items: center; justify-content: center; border-radius: 10px; color: var(--purple); border-color: var(--purple-light);">✏️</a>
                            <form method="POST" style="display:contents" onsubmit="return confirm('⚠️ XÓA VĨNH VIỄN SẢN PHẨM NÀY?\nThao tác này không thể hoàn tác.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['ma_sanpham'] ?>">
                                <button type="submit" class="btn-icon btn-outline" style="color: var(--danger); border-color: #fee2e2;">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paging['total_pages'] > 1): ?>
    <div class="pagination" style="padding: 30px;">
        <?php for ($i = 1; $i <= min($paging['total_pages'], 10); $i++): ?>
        <a href="?q=<?= urlencode($q) ?>&cat=<?= $catFilter ?>&page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!isset($is_included_mode)): ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
