<?php
require_once __DIR__ . '/includes/auth_admin.php';

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ten  = trim($_POST['ten_danhmuc'] ?? '');
        $original_slug = trim($_POST['slug'] ?? '') ?: $ten;
        $slug = getUniqueSlug('danhmuc', $original_slug);
        $mota = trim($_POST['mo_ta'] ?? '');
        $thu_tu = (int)($_POST['thu_tu'] ?? 0);
        if ($ten) {
            try {
                db()->insert("INSERT INTO danhmuc (ten_danhmuc, slug, mo_ta, thu_tu, is_active) VALUES (?,?,?,?,1)",
                    [$ten, $slug, $mota, $thu_tu]);
                setFlash('success', 'Thêm danh mục thành công!');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi: Slug hoặc tên đã tồn tại.');
            }
        }
    }
    elseif ($action === 'edit') {
        $id   = (int)$_POST['id'];
        $ten  = trim($_POST['ten_danhmuc'] ?? '');
        $original_slug = trim($_POST['slug'] ?? '') ?: $ten;
        $slug = getUniqueSlug('danhmuc', $original_slug, 'ma_danhmuc', $id);
        $mota = trim($_POST['mo_ta'] ?? '');
        $thu_tu = (int)($_POST['thu_tu'] ?? 0);
        if ($id && $ten) {
            db()->execute("UPDATE danhmuc SET ten_danhmuc=?, slug=?, mo_ta=?, thu_tu=? WHERE ma_danhmuc=?",
                [$ten, $slug, $mota, $thu_tu, $id]);
            setFlash('success', 'Cập nhật danh mục thành công!');
        }
    }
    elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        db()->execute("UPDATE danhmuc SET is_active = !is_active WHERE ma_danhmuc=?", [$id]);
        setFlash('success', 'Đã cập nhật trạng thái!');
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        try {
            // Chỉ chặn xóa nếu có sản phẩm ĐANG HOẠT ĐỘNG hoặc TẠM ẨN (is_active >= 0)
            // Các sản phẩm ĐÃ XÓA MỀM (is_active = -1) sẽ được tự động gỡ liên kết bởi Database (SET NULL)
            $usedInProducts = db()->fetchColumn("SELECT COUNT(*) FROM sanpham WHERE ma_danhmuc=? AND is_active >= 0", [$id]);
            $usedInMapping  = db()->fetchColumn("
                SELECT COUNT(*) FROM sanpham_danhmuc spdm 
                JOIN sanpham sp ON spdm.ma_sanpham = sp.ma_sanpham 
                WHERE spdm.ma_danhmuc=? AND sp.is_active >= 0", [$id]);
            
            $totalActiveUsed = $usedInProducts + $usedInMapping;
            
            if ($totalActiveUsed > 0) {
                setFlash('error', "Không thể xóa! Danh mục này đang được gán cho $totalActiveUsed sản phẩm đang hoạt động. Hãy gỡ danh mục khỏi các sản phẩm này trước.");
            } else {
                db()->execute("DELETE FROM sanpham_danhmuc WHERE ma_danhmuc=?", [$id]);
                db()->execute("DELETE FROM danhmuc WHERE ma_danhmuc=?", [$id]);
                setFlash('success', 'Đã xóa danh mục thành công!');
            }
        } catch (Exception $e) {
            setFlash('error', 'Lỗi hệ thống không thể xóa: ' . $e->getMessage());
        }
    }
    redirect(BASE_URL . '/admin/product_settings.php?tab=categories');
}

$pageTitle = 'Danh Mục';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}


