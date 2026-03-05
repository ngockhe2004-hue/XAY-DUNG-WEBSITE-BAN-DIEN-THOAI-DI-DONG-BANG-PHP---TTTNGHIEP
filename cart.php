<?php
$pageTitle = 'Giỏ Hàng';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = $_SESSION['user_site']['id'];
$gio    = db()->fetchOne("SELECT ma_gio FROM giohang WHERE ma_user = ?", [$userId]);
$items  = [];
$total  = 0;

if ($gio) {
    $items = db()->fetchAll("
        SELECT ctgh.*, b.mau_sac, b.ram_gb, b.rom_gb, b.gia, b.gia_goc, b.ton_kho, b.ma_sku,
               sp.ten_sanpham, sp.ma_sanpham,
               (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_bienthe = b.ma_bienthe AND h.la_anh_chinh = 1 LIMIT 1) as anh_bienthe,
               (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh_sp
        FROM chitiet_giohang ctgh
        JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
        JOIN sanpham sp ON b.ma_sanpham = sp.ma_sanpham
        WHERE ctgh.ma_gio = ?
        ORDER BY ctgh.ngay_them DESC
    ", [$gio['ma_gio']]);
    $total = array_sum(array_map(fn($i) => $i['gia'] * $i['so_luong'], $items));
}
?>

<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
        <div>
            <h1 class="page-title">🛒 Giỏ Hàng</h1>
            <p style="color:var(--text-secondary);"><?= count($items) ?> sản phẩm</p>
        </div>
        <?php if ($items): ?>
        <button onclick="clearCart()" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);">🗑️ Xóa tất cả</button>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
    <div class="empty-state">
        <div class="empty-icon">🛒</div>
        <h3 class="empty-title">Giỏ hàng trống</h3>
        <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary" style="margin-top:20px;">Tiếp tục mua sắm →</a>
    </div>
    <?php else: ?>

    <div class="cart-layout">
        <!-- Cart Items -->
        <div id="cartItems">
            <?php foreach ($items as $item): 
                $imgSrc = ($item['anh_bienthe'] ?: $item['anh_sp']) 
                    ? BASE_URL . '/uploads/products/' . basename($item['anh_bienthe'] ?: $item['anh_sp'])
                    : 'https://placehold.co/200x200/1a1a26/6c63ff?text=SP';
                $outOfStock = $item['ton_kho'] <= 0;
                $overStock  = $item['so_luong'] > $item['ton_kho'];
            ?>
            <div class="cart-item" id="cartItem-<?= $item['ma_ctgh'] ?>" <?= $outOfStock ? 'style="opacity:0.6"' : '' ?>>
                <div class="cart-item-img">
                    <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $item['ma_sanpham'] ?>">
                        <img src="<?= $imgSrc ?>" alt="<?= sanitize($item['ten_sanpham']) ?>">
                    </a>
                </div>
                <div class="cart-item-info">
                    <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $item['ma_sanpham'] ?>" class="cart-item-name"><?= sanitize($item['ten_sanpham']) ?></a>
                    <div class="cart-item-variant"><?= $item['ram_gb'] ?>GB RAM · <?= $item['rom_gb'] ?>GB ROM · <?= sanitize($item['mau_sac']) ?></div>
                    <?php if ($outOfStock): ?>
                    <div style="color:var(--danger);font-size:13px;font-weight:600;">⚠️ Sản phẩm này đã hết hàng</div>
                    <?php elseif ($overStock): ?>
                    <div style="color:var(--warning);font-size:13px;">⚠️ Chỉ còn <?= $item['ton_kho'] ?> sản phẩm</div>
                    <?php endif; ?>
                    <div class="cart-item-controls">
                        <div class="qty-selector" style="margin:0;">
                            <button class="qty-btn" onclick="updateQty(<?= $item['ma_ctgh'] ?>, -1, <?= $item['ton_kho'] ?>)">−</button>
                            <input type="number" class="qty-input" id="qty-<?= $item['ma_ctgh'] ?>" 
                                   value="<?= $item['so_luong'] ?>" min="1" max="<?= $item['ton_kho'] ?>" readonly
                                   style="width:52px;height:36px;">
                            <button class="qty-btn" onclick="updateQty(<?= $item['ma_ctgh'] ?>, 1, <?= $item['ton_kho'] ?>)">+</button>
                        </div>
                        <button class="cart-remove" onclick="removeItem(<?= $item['ma_ctgh'] ?>)">🗑️ Xóa</button>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div class="cart-item-price" id="price-<?= $item['ma_ctgh'] ?>"><?= formatPrice($item['gia'] * $item['so_luong']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);"><?= formatPrice($item['gia']) ?>/sp</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <h3>Tóm Tắt Đơn Hàng</h3>
            
            <!-- Coupon -->
            <div class="coupon-input">
                <input type="text" id="couponCode" placeholder="Nhập mã giảm giá..." class="form-control">
                <button class="btn btn-outline btn-sm" onclick="applyCoupon()">Áp dụng</button>
            </div>
            <div id="couponMsg" style="font-size:13px;margin-bottom:12px;"></div>
            <input type="hidden" id="couponId" value="">
            <input type="hidden" id="discountAmt" value="0">

            <div class="summary-row">
                <span class="label">Tạm tính (<?= count($items) ?> SP)</span>
                <span id="subtotalDisplay"><?= formatPrice($total) ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Phí vận chuyển</span>
                <span><?= $total >= 500000 ? '<span style="color:var(--success)">Miễn phí</span>' : formatPrice(30000) ?></span>
            </div>
            <div class="summary-row" id="discountRow" style="display:none;">
                <span class="label">Giảm giá</span>
                <span style="color:var(--danger);" id="discountDisplay">-0 ₫</span>
            </div>
            <div class="summary-row summary-total">
                <span>Tổng cộng</span>
                <span style="background:var(--gradient-main);-webkit-background-clip:text;-webkit-text-fill-color:transparent;" id="totalDisplay">
                    <?= formatPrice($total >= 500000 ? $total : $total + 30000) ?>
                </span>
            </div>

            <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top:20px;" id="checkoutBtn">
                Tiến Hành Đặt Hàng →
            </a>
            <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline btn-block" style="margin-top:10px;">
                ← Tiếp tục mua sắm
            </a>
            
            <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;font-size:13px;color:var(--text-muted);">
                <span>🔒 Bảo mật SSL</span>
                <span>•</span>
                <span>🏆 Hàng chính hãng</span>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const SHIP_FREE = 500000;
const SHIP_FEE  = 30000;

let subtotal = <?= $total ?>;
let discount = 0;

function calcTotal() {
    const ship = (subtotal - discount) >= SHIP_FREE ? 0 : SHIP_FEE;
    return Math.max(0, subtotal - discount) + ship;
}

function refreshDisplay() {
    document.getElementById('subtotalDisplay').textContent = fmtPrice(subtotal);
    document.getElementById('totalDisplay').textContent = fmtPrice(calcTotal());
    const dr = document.getElementById('discountRow');
    if (discount > 0) {
        dr.style.display = '';
        document.getElementById('discountDisplay').textContent = '-' + fmtPrice(discount);
    } else { dr.style.display = 'none'; }
}

function fmtPrice(n) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' ₫';
}

