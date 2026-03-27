<?php
$pageTitle = 'Thông Tin Sản Phẩm';
require_once __DIR__ . '/includes/auth_admin.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$variants = [];
$productImages = [];

if ($id > 0) {
    $product = db()->fetchOne("SELECT * FROM sanpham WHERE ma_sanpham = ?", [$id]);
    if (!$product) {
        setFlash('error', 'Không tìm thấy sản phẩm!');
        redirect(BASE_URL . '/admin/products.php');
    }
    $variants = db()->fetchAll("SELECT * FROM bienthe_sanpham WHERE ma_sanpham = ?", [$id]);
    $productImages = db()->fetchAll("SELECT * FROM hinhanh_sanpham WHERE ma_sanpham = ? ORDER BY la_anh_chinh DESC, thu_tu ASC", [$id]);
    
    // Lấy danh mục sản phẩm (mới)
    $productCategories = db()->fetchAll("SELECT ma_danhmuc FROM sanpham_danhmuc WHERE ma_sanpham = ?", [$id]);
    $productCategoryIds = array_column($productCategories, 'ma_danhmuc');
} else {
    $productCategoryIds = [];
}

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_sanpham = sanitize($_POST['ten_sanpham']);
    $original_slug = trim($_POST['slug'] ?? '') ?: $ten_sanpham;
    $slug = getUniqueSlug('sanpham', $original_slug, 'ma_sanpham', $id > 0 ? $id : null);
    $ma_sanpham_code = sanitize($_POST['ma_sanpham_code'] ?? '');
    $ma_danhmucs = $_POST['ma_danhmuc'] ?? []; // Mảng các danh mục
    $ma_thuonghieu = (int)$_POST['ma_thuonghieu'];
    
    // Xử lý giá: Loại bỏ dấu chấm (nếu người dùng nhập dạng 1.000.000)
    $clean_gia_goc = str_replace('.', '', $_POST['gia_goc']);
    $gia_goc = (float)$clean_gia_goc;
    
    $gia_khuyen_mai = null;
    if (!empty($_POST['gia_khuyen_mai'])) {
        $clean_gia_km = str_replace('.', '', $_POST['gia_khuyen_mai']);
        $gia_khuyen_mai = (float)$clean_gia_km;
    }

    $mo_ta_ngan = sanitize($_POST['mo_ta_ngan']);
    $mo_ta_day_du = $_POST['mo_ta_day_du']; 
    
    $he_dieu_hanh = sanitize($_POST['he_dieu_hanh'] ?? '');
    $chip = sanitize($_POST['chip'] ?? '');
    $man_hinh_size = (float)($_POST['man_hinh_size'] ?? 0) ?: null;
    $man_hinh_loai = sanitize($_POST['man_hinh_loai'] ?? '');
    $pin_dung_luong = (int)($_POST['pin_dung_luong'] ?? 0) ?: null;
    $sac_nhanh = (int)($_POST['sac_nhanh'] ?? 0) ?: null;
    $camera_sau = sanitize($_POST['camera_sau'] ?? '');
    $camera_truoc = sanitize($_POST['camera_truoc'] ?? '');
    $is_noi_bat = isset($_POST['is_noi_bat']) ? 1 : 0;
    $is_hang_moi = isset($_POST['is_hang_moi']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate: phải chọn ít nhất 1 danh mục
    if (empty($ma_danhmucs)) {
        setFlash('error', 'Vui lòng chọn ít nhất một danh mục sản phẩm!');
        // Không redirect, để form hiển thị lại với lỗi
    } else {

    try {
        db()->beginTransaction();

        if ($id > 0) {
            // Cập nhật sản phẩm (vẫn giữ ma_danhmuc cũ để tương thích nhưng sẽ lấy cái đầu tiên)
            $first_dm = (int)$ma_danhmucs[0];

            db()->execute("UPDATE sanpham SET 
                ten_sanpham=?, slug=?, ma_sanpham_code=?, ma_danhmuc=?, ma_thuonghieu=?, 
                mo_ta_ngan=?, mo_ta_day_du=?, gia_goc=?, gia_khuyen_mai=?,
                he_dieu_hanh=?, chip=?, man_hinh_size=?, man_hinh_loai=?, 
                pin_dung_luong=?, sac_nhanh=?, camera_sau=?, camera_truoc=?, 
                is_noi_bat=?, is_hang_moi=?, is_active=? 
                WHERE ma_sanpham=?", 
                [$ten_sanpham, $slug, $ma_sanpham_code, $first_dm, $ma_thuonghieu, 
                 $mo_ta_ngan, $mo_ta_day_du, $gia_goc, $gia_khuyen_mai,
                 $he_dieu_hanh, $chip, $man_hinh_size, $man_hinh_loai, 
                 $pin_dung_luong, $sac_nhanh, $camera_sau, $camera_truoc, 
                 $is_noi_bat, $is_hang_moi, $is_active, $id]);
            $productId = $id;
        } else {
            // Thêm mới sản phẩm
            $first_dm = (int)$ma_danhmucs[0];

            $productId = db()->insert("INSERT INTO sanpham (
                ten_sanpham, slug, ma_sanpham_code, ma_danhmuc, ma_thuonghieu, 
                mo_ta_ngan, mo_ta_day_du, gia_goc, gia_khuyen_mai,
                he_dieu_hanh, chip, man_hinh_size, man_hinh_loai, 
                pin_dung_luong, sac_nhanh, camera_sau, camera_truoc, 
                is_noi_bat, is_hang_moi, is_active
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", 
                [$ten_sanpham, $slug, $ma_sanpham_code, $first_dm, $ma_thuonghieu, 
                 $mo_ta_ngan, $mo_ta_day_du, $gia_goc, $gia_khuyen_mai,
                 $he_dieu_hanh, $chip, $man_hinh_size, $man_hinh_loai, 
                 $pin_dung_luong, $sac_nhanh, $camera_sau, $camera_truoc, 
                 $is_noi_bat, $is_hang_moi, $is_active]);
        }

        // --- Xử lý Nhiều danh mục ---
        db()->execute("DELETE FROM sanpham_danhmuc WHERE ma_sanpham = ?", [$productId]);
        foreach ($ma_danhmucs as $dm_id) {
            db()->execute("INSERT INTO sanpham_danhmuc (ma_sanpham, ma_danhmuc) VALUES (?,?)", [$productId, (int)$dm_id]);
        }

        // --- Xử lý biến thể ---
        $savedVariantIdsByColor = []; // Map để lưu ID biến thể theo tên màu (hỗ trợ gán ảnh)
        $keepIds = [];

        if (isset($_POST['variant_ram'])) {
            // Lấy danh sách biến thể hiện có để so sánh
            $existingVariants = db()->fetchAll("SELECT * FROM bienthe_sanpham WHERE ma_sanpham = ?", [$productId]);
            $existingKeys = [];
            foreach ($existingVariants as $ev) {
                $key = $ev['ram_gb'] . '-' . $ev['rom_gb'] . '-' . mb_strtolower(trim($ev['mau_sac']));
                $existingKeys[$key] = $ev['ma_bienthe'];
            }

            foreach ($_POST['variant_ram'] as $i => $ram) {
                $rom = (int)$_POST['variant_rom'][$i];
                $mau = sanitize($_POST['variant_mau'][$i]);
                $gia_v = (float)str_replace('.', '', $_POST['variant_gia'][$i]);
                $ton = (int)$_POST['variant_tonkho'][$i];
                $sku = strtolower($slug . '-' . $ram . 'gb-' . $rom . 'gb-' . generateSlug($mau));
                
                $key = $ram . '-' . $rom . '-' . mb_strtolower(trim($mau));
                $lowerMau = mb_strtolower(trim($mau));
                
                if (isset($existingKeys[$key])) {
                    // Cập nhật biến thể đã tồn tại
                    $vId = $existingKeys[$key];
                    db()->execute("UPDATE bienthe_sanpham SET gia=?, ton_kho=?, ma_sku=? WHERE ma_bienthe=?", 
                                 [$gia_v, $ton, $sku, $vId]);
                    $keepIds[] = $vId;
                    $savedVariantIdsByColor[$lowerMau] = $vId;
                } else {
                    // Thêm biến thể mới
                    $newId = db()->insert("INSERT INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, gia, ton_kho) 
                                 VALUES (?,?,?,?,?,?,?)",
                        [$productId, $sku, (int)$ram, (int)$rom, $mau, $gia_v, $ton]);
                    $keepIds[] = $newId;
                    $savedVariantIdsByColor[$lowerMau] = $newId;
                }
            }
        }

        // Xóa những biến thể không còn trong danh sách mới
        if ($productId > 0) {
            $placeholders = empty($keepIds) ? '0' : implode(',', array_fill(0, count($keepIds), '?'));
            // Kiểm tra xem có biến thể nào có trong đơn hàng không trước khi xóa
            $toDelete = db()->fetchAll("SELECT ma_bienthe FROM bienthe_sanpham WHERE ma_sanpham = ? AND ma_bienthe NOT IN ($placeholders)", array_merge([$productId], $keepIds));
            
            foreach ($toDelete as $td) {
                $hasOrders = db()->fetchColumn("SELECT COUNT(*) FROM chitiet_donhang WHERE ma_bienthe = ?", [$td['ma_bienthe']]);
                if ($hasOrders > 0) {
                    // Nếu có đơn hàng, chỉ ẩn
                    db()->execute("UPDATE bienthe_sanpham SET is_active = 0 WHERE ma_bienthe = ?", [$td['ma_bienthe']]);
                } else {
                    // Nếu không, xóa cứng
                    // Bước 1: Xóa khỏi giỏ hàng của tất cả người dùng trước (tránh lỗi khóa ngoại)
                    db()->execute("DELETE FROM chitiet_giohang WHERE ma_bienthe = ?", [$td['ma_bienthe']]);
                    // Bước 2: Xóa ảnh đã liên kết với biến thể này
                    db()->execute("UPDATE hinhanh_sanpham SET ma_bienthe = NULL WHERE ma_bienthe = ?", [$td['ma_bienthe']]);
                    // Bước 3: Xóa biến thể
                    db()->execute("DELETE FROM bienthe_sanpham WHERE ma_bienthe = ?", [$td['ma_bienthe']]);
                }
            }
        }

        // --- Xử lý hình ảnh ---
        // Gán biến thể cho ảnh cũ (hỗ trợ map theo tên màu)
        if (!empty($_POST['image_variant'])) {
            foreach ($_POST['image_variant'] as $imgId => $selectedColor) {
                $bientheId = null;
                $lowerColor = mb_strtolower(trim($selectedColor));
                if ($selectedColor !== '') {
                    if (isset($savedVariantIdsByColor[$lowerColor])) {
                        $bientheId = $savedVariantIdsByColor[$lowerColor];
                    } elseif (is_numeric($selectedColor)) {
                        $bientheId = (int)$selectedColor;
                        if (!db()->fetchOne("SELECT ma_bienthe FROM bienthe_sanpham WHERE ma_bienthe = ?", [$bientheId])) {
                            $bientheId = null;
                        }
                    }
                }
                db()->execute("UPDATE hinhanh_sanpham SET ma_bienthe = ? WHERE ma_anh = ? AND ma_sanpham = ?",
                    [$bientheId, (int)$imgId, $productId]);
            }
        }
        
        $deletedNewImages = isset($_POST['deleted_new_images']) ? $_POST['deleted_new_images'] : [];

        // Upload ảnh mới với gán biến thể
        if (!empty($_FILES['hinh_anh']['name'][0])) {
            $newMainIndex = isset($_POST['new_main_image_id']) ? (int)$_POST['new_main_image_id'] : -1;
            foreach ($_FILES['hinh_anh']['tmp_name'] as $i => $tmp) {
                // Bỏ qua nếu ảnh này đã bị xóa ở frontend
                if (in_array((string)$i, $deletedNewImages)) continue;

                if (!$tmp || $_FILES['hinh_anh']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($_FILES['hinh_anh']['name'][$i], PATHINFO_EXTENSION));
                $allowedExts = ['jpg','jpeg','png','webp','gif'];
                if (!in_array($ext, $allowedExts)) continue;
                $fname = 'sp_' . $productId . '_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($tmp, UPLOAD_DIR . $fname)) {
                    // Nếu là sản phẩm mới và đây là ảnh đầu tiên, HOẶC nếu admin chọn ảnh này làm chính
                    $isMain = ( ($id == 0 && $i === 0 && $newMainIndex === -1) || ($i === $newMainIndex) ) ? 1 : 0;
                    
                    // Lấy mã biến thể gán cho ảnh mới này
                    $bientheId = null;
                    $selectedColor = isset($_POST['new_image_variant'][$i]) ? trim($_POST['new_image_variant'][$i]) : '';
                    if ($selectedColor !== '') {
                        $lowerColor = mb_strtolower($selectedColor);
                        if (isset($savedVariantIdsByColor[$lowerColor])) {
                            $bientheId = $savedVariantIdsByColor[$lowerColor];
                        } elseif (is_numeric($selectedColor)) {
                            $bientheId = (int)$selectedColor;
                            if (!db()->fetchOne("SELECT ma_bienthe FROM bienthe_sanpham WHERE ma_bienthe = ?", [$bientheId])) {
                                $bientheId = null;
                            }
                        }
                    }

                    // Nếu ảnh mới này được chọn làm chính, tắt ảnh chính cũ
                    if ($isMain) {
                        db()->execute("UPDATE hinhanh_sanpham SET la_anh_chinh = 0 WHERE ma_sanpham = ?", [$productId]);
                    }

                    db()->insert("INSERT INTO hinhanh_sanpham (ma_sanpham, image_url, la_anh_chinh, ma_bienthe) VALUES (?,?,?,?)",
                        [$productId, $fname, $isMain, $bientheId]);
                }
            }
        }
        
        if (isset($_POST['main_image_id'])) {
            db()->execute("UPDATE hinhanh_sanpham SET la_anh_chinh = 0 WHERE ma_sanpham = ?", [$productId]);
            db()->execute("UPDATE hinhanh_sanpham SET la_anh_chinh = 1 WHERE ma_anh = ?", [(int)$_POST['main_image_id']]);
        }

        db()->commit();
        setFlash('success', ($id > 0 ? 'Cập nhật' : 'Thêm') . ' sản phẩm thành công!');
        redirect(BASE_URL . '/admin/products.php');
    } catch (Exception $e) {
        db()->rollback();
        setFlash('error', 'Lỗi: ' . $e->getMessage());
    }

    } // end if (!empty($ma_danhmucs))
}

