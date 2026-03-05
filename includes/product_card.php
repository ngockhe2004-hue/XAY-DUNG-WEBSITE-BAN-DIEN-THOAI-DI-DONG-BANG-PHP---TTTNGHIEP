<?php
// Product Card Component (included in loops)
// Variable $p must be set (product row from v_sanpham_tongquan + joins)
$wished = in_array($p['ma_sanpham'], $wishedProducts ?? []);
$imgSrc = $p['anh_chinh'] ? BASE_URL . '/uploads/products/' . basename($p['anh_chinh']) : 'https://placehold.co/400x400/1a1a26/6c63ff?text=' . urlencode($p['ten_sanpham']);

// Logic xác định giá hiển thị
// Ưu tiên giá từ bảng sanpham (nhập từ admin), nếu không có mới lấy giá thấp nhất của biến thể
$currentPrice = $p['gia_khuyen_mai'] ?: ($p['gia_thap'] ?? $p['gia_min'] ?? 0);
$originalPrice = $p['gia_goc'] ?: ($p['gia_goc_thap_nhat'] ?? $p['gia_goc_min'] ?? 0);
$hasDiscount = $originalPrice > 0 && $currentPrice > 0 && $originalPrice > $currentPrice;
$discountPct = $hasDiscount ? round((1 - $currentPrice / $originalPrice) * 100) : 0;
?>
<div class="product-card-v2" data-id="<?= $p['ma_sanpham'] ?>">
    <div class="card-image-wrap">
        <?php if ($p['is_hang_moi']): ?><span class="badge-v2 b-new">Mới</span><?php endif; ?>
        <?php if ($hasDiscount): ?><span class="badge-v2 b-discount"> Giảm <?= $discountPct ?>%</span><?php endif; ?>
        <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $p['ma_sanpham'] ?>">
            <img src="<?= $imgSrc ?>" alt="<?= sanitize($p['ten_sanpham']) ?>" loading="lazy">
        </a>
    </div>
    
    <div class="card-content-v2">
        <h3 class="product-title-v2">
            <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $p['ma_sanpham'] ?>"><?= sanitize($p['ten_sanpham']) ?></a>
        </h3>

        <div class="tech-info-v2">
            <span><?= $p['man_hinh_size'] ?: '6.7' ?>"</span>
            <span><?= $p['man_hinh_dophangiai'] ?: 'OLED' ?></span>
        </div>
        
        <div class="price-box-v2">
            <span class="p-current"><?= $currentPrice > 0 ? formatPrice($currentPrice) : 'Liên hệ' ?></span>
            <?php if ($hasDiscount): ?>
                <span class="p-old"><?= formatPrice($originalPrice) ?></span>
            <?php endif; ?>
        </div>

        <div class="card-footer-v2">
            <div class="rating-v2">⭐ <?= number_format($p['diem_danh_gia'], 1) ?></div>
            <button class="btn-quick-add" onclick="quickAddToCart(<?= $p['ma_sanpham'] ?>, this)">+</button>
        </div>
    </div>
</div>