async function updateQty(ctghId, delta, maxStock) {
    const input = document.getElementById('qty-' + ctghId);
    const oldQty = parseInt(input.value);
    const newQty = Math.max(1, Math.min(maxStock, oldQty + delta));
    if (newQty === oldQty) return;
    input.value = newQty;

    const res = await fetch(BASE_URL + '/api/cart.php', {
        method: 'PUT',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ma_ctgh: ctghId, so_luong: newQty})
    });
    const data = await res.json();
    if (data.success) {
        // Update price display
        const priceEl = document.getElementById('price-' + ctghId);
        const unitPrice = data.unit_price;
        if (priceEl) priceEl.textContent = fmtPrice(unitPrice * newQty);
        subtotal = data.cart_total;
        document.getElementById('cartCount').textContent = data.cart_count;
        refreshDisplay();
    } else {
        input.value = oldQty;
        alert(data.message || 'Không thể cập nhật số lượng');
    }
}

async function removeItem(ctghId) {
    if (!confirm('Xóa sản phẩm này khỏi giỏ hàng?')) return;
    const res = await fetch(BASE_URL + '/api/cart.php?ma_ctgh=' + ctghId, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
        document.getElementById('cartItem-' + ctghId)?.remove();
        subtotal = data.cart_total;
        document.getElementById('cartCount').textContent = data.cart_count;
        refreshDisplay();
        if (data.cart_count === 0) location.reload();
    }
}

async function clearCart() {
    if (!confirm('Xóa toàn bộ giỏ hàng?')) return;
    const res = await fetch(BASE_URL + '/api/cart.php?clear=1', { method: 'DELETE' });
    const data = await res.json();
    if (data.success) location.reload();
}

async function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    if (!code) return;
    const res  = await fetch(BASE_URL + '/api/coupon.php?code=' + encodeURIComponent(code) + '&total=' + subtotal);
    const data = await res.json();
    const msg  = document.getElementById('couponMsg');
    if (data.success) {
        discount = data.discount;
        document.getElementById('discountAmt').value = discount;
        document.getElementById('couponId').value = data.coupon_id;
        msg.innerHTML = '<span style="color:var(--success)">✅ ' + data.message + '</span>';
        refreshDisplay();
    } else {
        msg.innerHTML = '<span style="color:var(--danger)">❌ ' + data.message + '</span>';
        discount = 0; refreshDisplay();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
