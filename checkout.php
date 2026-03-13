<?php
// ============================================================
// TOÀN BỘ LOGIC PHP Ở ĐÂY - TRƯỚC KHI XUẤT HTML
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php?redirect=' . urlencode(BASE_URL . '/checkout.php'));
}
if (!isCustomer()) {
    redirect(BASE_URL . '/cart.php');
}

$user = getCurrentUser();
if ($user && $user['trang_thai'] === 'pending') {
    setFlash('warning', '⚠️ Tài khoản của bạn đang ở trạng thái **Chờ duyệt**. Bạn có thể xem sản phẩm nhưng chưa thể thực hiện đặt hàng vào lúc này. Vui lòng liên hệ quản trị viên.');
    redirect(BASE_URL . '/cart.php');
}

$userId = $_SESSION['user_site']['id'];
$gioId  = db()->fetchOne("SELECT ma_gio FROM giohang WHERE ma_user = ?", [$userId]);
if (!$gioId) { setFlash('error','Giỏ hàng trống!'); redirect(BASE_URL . '/cart.php'); }

$gioId = $gioId['ma_gio'];
$items  = db()->fetchAll("
    SELECT ctgh.*, b.mau_sac, b.ram_gb, b.rom_gb, b.gia, b.ton_kho, sp.ten_sanpham,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh
    FROM chitiet_giohang ctgh
    JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
    JOIN sanpham sp ON b.ma_sanpham = sp.ma_sanpham
    WHERE ctgh.ma_gio = ?
", [$gioId]);

if (empty($items)) { setFlash('error','Giỏ hàng trống!'); redirect(BASE_URL . '/cart.php'); }

$subtotal = array_sum(array_map(fn($i) => $i['gia'] * $i['so_luong'], $items));
$ship = $subtotal >= 500000 ? 0 : 30000;
$total = $subtotal + $ship;

// Xử lý POST - đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenNguoiNhan = sanitize($_POST['ten_nguoi_nhan'] ?? '');
    $sdt          = sanitize($_POST['sdt'] ?? '');
    $tinhThanh    = sanitize($_POST['tinh_thanh'] ?? '');
    $quanHuyen    = sanitize($_POST['quan_huyen'] ?? '');
    $phuongXa     = sanitize($_POST['phuong_xa'] ?? '');
    $diaChiCuThe  = sanitize($_POST['dia_chi_cu_the'] ?? '');
    $ghiChu       = sanitize($_POST['ghi_chu'] ?? '');
    $phuongThucTT = sanitize($_POST['phuong_thuc_tt'] ?? 'cod');
    $maKmId       = (int)($_POST['ma_km'] ?? 0) ?: null;
    $soTienGiam   = 0; // Will calculate on server for security

    if (!$tenNguoiNhan || !$sdt || !$tinhThanh || !$diaChiCuThe) {
        setFlash('error','Vui lòng điền đầy đủ thông tin giao hàng');
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Lỗi xác thực bảo mật (CSRF). Vui lòng thử lại.');
    } else {
        // Re-check tồn kho
        $lack = false;
        foreach ($items as $item) {
            if ($item['so_luong'] > $item['ton_kho']) { $lack = true; break; }
        }
        if ($lack) {
            setFlash('error','Có sản phẩm trong giỏ vượt quá tồn kho. Vui lòng cập nhật giỏ hàng.');
            redirect(BASE_URL . '/cart.php');
        }

        // --- SECURITY: RE-VALIDATE COUPON ON SERVER ---
        if ($maKmId) {
            $coupon = db()->fetchOne("SELECT * FROM ma_khuyenmai WHERE ma_km = ? AND is_active = 1 AND NOW() BETWEEN ngay_bat_dau AND ngay_ket_thuc", [$maKmId]);
            if ($coupon && $subtotal >= $coupon['don_toi_thieu']) {
                if ($coupon['kieu_giam'] === 'phan_tram') {
                    $soTienGiam = $subtotal * ($coupon['gia_tri_giam'] / 100);
                    if ($coupon['giam_toi_da']) $soTienGiam = min($soTienGiam, $coupon['giam_toi_da']);
                } else {
                    $soTienGiam = $coupon['gia_tri_giam'];
                }
                $soTienGiam = min($soTienGiam, $subtotal); // Cannot discount more than subtotal
            } else {
                $maKmId = null; // Invalidate if doesn't meet criteria
            }
        }

        try {
            db()->beginTransaction();

            // 1. Tạo mã đơn hàng
            $maCode = generateOrderCode();

            // 2. Tính tổng tiền hàng thực
            $tongHang    = array_sum(array_map(fn($i) => $i['gia'] * $i['so_luong'], $items));
            $tongThanhToan = max(0, $tongHang + $ship - $soTienGiam);

            // 3. Insert đơn hàng
            $maOrder = db()->insert(
                "INSERT INTO donhang (ma_donhang_code, ma_user, ten_nguoi_nhan, SDT_nguoi_nhan,
                 tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the, ghi_chu,
                 tong_tien_hang, phi_giao_hang, so_tien_giam, tong_thanh_toan,
                 ma_km, trang_thai, phuong_thuc_TT, trang_thai_TT)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$maCode, $userId, $tenNguoiNhan, $sdt,
                 $tinhThanh, $quanHuyen, $phuongXa, $diaChiCuThe, $ghiChu,
                 $tongHang, $ship, $soTienGiam, $tongThanhToan,
                 $maKmId ?: null, 'cho_xac_nhan', $phuongThucTT,
                 in_array($phuongThucTT, ['cod','chuyen_khoan']) ? 'chua_thanh_toan' : 'pending']
            );

            // 4. Insert chi tiết đơn hàng + trừ tồn kho
            foreach ($items as $item) {
                db()->insert(
                    "INSERT INTO chitiet_donhang (ma_donhang, ma_bienthe, ten_sanpham,
                     ram_gb, rom_gb, mau_sac, hinh_anh_url, so_luong, don_gia, thanh_tien)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    [$maOrder, $item['ma_bienthe'], $item['ten_sanpham'],
                     $item['ram_gb'], $item['rom_gb'], $item['mau_sac'],
                     $item['anh'] ?? null, $item['so_luong'],
                     $item['gia'], $item['gia'] * $item['so_luong']]
                );
                // Trừ tồn kho
                db()->execute(
                    "UPDATE bienthe_sanpham SET ton_kho = ton_kho - ? WHERE ma_bienthe = ?",
                    [$item['so_luong'], $item['ma_bienthe']]
                );
                // Tăng số đã bán
                db()->execute(
                    "UPDATE sanpham sp JOIN bienthe_sanpham b ON sp.ma_sanpham = b.ma_sanpham
                     SET sp.tong_da_ban = sp.tong_da_ban + ? WHERE b.ma_bienthe = ?",
                    [$item['so_luong'], $item['ma_bienthe']]
                );
            }

            // 5. Xóa giỏ hàng
            db()->execute("DELETE FROM chitiet_giohang WHERE ma_gio = ?", [$gioId]);

            // 6. Tăng lần dùng mã KM
            if ($maKmId) {
                db()->execute("UPDATE ma_khuyenmai SET so_lan_da_dung = so_lan_da_dung + 1 WHERE ma_km = ?", [$maKmId]);
                db()->insert("INSERT INTO lichsu_dung_km (ma_km, ma_user, ma_donhang, so_tien_giam) VALUES (?,?,?,?)",
                    [$maKmId, $userId, $maOrder, $soTienGiam]);
            }

            // 6.b Ghi log trạng thái ban đầu
            db()->insert("INSERT INTO donhang_trangthai_logs (ma_donhang, trang_thai, mo_ta) VALUES (?,?,?)",
                [$maOrder, 'cho_xac_nhan', 'Đơn hàng đã được đặt thành công và đang chờ xác nhận từ cửa hàng']);

            // 7. Tạo bản ghi thanh toán
            db()->insert(
                "INSERT INTO thanhtoan (ma_donhang, so_tien, phuong_thuc, trang_thai) VALUES (?,?,?,?)",
                [$maOrder, $tongThanhToan, $phuongThucTT, 'pending']
            );

            db()->commit();
            
            // XỬ LÝ THANH TOÁN VNPay
            if ($phuongThucTT === 'vnpay') {
                require_once __DIR__ . '/config/vnpay_config.php';
                require_once __DIR__ . '/includes/vnpay_helper.php';
                
                $vnp_Url = createVNPayUrl($maCode, $tongThanhToan, "Thanh toan don hang " . $maCode);
                redirect($vnp_Url);
            }

            // Redirect TRƯỚC khi xuất HTML - bây giờ hoạt động đúng
            redirect(BASE_URL . '/order_success.php?id=' . $maOrder);

        } catch (Exception $e) {
            db()->rollback();
            setFlash('error', 'Lỗi đặt hàng: ' . $e->getMessage());
        }
    }
}

