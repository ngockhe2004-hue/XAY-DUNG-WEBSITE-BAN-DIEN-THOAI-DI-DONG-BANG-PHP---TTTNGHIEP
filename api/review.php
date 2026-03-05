<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
function jsonOut($d) { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if (!isCustomer()) jsonOut(['success'=>false,'message'=>'Yêu cầu đăng nhập làm khách hàng']);

$input = $_POST;
$maSanpham = (int)($input['ma_sanpham'] ?? 0);
$diem      = (int)($input['diem'] ?? 0);
$tieuDe    = sanitize($input['tieu_de'] ?? '');
$noiDung   = sanitize($input['noi_dung'] ?? '');

if (!$maSanpham) jsonOut(['success'=>false,'message'=>'Thiếu ID sản phẩm']);
if ($diem < 1 || $diem > 5) jsonOut(['success'=>false,'message'=>'Số sao không hợp lệ (1-5)']);
if (!$noiDung) jsonOut(['success'=>false,'message'=>'Vui lòng viết nội dung đánh giá']);

// Check sản phẩm tồn tại
$sp = db()->fetchOne("SELECT 1 FROM sanpham WHERE ma_sanpham = ? AND is_active = 1", [$maSanpham]);
if (!$sp) jsonOut(['success'=>false,'message'=>'Sản phẩm không tồn tại']);

try {
    db()->insert("INSERT INTO danhgia (ma_sanpham, ma_user, diem, tieu_de, noi_dung) VALUES (?,?,?,?,?)",
        [$maSanpham, $_SESSION['user_site']['id'], $diem, $tieuDe, $noiDung]);
    jsonOut(['success'=>true,'message'=>'Đánh giá đã gửi, chờ admin duyệt']);
} catch (Exception $e) {
    jsonOut(['success'=>false,'message'=>'Gửi thất bại: ' . $e->getMessage()]);
}
