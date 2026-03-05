<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
function jsonOut($d) { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$code  = sanitize($_GET['code'] ?? '');
$total = (float)($_GET['total'] ?? 0);

if (!$code) jsonOut(['success'=>false,'message'=>'Vui lòng nhập mã giảm giá']);

$coupon = db()->fetchOne("
    SELECT * FROM ma_khuyenmai 
    WHERE ma_code = ? AND is_active = 1 
    AND NOW() BETWEEN ngay_bat_dau AND ngay_ket_thuc
    AND (so_lan_toi_da IS NULL OR so_lan_da_dung < so_lan_toi_da)
", [$code]);

if (!$coupon) jsonOut(['success'=>false,'message'=>'Mã không hợp lệ hoặc đã hết hạn']);
if ($total < $coupon['don_toi_thieu']) {
    jsonOut(['success'=>false,'message'=>'Đơn hàng cần tối thiểu ' . number_format($coupon['don_toi_thieu'],0,',','.') . '₫ để áp dụng mã này']);
}

// Check user already used
if (isLoggedIn() && $coupon['chi_1_lan_per_user']) {
    $used = db()->fetchOne("SELECT 1 FROM lichsu_dung_km WHERE ma_km = ? AND ma_user = ?", [$coupon['ma_km'], $_SESSION['user_site']['id']]);
    if ($used) jsonOut(['success'=>false,'message'=>'Bạn đã sử dụng mã này rồi']);
}

// Calculate discount
if ($coupon['kieu_giam'] === 'phan_tram') {
    $discount = $total * $coupon['gia_tri_giam'] / 100;
    if ($coupon['giam_toi_da']) $discount = min($discount, $coupon['giam_toi_da']);
} else {
    $discount = $coupon['gia_tri_giam'];
}
$discount = min($discount, $total);

jsonOut([
    'success'   => true,
    'coupon_id' => $coupon['ma_km'],
    'discount'  => round($discount),
    'message'   => "Giảm " . number_format($discount,0,',','.') . "₫ với mã {$code}"
]);