// Lấy thông tin user và địa chỉ đã lưu
$user = getCurrentUser();
$addresses = db()->fetchAll("SELECT * FROM diachi_user WHERE ma_user = ? ORDER BY la_macdinh DESC", [$userId]);
$defaultAddr = !empty($addresses) ? $addresses[0] : null;

// ============================================================
// CHỈ SAU KHI XỬ LÝ XONG MỚI LOAD HEADER (xuất HTML)
// ============================================================
$pageTitle = 'Đặt Hàng';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:28px;padding-bottom:60px;">
    <h1 class="page-title">📦 Đặt Hàng</h1>
    
    <form method="POST" id="checkoutForm">
    <?php csrfInput(); ?>
    <div class="checkout-layout">
        <!-- Left -->
        <div>
            <!-- Thông tin giao hàng -->
            <div class="checkout-section">
                <h3 class="checkout-section-title">📍 Thông Tin Giao Hàng</h3>
                <?php if ($addresses): ?>
                <div style="margin-bottom:16px;">
                    <label style="font-size:14px;color:var(--text-secondary);margin-bottom:8px;display:block;">Dùng địa chỉ đã lưu:</label>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($addresses as $addr): ?>
                        <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;padding:10px;background:var(--bg-secondary);border-radius:var(--radius-md);">
                            <input type="radio" name="saved_addr" value="<?= $addr['ma_diachi'] ?>" onchange="fillAddress(<?= htmlspecialchars(json_encode($addr)) ?>)" style="margin-top:2px;accent-color:var(--accent);">
                            <div style="font-size:14px;">
                                <strong><?= sanitize($addr['ho_ten_nguoinhan']) ?></strong> - <?= sanitize($addr['SDT_nguoinhan']) ?><br>
                                <span style="color:var(--text-muted)"><?= sanitize($addr['dia_chi_cu_the']) ?>, <?= sanitize($addr['phuong_xa']) ?>, <?= sanitize($addr['quan_huyen']) ?>, <?= sanitize($addr['tinh_thanh']) ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div style="height:1px;background:var(--border);margin:16px 0;"></div>
                </div>
                <?php endif; ?>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Họ và tên *</label>
                        <input type="text" name="ten_nguoi_nhan" class="form-control" required
                               value="<?= sanitize($_POST['ten_nguoi_nhan'] ?? $defaultAddr['ho_ten_nguoinhan'] ?? $user['hovaten'] ?? '') ?>" placeholder="Người nhận hàng">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" name="sdt" class="form-control" required
                               value="<?= sanitize($_POST['sdt'] ?? $defaultAddr['SDT_nguoinhan'] ?? $user['SDT'] ?? '') ?>" placeholder="09xxxxxxxx">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Tỉnh/Thành *</label>
                        <select name="tinh_thanh" id="checkout_tinh_thanh" class="form-control" required></select>
                        <input type="hidden" id="pending_tinh" value="<?= sanitize($_POST['tinh_thanh'] ?? $defaultAddr['tinh_thanh'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quận/Huyện</label>
                        <select name="quan_huyen" id="checkout_quan_huyen" class="form-control" required></select>
                        <input type="hidden" id="pending_quan" value="<?= sanitize($_POST['quan_huyen'] ?? $defaultAddr['quan_huyen'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phường/Xã</label>
                        <select name="phuong_xa" id="checkout_phuong_xa" class="form-control" required></select>
                        <input type="hidden" id="pending_phuong" value="<?= sanitize($_POST['phuong_xa'] ?? $defaultAddr['phuong_xa'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Địa chỉ cụ thể *</label>
                    <input type="text" name="dia_chi_cu_the" class="form-control" required
                           value="<?= sanitize($_POST['dia_chi_cu_the'] ?? $defaultAddr['dia_chi_cu_the'] ?? '') ?>" placeholder="Số nhà, tên đường...">
                </div>
                <div class="form-group">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Ghi chú cho người giao hàng..."><?= sanitize($_POST['ghi_chu'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="checkout-section">
                <h3 class="checkout-section-title">💳 Phương Thức Thanh Toán</h3>
                <div class="payment-options">
                    <?php
                    $payMethods = [
                        ['cod','🤝','Thanh toán khi nhận'],
                        ['chuyen_khoan','🏦','Chuyển khoản'],
                        ['momo','💜','MoMo'],
                        ['vnpay','🔴','VNPay'],
                        ['zalopay','🔵','ZaloPay'],
                        ['visa_mastercard','💳','Visa/Master'],
                    ];
                    foreach ($payMethods as [$val, $icon, $name]):
                    ?>
                    <label class="payment-option <?= ($_POST['phuong_thuc_tt'] ?? 'cod') === $val ? 'selected' : '' ?>" onclick="selectPayment(this)">
                        <input type="radio" name="phuong_thuc_tt" value="<?= $val ?>" <?= ($_POST['phuong_thuc_tt'] ?? 'cod') === $val ? 'checked' : '' ?>>
                        <div class="payment-icon"><?= $icon ?></div>
                        <div class="payment-name"><?= $name ?></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Order Summary -->
        <div>
            <div class="order-summary">
                <h3>Đơn Hàng</h3>
                <div style="max-height:300px;overflow-y:auto;margin-bottom:16px;">
                    <?php foreach ($items as $item): 
                        $img = $item['anh'] ? BASE_URL . '/uploads/products/' . basename($item['anh']) : 'https://placehold.co/60x60/1a1a26/6c63ff?text=SP';
                    ?>
                    <div style="display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                        <img src="<?= $img ?>" style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:var(--bg-secondary);">
                        <div style="flex:1;font-size:13px;">
                            <div style="font-weight:500;"><?= sanitize($item['ten_sanpham']) ?></div>
                            <div style="color:var(--text-muted)"><?= $item['ram_gb'] ?>+<?= $item['rom_gb'] ?>GB, <?= sanitize($item['mau_sac']) ?> × <?= $item['so_luong'] ?></div>
                        </div>
                        <div style="font-weight:600;font-size:14px;"><?= formatPrice($item['gia'] * $item['so_luong']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-row">
                    <span class="label">Tạm tính</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Phí vận chuyển</span>
                    <span><?= $ship == 0 ? '<span style="color:var(--success)">Miễn phí</span>' : formatPrice($ship) ?></span>
                </div>
                
                <?php 
                $applied = $_SESSION['applied_coupon'] ?? null;
                $discount = $applied['discount'] ?? 0;
                ?>
                <div id="checkoutDiscountRow" class="summary-row" style="<?= $discount > 0 ? '' : 'display:none' ?>">
                    <span class="label">Giảm giá (<span id="checkoutCouponCode"><?= $applied['code'] ?? '' ?></span>)</span>
                    <span style="color:var(--danger);">-<?= formatPrice($discount) ?></span>
                </div>

                <div class="summary-row summary-total">
                    <span>Tổng cộng</span>
                    <span style="color:var(--accent);" id="checkoutTotalText"><?= formatPrice(max(0, $total - $discount)) ?></span>
                </div>

                <input type="hidden" name="ma_km" id="checkout_km" value="<?= $applied['id'] ?? '' ?>">
                <input type="hidden" name="so_tien_giam" id="checkout_giam" value="<?= $discount ?>">
                
                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:20px;">
                    ✅ Xác Nhận Đặt Hàng
                </button>
                <p style="text-align:center;font-size:12px;color:var(--text-muted);margin-top:10px;">🔒 Thông tin của bạn được bảo mật an toàn</p>
            </div>
        </div>
    </div>
    </form>
</div>

<script>
let checkoutSelector;

document.addEventListener('DOMContentLoaded', () => {
    checkoutSelector = initAddressSelector({
        provinceSelector: '#checkout_tinh_thanh',
        districtSelector: '#checkout_quan_huyen',
        wardSelector: '#checkout_phuong_xa'
    });

    // Fill initial values if any
    const pTinh = document.getElementById('pending_tinh').value;
    const pQuan = document.getElementById('pending_quan').value;
    const pPhuong = document.getElementById('pending_phuong').value;
    if (pTinh) {
        checkoutSelector.setValues(pTinh, pQuan, pPhuong);
    }
});

function selectPayment(el) {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}
function fillAddress(addr) {
    document.querySelector('[name=ten_nguoi_nhan]').value = addr.ho_ten_nguoinhan || '';
    document.querySelector('[name=sdt]').value = addr.SDT_nguoinhan || '';
    document.querySelector('[name=dia_chi_cu_the]').value = addr.dia_chi_cu_the || '';
    
    if (checkoutSelector) {
        checkoutSelector.setValues(addr.tinh_thanh, addr.quan_huyen, addr.phuong_xa);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
