<?php
require_once __DIR__ . '/includes/auth_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $ten   = trim($_POST['ten_thuonghieu'] ?? '');
        $slug  = trim($_POST['slug'] ?? '') ?: generateSlug($ten);
        $quocgia = trim($_POST['quoc_gia'] ?? '');
        $mota  = trim($_POST['mo_ta'] ?? '');
        if ($ten) {
            try {
                db()->insert("INSERT INTO thuonghieu (ten_thuonghieu, slug, quoc_gia, mo_ta, is_active) VALUES (?,?,?,?,1)",
                    [$ten, $slug, $quocgia, $mota]);
                setFlash('success', 'Thêm thương hiệu thành công!');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi: Tên hoặc slug đã tồn tại.');
            }
        }
    }
    elseif ($action === 'edit') {
        $id   = (int)$_POST['id'];
        $ten  = trim($_POST['ten_thuonghieu'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $quocgia = trim($_POST['quoc_gia'] ?? '');
        $mota = trim($_POST['mo_ta'] ?? '');
        if ($id && $ten) {
            db()->execute("UPDATE thuonghieu SET ten_thuonghieu=?, slug=?, quoc_gia=?, mo_ta=? WHERE ma_thuonghieu=?",
                [$ten, $slug, $quocgia, $mota, $id]);
            setFlash('success', 'Cập nhật thành công!');
        }
    }
    elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        db()->execute("UPDATE thuonghieu SET is_active = !is_active WHERE ma_thuonghieu=?", [$id]);
        setFlash('success', 'Đã cập nhật!');
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $used = db()->fetchColumn("SELECT COUNT(*) FROM sanpham WHERE ma_thuonghieu=?", [$id]);
        if ($used > 0) {
            setFlash('error', "Không thể xóa! Thương hiệu đang có $used sản phẩm.");
        } else {
            db()->execute("DELETE FROM thuonghieu WHERE ma_thuonghieu=?", [$id]);
            setFlash('success', 'Đã xóa thương hiệu!');
        }
    }
    redirect(BASE_URL . '/admin/product_settings.php?tab=brands');
}

$pageTitle = 'Thương Hiệu';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}