$categories = db()->fetchAll("SELECT * FROM danhmuc WHERE is_active = 1 ORDER BY thu_tu");
$brands     = db()->fetchAll("SELECT * FROM thuonghieu WHERE is_active = 1 ORDER BY ten_thuonghieu");

require_once __DIR__ . '/includes/header.php';
?>
<style>
    .btn-main-tg {
        width: 100%;
        padding: 6px;
        border: 1px solid var(--purple);
        background: transparent;
        color: var(--purple);
        font-size: 9px;
        font-weight: 800;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-main-tg.active {
        background: var(--purple);
        color: white;
    }
    .gallery-item-card.is-main {
        border: 2px solid var(--purple);
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.3);
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id > 0 ? '✨ CHỈNH SỬA SẢN PHẨM' : '🚀 THÊM SẢN PHẨM MỚI' ?></h1>
        <p class="page-desc">Thiết lập thông tin chi tiết, hình ảnh và các biến thể cấu hình cho sản phẩm</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="products.php" class="btn btn-outline">QUAY LẠI</a>
        <button type="submit" form="productForm" class="btn btn-primary">LƯU SẢN PHẨM</button>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="productForm" class="animate-fade-up">
    <!-- IMAGES SECTION -->
    <div class="section-card" style="margin-bottom: 30px; border-top: 4px solid var(--purple);">
        <div class="section-card-header">
            <h3>📸 QUẢN LÝ HÌNH ẢNH</h3>
        </div>
        <div class="section-card-body">
            <div style="display: grid; grid-template-columns: 320px 1fr; gap: 40px; align-items: start;">
                <div>
                    <label class="form-label">ẢNH ĐẠI DIỆN CHÍNH</label>
                    <div class="main-upload-area" id="mainImagePreviewContainer">
                        <?php 
                        $mainImg = null;
                        foreach($productImages as $img) if($img['la_anh_chinh']) $mainImg = $img['image_url'];
                        ?>
                        <img src="<?= $mainImg ? BASE_URL . '/uploads/products/' . $mainImg : 'https://placehold.co/600x600/f1f5f9/8b5cf6?text=CHỌN+ẢNH+CHÍNH' ?>" id="mainImagePreview">
                    </div>
                    <p style="text-align: center; color: var(--txt3); font-size: 11px; margin-top: 10px; font-weight: 600;">
                        (Hệ thống sẽ dùng ảnh này làm ảnh chính)
                    </p>
                </div>
                <div>
                    <label class="form-label">THƯ VIỆN HÌNH ẢNH & BIẾN THỂ MÀU</label>
                    <div class="gallery-grid" id="galleryContainer">
                        <?php 
                        $colorVariants = [];
                        foreach ($variants as $v) $colorVariants[$v['mau_sac']] = $v['ma_bienthe'];
                        ?>
                        <?php foreach($productImages as $img): ?>
                        <div class="gallery-item-card <?= $img['la_anh_chinh'] ? 'is-main' : '' ?>" id="img_card_<?= $img['ma_anh'] ?>">
                            <div class="gallery-thumb">
                                <img src="<?= BASE_URL ?>/uploads/products/<?= $img['image_url'] ?>" onclick="setMainImage(<?= $img['ma_anh'] ?>, '<?= BASE_URL ?>/uploads/products/<?= $img['image_url'] ?>')">
                                <div class="thumb-actions">
                                    <button type="button" class="btn-icon btn-sm" onclick="deleteImage(<?= $img['ma_anh'] ?>, this.closest('.gallery-item-card'))" style="color:red; width:30px; height:30px;">✕</button>
                                </div>
                            </div>
                            <div style="padding: 10px; background: var(--card2);">
                                <select name="image_variant[<?= $img['ma_anh'] ?>]" class="form-control" style="font-size: 10px; padding: 5px; height: auto; margin-bottom: 8px;">
                                    <option value="">🌈 Chung</option>
                                    <?php foreach ($colorVariants as $color => $vid): ?>
                                    <option value="<?= $vid ?>" <?= ($img['ma_bienthe'] == $vid) ? 'selected' : '' ?>>
                                        🎨 <?= sanitize($color) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <input type="radio" name="main_image_id" value="<?= $img['ma_anh'] ?>" <?= $img['la_anh_chinh'] ? 'checked' : '' ?> style="display:none;" id="main_radio_<?= $img['ma_anh'] ?>" onchange="updateMainPreview(this, '<?= BASE_URL ?>/uploads/products/<?= $img['image_url'] ?>')">
                                
                                <button type="button" class="btn-main-tg <?= $img['la_anh_chinh'] ? 'active' : '' ?>" onclick="document.getElementById('main_radio_<?= $img['ma_anh'] ?>').click()">
                                    <?= $img['la_anh_chinh'] ? '⭐ ẢNH CHÍNH' : 'MẶC ĐỊNH' ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="main-upload-area" onclick="document.getElementById('galleryInput').click()" style="cursor: pointer; height: 100%;">
                            <div style="text-align: center;">
                                <div style="font-size: 30px; color: var(--purple);">➕</div>
                                <div style="font-size: 10px; font-weight: 800; color: var(--txt3); margin-top: 5px;">TẢI LÊN</div>
                            </div>
                            <input type="file" name="hinh_anh[]" id="galleryInput" style="display:none" multiple accept="image/*" onchange="previewNewImages(this)">
                        </div>
                    </div>
                    <div id="newImagesPreviewContainer" style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- LEFT: INFO & SPECS -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <div class="section-card">
                <div class="section-card-header">
                    <h3>📝 THÔNG TIN CƠ BẢN</h3>
                </div>
                <div class="section-card-body">
                    <div class="form-group">
                        <label class="form-label">TÊN SẢN PHẨM *</label>
                        <input type="text" name="ten_sanpham" class="form-control" required value="<?= sanitize($product['ten_sanpham'] ?? '') ?>" placeholder="VD: iPhone 16 Pro Max 256GB...">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">MÃ SKU / CODE</label>
                            <input type="text" name="ma_sanpham_code" class="form-control" value="<?= sanitize($product['ma_sanpham_code'] ?? '') ?>" placeholder="VD: IP16PM-256...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">THƯƠNG HIỆU</label>
                            <select name="ma_thuonghieu" class="form-control" required>
                                <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['ma_thuonghieu'] ?>" <?= ($product['ma_thuonghieu'] ?? 0) == $b['ma_thuonghieu'] ? 'selected' : '' ?>><?= sanitize($b['ten_thuonghieu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">DANH MỤC SẢN PHẨM (CHỌN NHIỀU)</label>
                        <div class="category-grid-select">
                            <?php foreach ($categories as $c): 
                                $selected = in_array($c['ma_danhmuc'], $productCategoryIds) ? 'checked' : '';
                            ?>
                            <label class="cat-checkbox-item <?= $selected ? 'is-selected' : '' ?>">
                                <input type="checkbox" name="ma_danhmuc[]" value="<?= $c['ma_danhmuc'] ?>" <?= $selected ?> onchange="this.parentElement.classList.toggle('is-selected', this.checked)">
                                <span class="cat-name"><?= sanitize($c['ten_danhmuc']) ?></span>
                                <span class="cat-check">✓</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header">
                    <h3>⚙️ THÔNG SỐ KỸ THUẬT</h3>
                </div>
                <div class="section-card-body">
                    <div class="specs-grid">
                        <div class="spec-item">
                            <div class="spec-icon">📱</div>
                            <div style="flex:1">
                                <label class="form-label" style="font-size: 9px; margin-bottom: 3px;">Hệ điều hành</label>
                                <input type="text" name="he_dieu_hanh" class="form-control" style="border:none; padding:0; height:auto;" value="<?= sanitize($product['he_dieu_hanh'] ?? '') ?>" placeholder="Android / iOS...">
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">🧠</div>
                            <div style="flex:1">
                                <label class="form-label" style="font-size: 9px; margin-bottom: 3px;">Chipset</label>
                                <input type="text" name="chip" class="form-control" style="border:none; padding:0; height:auto;" value="<?= sanitize($product['chip'] ?? '') ?>" placeholder="Apple A18 Pro...">
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">📐</div>
                            <div style="flex:1">
                                <label class="form-label" style="font-size: 9px; margin-bottom: 3px;">Màn hình</label>
                                <input type="text" name="man_hinh_size" class="form-control" style="border:none; padding:0; height:auto;" value="<?= sanitize($product['man_hinh_size'] ?? '') ?>" placeholder="6.9 inch, OLED...">
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">⚡</div>
                            <div style="flex:1">
                                <label class="form-label" style="font-size: 9px; margin-bottom: 3px;">Pin / Sạc</label>
                                <input type="text" name="pin_dung_luong" class="form-control" style="border:none; padding:0; height:auto;" value="<?= sanitize($product['pin_dung_luong'] ?? '') ?>" placeholder="4685 mAh, 45W...">
                            </div>
                        </div>
                    </div>
                    <div class="form-grid" style="margin-top: 20px;">
                         <div class="form-group">
                            <label class="form-label">CAMERA SAU</label>
                            <input type="text" name="camera_sau" class="form-control" value="<?= sanitize($product['camera_sau'] ?? '') ?>" placeholder="48MP + 12MP...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">CAMERA TRƯỚC</label>
                            <input type="text" name="camera_truoc" class="form-control" value="<?= sanitize($product['camera_truoc'] ?? '') ?>" placeholder="12MP, f/1.9...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: PRICING & DESC -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <div class="section-card price-card">
                <div class="section-card-body">
                    <div class="form-group">
                        <label class="form-label">GIÁ NIÊM YẾT (VND)</label>
                        <input type="text" name="gia_goc" class="form-control" value="<?= number_format($product['gia_goc'] ?? 0, 0, ',', '.') ?>" 
                               oninput="formatCurrency(this)" style="font-size: 24px; text-align: right;">
                    </div>
                    <div class="form-group" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;">
                        <label class="form-label">GIÁ KHUYẾN MÃI (VND)</label>
                        <input type="text" name="gia_khuyen_mai" class="form-control" value="<?= $product['gia_khuyen_mai'] ? number_format($product['gia_khuyen_mai'], 0, ',', '.') : '' ?>" 
                               oninput="formatCurrency(this)" style="font-size: 24px; text-align: right; color: #facc15;">
                        <p style="font-size: 10px; margin-top: 8px; font-weight: 700;">* Để trống nếu không khuyến mãi</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header">
                    <h3>🏷️ TRẠNG THÁI & HIỂN THỊ</h3>
                </div>
                <div class="section-card-body">
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 10px; background: var(--card2); border-radius: 12px;">
                            <span style="font-size: 13px; font-weight: 700;">BÁN CÔNG KHAI</span>
                            <input type="checkbox" name="is_active" <?= ($id == 0 || ($product['is_active'] ?? 0)) ? 'checked' : '' ?> style="width:20px; height:20px; accent-color: var(--purple);">
                        </label>
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 10px; background: var(--card2); border-radius: 12px;">
                            <span style="font-size: 13px; font-weight: 700;">🎯 NỔI BẬT</span>
                            <input type="checkbox" name="is_noi_bat" <?= ($product['is_noi_bat'] ?? 0) ? 'checked' : '' ?> style="width:20px; height:20px; accent-color: var(--purple);">
                        </label>
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 10px; background: var(--card2); border-radius: 12px;">
                            <span style="font-size: 13px; font-weight: 700;">🆕 HÀNG MỚI VỀ</span>
                            <input type="checkbox" name="is_hang_moi" <?= ($product['is_hang_moi'] ?? 0) ? 'checked' : '' ?> style="width:20px; height:20px; accent-color: var(--purple);">
                        </label>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header">
                    <h3>📝 MÔ TẢ NGẮN</h3>
                </div>
                <div class="section-card-body" style="padding: 10px;">
                    <textarea name="mo_ta_ngan" class="form-control" style="height: 120px; resize: none; border:none;" placeholder="Mô tả tóm tắt điểm đặc biệt..."><?= sanitize($product['mo_ta_ngan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- VARIANTS SECTION -->
    <div class="section-card" style="margin-top: 30px;">
        <div class="section-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>🎨 CÁC BIẾN THỂ CẤU HÌNH & MÀU SẮC</h3>
            <button type="button" class="btn btn-primary btn-sm" id="addVariant">➕ THÊM BIẾN THỂ</button>
        </div>
        <div class="admin-table-container">
            <table class="admin-table" id="variantTable">
                <thead>
                    <tr>
                        <th width="120">RAM (GB)</th>
                        <th width="120">ROM (GB)</th>
                        <th>MÀU SẮC</th>
                        <th>GIÁ BIẾN THỂ (VND)</th>
                        <th width="150">TỒN KHO</th>
                        <th width="80">XÓA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variants)): ?>
                    <tr>
                        <td><input type="number" name="variant_ram[]" class="form-control" style="padding:8px" value="8" required></td>
                        <td><input type="number" name="variant_rom[]" class="form-control" style="padding:8px" value="128" required></td>
                        <td><input type="text" name="variant_mau[]" class="form-control" style="padding:8px" value="Tiêu chuẩn" required></td>
                        <td><input type="text" name="variant_gia[]" class="form-control" style="padding:8px" value="0" oninput="formatCurrency(this)" required></td>
                        <td><input type="number" name="variant_tonkho[]" class="form-control" style="padding:8px" value="10" required></td>
                        <td style="text-align: center;">---</td>
                    </tr>
                    <?php else: foreach ($variants as $v): ?>
                    <tr>
                        <td><input type="number" name="variant_ram[]" class="form-control" style="padding:8px" value="<?= $v['ram_gb'] ?>" required></td>
                        <td><input type="number" name="variant_rom[]" class="form-control" style="padding:8px" value="<?= $v['rom_gb'] ?>" required></td>
                        <td><input type="text" name="variant_mau[]" class="form-control" style="padding:8px" value="<?= sanitize($v['mau_sac']) ?>" required></td>
                        <td><input type="text" name="variant_gia[]" class="form-control" style="padding:8px" value="<?= number_format($v['gia'], 0, ',', '.') ?>" oninput="formatCurrency(this)" required></td>
                        <td><input type="number" name="variant_tonkho[]" class="form-control" style="padding:8px" value="<?= $v['ton_kho'] ?>" required></td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-icon btn-sm" style="color:red; border-color:#fee2e2;" onclick="this.closest('tr').remove()">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- LONG DESCRIPTION -->
    <div class="section-card" style="margin-top: 30px;">
        <div class="section-card-header">
            <h3>📑 MÔ TẢ CHI TIẾT SẢN PHẨM</h3>
        </div>
        <div class="section-card-body" style="padding: 0;">
            <textarea name="mo_ta_day_du" id="mo_ta_day_du" class="form-control" style="height: 400px; border-radius: 0; border: none;"><?= $product['mo_ta_day_du'] ?? '' ?></textarea>
        </div>
    </div>

    <div style="margin: 40px 0 80px; display: flex; justify-content: center; gap: 20px;">
        <button type="button" class="btn btn-outline" style="min-width: 200px;" onclick="location.href='products.php'">HUỶ BỎ</button>
        <button type="submit" class="btn btn-primary" style="min-width: 250px;">LƯU TẤT CẢ THAY ĐỔI</button>
    </div>
</form>

<!-- CKEditor if available -->
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
    // Đồng bộ danh sách biến thể màu sắc sang JS
    const colorVariants = <?= json_encode($colorVariants) ?>;

    if (document.getElementById('mo_ta_day_du')) {
        CKEDITOR.replace('mo_ta_day_du', {
            height: 400,
            removeButtons: 'PasteFromWord'
        });
    }

    function formatCurrency(input) {
        let val = input.value.replace(/\D/g, "");
        if (val) {
            val = parseInt(val).toLocaleString('vi-VN');
            input.value = val;
        }
    }

    function updateMainPreview(radio, url) {
        document.getElementById('mainImagePreview').src = url;
        document.querySelectorAll('.gallery-item-card').forEach(card => {
            card.classList.remove('is-main');
            const btn = card.querySelector('.btn-main-tg');
            if (btn) {
                btn.classList.remove('active');
                btn.innerHTML = 'MẶC ĐỊNH';
            }
        });
        const card = radio.closest('.gallery-item-card');
        card.classList.add('is-main');
        const activeBtn = card.querySelector('.btn-main-tg');
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.innerHTML = '⭐ ẢNH CHÍNH';
        }
    }

    function setMainImage(imgId, url) {
        const radio = document.querySelector(`input[name="main_image_id"][value="${imgId}"]`);
        if(radio) {
            radio.checked = true;
            updateMainPreview(radio, url);
        }
    }

    function removeNewImage(btn, index) {
        const card = btn.closest('.gallery-item-card');
        card.remove();
        // Thêm hidden input để PHP không upload ảnh này
        const form = document.getElementById('productForm');
        const hiddenInfo = document.createElement('input');
        hiddenInfo.type = 'hidden';
        hiddenInfo.name = 'deleted_new_images[]';
        hiddenInfo.value = index;
        form.appendChild(hiddenInfo);
    }

    let batchCount = 0;
    function previewNewImages(input) {
        const container = document.getElementById('newImagesPreviewContainer');
        
        // Lân danh sách màu từ các ô nhập "Màu sắc" hiện tại
        let dynamicColors = new Set();
        document.querySelectorAll('input[name="variant_mau[]"]').forEach(el => {
            if(el.value.trim() !== '') dynamicColors.add(el.value.trim());
        });

        // Tạo danh sách option cho select box
        let optionsHtml = '<option value="">🌈 Chung</option>';
        dynamicColors.forEach(color => {
            optionsHtml += `<option value="${color}">🎨 ${color}</option>`;
        });

        if (input.files) {
            Array.from(input.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'gallery-item-card animate-fade-up';
                    div.style.width = '140px';
                    
                    div.innerHTML = `
                        <div class="gallery-thumb">
                            <img src="${e.target.result}">
                            <div class="thumb-actions">
                                <button type="button" class="btn-icon btn-sm" onclick="removeNewImage(this, ${index})" style="color:red; width:30px; height:30px;">✕</button>
                            </div>
                        </div>
                        <div style="padding: 10px; background: var(--purple-light);">
                            <div style="font-size: 10px; font-weight: 800; color: var(--purple); text-align: center; margin-bottom: 8px;">ẢNH MỚI CHỜ TẢI</div>
                            
                            <select name="new_image_variant[${index}]" class="form-control" style="font-size: 10px; padding: 5px; height: auto; margin-bottom: 8px;">
                                ${optionsHtml}
                            </select>

                            <input type="radio" name="new_main_image_id" value="${index}" style="display:none;" id="new_main_${batchCount}_${index}" onchange="updateMainPreview(this, '${e.target.result}')">
                            <button type="button" class="btn-main-tg" onclick="document.getElementById('new_main_${batchCount}_${index}').click()">
                                MẶC ĐỊNH
                            </button>
                        </div>
                    `;
                    container.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
            batchCount++;
        }
    }

    document.getElementById('addVariant').addEventListener('click', function() {
        const tbody = document.querySelector('#variantTable tbody');
        const rows = tbody.querySelectorAll('tr');
        const firstRow = rows[0];
        if(!firstRow) {
            const html = `
                <tr>
                    <td><input type="number" name="variant_ram[]" class="form-control" style="padding:8px" value="8" required></td>
                    <td><input type="number" name="variant_rom[]" class="form-control" style="padding:8px" value="128" required></td>
                    <td><input type="text" name="variant_mau[]" class="form-control" style="padding:8px" value="Tiêu chuẩn" required></td>
                    <td><input type="text" name="variant_gia[]" class="form-control" style="padding:8px" value="0" oninput="formatCurrency(this)" required></td>
                    <td><input type="number" name="variant_tonkho[]" class="form-control" style="padding:8px" value="10" required></td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-icon btn-sm" style="color:red; border-color:#fee2e2;" onclick="this.closest('tr').remove()">🗑️</button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', html);
            return;
        }

        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => {
            if (input.type === 'number') input.value = '0';
            else input.value = '';
        });
        const lastTd = newRow.lastElementChild;
        lastTd.innerHTML = '<button type="button" class="btn-icon btn-sm" style="color:red; border-color:#fee2e2;" onclick="this.closest(\'tr\').remove()">🗑️</button>';
        tbody.appendChild(newRow);
    });

    async function deleteImage(imgId, el) {
        if (!confirm('⚠️ Xác nhận xóa vĩnh viễn ảnh này?')) return;
        try {
            const data = new FormData();
            data.append('img_id', imgId);
            const res = await fetch('<?= BASE_URL ?>/admin/api/delete_image.php', { method: 'POST', body: data });
            const json = await res.json();
            if (json.success) {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            } else {
                alert('Xóa thất bại: ' + json.message);
            }
        } catch(e) { alert('Lỗi hệ thống khi xóa ảnh!'); }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