$categories = db()->fetchAll("
    SELECT dm.*, 
        (SELECT COUNT(DISTINCT combined.ma_sanpham) FROM (
            SELECT ma_sanpham, ma_danhmuc FROM sanpham WHERE is_active >= 0
            UNION ALL
            SELECT spdm.ma_sanpham, spdm.ma_danhmuc 
            FROM sanpham_danhmuc spdm
            JOIN sanpham sp ON spdm.ma_sanpham = sp.ma_sanpham
            WHERE sp.is_active >= 0
        ) as combined WHERE combined.ma_danhmuc = dm.ma_danhmuc) as so_sanpham
    FROM danhmuc dm
    ORDER BY dm.thu_tu ASC, dm.ma_danhmuc ASC
");
?>

<!-- Categories Layout Section -->
<div class="page-header">
    <div>
        <h1 class="page-title">🗂️ QUẢN LÝ DANH MỤC</h1>
        <p class="page-desc">Tổ chức và phân loại sản phẩm của bạn một cách chuyên nghiệp</p>
    </div>
    <button class="btn btn-primary" onclick="toggleForm('add')">
        <span class="icon">+</span> THÊM DANH MỤC
    </button>
</div>

<!-- Dynamic Add/Edit Form Section -->
<div id="categoryFormCard" class="section-card animate-fade-up" style="display: none; margin-bottom: 30px; border-top: 4px solid var(--accent);">
    <div class="section-card-header">
        <h3 id="formTitle">✨ THÊM DANH MỤC MỚI</h3>
    </div>
    <div class="section-card-body">
        <form method="POST" id="categoryForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="catId">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">TÊN DANH MỤC *</label>
                    <input type="text" name="ten_danhmuc" id="catTen" class="form-control" required placeholder="VD: iPhone 16 Series" oninput="generateAutoSlug(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">SLUG (URL)</label>
                    <input type="text" name="slug" id="catSlug" class="form-control" placeholder="tu-dong-tao-neu-trong">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">MÔ TẢ DANH MỤC</label>
                <textarea name="mo_ta" id="catMota" class="form-control" rows="3" placeholder="Nhập mô tả ngắn cho danh mục này..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">THỨ TỰ HIỂN THỊ</label>
                    <input type="number" name="thu_tu" id="catThutu" class="form-control" value="0" min="0">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="toggleForm(null)">HỦY BỎ</button>
                    <button type="submit" class="btn btn-primary" style="min-width: 150px;">LƯU DANH MỤC</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Categories Table Section -->
<div class="section-card animate-fade-up">
    <div class="section-card-body" style="padding: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60" style="text-align:center;">ID</th>
                    <th style="text-align:center;">DANH MỤC</th>
                    <th style="text-align:center;">SLUG (URL)</th>
                    <th width="120" style="text-align: center;">SẢN PHẨM</th>
                    <th width="100" style="text-align: center;">THỨ TỰ</th>
                    <th width="120" style="text-align: center;">TRẠNG THÁI</th>
                    <th width="150" style="text-align: center;">HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--txt3);">
                        Chưa có danh mục nào được tạo.
                    </td>
                </tr>
                <?php else: foreach ($categories as $cat): ?>
                <tr>
                    <td style="color: var(--txt3); text-align:center;">#<?= $cat['ma_danhmuc'] ?></td>
                    <td style="text-align:center;">
                        <div style="font-weight: 700; color: var(--txt); margin: 0 auto;"><?= sanitize($cat['ten_danhmuc']) ?></div>
                        <div style="font-size: 11px; color: var(--txt2); margin: 2px auto 0; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= sanitize($cat['mo_ta'] ?? 'Không có mô tả') ?>
                        </div>
                    </td>
                    <td style="text-align:center;"><code style="background: var(--card2); color: var(--accent); padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?= sanitize($cat['slug']) ?></code></td>
                    <td style="text-align: center;">
                        <span class="badge badge-purple">
                            <?= $cat['so_sanpham'] ?>
                        </span>
                    </td>
                    <td style="text-align: center; color: var(--txt2);"><?= $cat['thu_tu'] ?></td>
                    <td style="text-align: center;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $cat['ma_danhmuc'] ?>">
                            <button type="submit" class="badge <?= $cat['is_active'] ? 'badge-success' : 'badge-danger' ?>" style="cursor: pointer; border: 1px solid currentColor;">
                                <?= $cat['is_active'] ? 'HIỂN THỊ' : 'ẨN' ?>
                            </button>
                        </form>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 8px; justify-content: center;">
                            <button class="btn btn-icon btn-outline" title="Chỉnh sửa" onclick='openEdit(<?= json_encode($cat) ?>)'>
                                ✏️
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Xác nhận xóa danh mục này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['ma_danhmuc'] ?>">
                                <button type="submit" class="btn btn-icon btn-outline" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" title="Xóa">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleForm(mode) {
    const card = document.getElementById('categoryFormCard');
    const title = document.getElementById('formTitle');
    const action = document.getElementById('formAction');
    const form = document.getElementById('categoryForm');
    
    if (mode === null) {
        card.style.display = 'none';
        return;
    }

    if (mode === 'add') {
        title.innerHTML = '✨ THÊM DANH MỤC MỚI';
        action.value = 'add';
        form.reset();
        document.getElementById('catId').value = '';
    }
    
    card.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openEdit(cat) {
    toggleForm('edit');
    document.getElementById('formTitle').innerHTML = '✏️ CHỈNH SỬA DANH MỤC';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('catId').value = cat.ma_danhmuc;
    document.getElementById('catTen').value = cat.ten_danhmuc;
    document.getElementById('catSlug').value = cat.slug;
    document.getElementById('catMota').value = cat.mo_ta || '';
    document.getElementById('catThutu').value = cat.thu_tu;
}

function generateAutoSlug(input) {
    const slug = input.value.toLowerCase()
        .replace(/[àáảãạăắặằẳẵâấầẩẫậ]/g,'a')
        .replace(/[đ]/g,'d')
        .replace(/[èéẻẽẹêếềểễệ]/g,'e')
        .replace(/[ìíỉĩị]/g,'i')
        .replace(/[òóỏõọôốồổỗộơớờởỡợ]/g,'o')
        .replace(/[ùúủũụưứừửữự]/g,'u')
        .replace(/[ỳýỷỹỵ]/g,'y')
        .replace(/[^a-z0-9\s]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim('-');
    document.getElementById('catSlug').value = slug;
}
</script>

<?php if (!isset($is_included_mode)): ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