$brands = db()->fetchAll("
    SELECT th.*, COUNT(sp.ma_sanpham) as so_sanpham
    FROM thuonghieu th LEFT JOIN sanpham sp ON th.ma_thuonghieu = sp.ma_thuonghieu
    GROUP BY th.ma_thuonghieu ORDER BY th.ten_thuonghieu ASC
");
?>

<!-- Brands Layout Section -->
<div class="page-header">
    <div>
        <h1 class="page-title">🏷️ QUẢN LÝ THƯƠNG HIỆU</h1>
        <p class="page-desc">Hệ thống đang hợp tác với <strong><?= count($brands) ?></strong> thương hiệu quốc tế</p>
    </div>
    <button class="btn btn-primary" onclick="toggleBrandForm('add')">
        <span class="icon">➕</span> THÊM THƯƠNG HIỆU
    </button>
</div>

<!-- Dynamic Add/Edit Brand Form Section -->
<div id="brandFormCard" class="section-card animate-fade-up" style="display: none; margin-bottom: 30px; border-top: 4px solid var(--purple);">
    <div class="section-card-header">
        <h3 id="formTitle">✨ THÊM THƯƠNG HIỆU MỚI</h3>
    </div>
    <div class="section-card-body">
        <form method="POST" id="brandForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="brandId">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">TÊN THƯƠNG HIỆU *</label>
                    <input type="text" name="ten_thuonghieu" id="brandTen" class="form-control" required placeholder="VD: Apple, Samsung..." oninput="generateBrandSlug(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">SLUG (URL)</label>
                    <input type="text" name="slug" id="brandSlug" class="form-control" placeholder="tu-dong-tao-neu-trong">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">QUỐC GIA</label>
                    <input type="text" name="quoc_gia" id="brandQuocgia" class="form-control" placeholder="VD: Mỹ, Hàn Quốc...">
                </div>
                <div class="form-group">
                    <label class="form-label">MÔ TẢ NGẮN</label>
                    <input type="text" name="mo_ta" id="brandMota" class="form-control" placeholder="Nhập mô tả ngắn về thương hiệu...">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="toggleBrandForm(null)">HỦY BỎ</button>
                <button type="submit" class="btn btn-primary" style="min-width: 180px;">LƯU THÔNG TIN</button>
            </div>
        </form>
    </div>
</div>

<!-- Brands Table Section -->
<div class="section-card">
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="80" style="padding-left: 30px;">ID</th>
                    <th>THƯƠNG HIỆU</th>
                    <th>SLUG (URL)</th>
                    <th>QUỐC GIA</th>
                    <th style="text-align: center;">SẢN PHẨM</th>
                    <th style="text-align: center;">TRẠNG THÁI</th>
                    <th style="padding-right: 30px;">HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px; color: var(--txt3); font-weight: 700;">
                        🚫 CHƯA CÓ DỮ LIỆU THƯƠNG HIỆU
                    </td>
                </tr>
                <?php else: foreach ($brands as $b): ?>
                <tr>
                    <td style="padding-left: 30px; font-weight: 800; color: var(--txt3);">#<?= $b['ma_thuonghieu'] ?></td>
                    <td>
                        <div style="font-weight: 800; color: var(--txt); font-size: 14px;"><?= sanitize($b['ten_thuonghieu']) ?></div>
                    </td>
                    <td><code style="background: var(--card2); color: var(--accent); padding: 5px 10px; border-radius: 8px; font-size: 11px; font-weight: 700;"><?= sanitize($b['slug']) ?></code></td>
                    <td>
                        <span style="font-weight: 700; color: var(--txt2);">
                            <?= sanitize($b['quoc_gia'] ?: '---') ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-purple" style="font-size: 11px;">
                            <?= $b['so_sanpham'] ?> SP
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $b['ma_thuonghieu'] ?>">
                            <button type="submit" class="badge <?= $b['is_active'] ? 'badge-success' : 'badge-danger' ?>" 
                                    style="cursor: pointer; border: 1px solid var(--border); background: #fff; appearance: none; -webkit-appearance: none; font-family: inherit;">
                                <?= $b['is_active'] ? 'HOẠT ĐỘNG' : 'ẨN' ?>
                            </button>
                        </form>
                    </td>
                    <td style="padding-right: 30px;">
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button class="btn-icon btn-outline" title="Chỉnh sửa" onclick='openEditBrand(<?= json_encode($b) ?>)' style="color: var(--purple); border-color: var(--purple-light);">
                                ✏️
                            </button>
                            <form method="POST" style="display: contents;" onsubmit="return confirm('⚠️ Xác nhận XÓA thương hiệu này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $b['ma_thuonghieu'] ?>">
                                <button type="submit" class="btn-icon btn-outline" style="color: var(--danger); border-color: #fee2e2;" title="Xóa">
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
function toggleBrandForm(mode) {
    const card = document.getElementById('brandFormCard');
    const title = document.getElementById('formTitle');
    const action = document.getElementById('formAction');
    const form = document.getElementById('brandForm');
    
    if (mode === null) {
        card.style.display = 'none';
        return;
    }

    if (mode === 'add') {
        title.innerHTML = '✨ THÊM THƯƠNG HIỆU MỚI';
        action.value = 'add';
        form.reset();
        document.getElementById('brandId').value = '';
    }
    
    card.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openEditBrand(b) {
    toggleBrandForm('edit');
    document.getElementById('formTitle').innerHTML = '✏️ CHỈNH SỬA THƯƠNG HIỆU';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('brandId').value = b.ma_thuonghieu;
    document.getElementById('brandTen').value = b.ten_thuonghieu;
    document.getElementById('brandSlug').value = b.slug;
    document.getElementById('brandQuocgia').value = b.quoc_gia || '';
    document.getElementById('brandMota').value = b.mo_ta || '';
}

function generateBrandSlug(input) {
    const slug = input.value.toLowerCase()
        .replace(/[àáảãạăắặằẳẵâấầẩẫậ]/g,'a')
        .replace(/[đ]/g,'d')
        .replace(/[èéẻẽẹêếềểễệ]/g,'e')
        .replace(/[ìíỉĩị]/g,'i')
        .replace(/[òóỏõọôốồổỗộơớờởỡợ]/g,'o')
        .replace(/[ùúủũụưứừửữự]/g,'u')
        .replace(/[ỳýỷỹỵ]/g,'y')
        .replace(/[^a-z0-9\s]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim('-');
    document.getElementById('brandSlug').value = slug;
}
</script>

<?php if (!isset($is_included_mode)): ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
