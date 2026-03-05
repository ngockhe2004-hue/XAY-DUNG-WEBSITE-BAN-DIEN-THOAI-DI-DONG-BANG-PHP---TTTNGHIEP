<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
function jsonOut($d) { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if (!isLoggedIn()) jsonOut(['success'=>false,'message'=>'Vui lòng đăng nhập']);
$userId = $_SESSION['user_site']['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $maSanpham = (int)($input['ma_sanpham'] ?? 0);
    if (!$maSanpham) jsonOut(['success'=>false,'message'=>'Thiếu ID sản phẩm']);
    
    $exists = db()->fetchOne("SELECT 1 FROM dsyeuthich WHERE ma_user = ? AND ma_sanpham = ?", [$userId, $maSanpham]);
    if ($exists) {
        db()->execute("DELETE FROM dsyeuthich WHERE ma_user = ? AND ma_sanpham = ?", [$userId, $maSanpham]);
        jsonOut(['success'=>true,'action'=>'removed','message'=>'Đã xóa khỏi yêu thích']);
    } else {
        db()->insert("INSERT INTO dsyeuthich (ma_user, ma_sanpham) VALUES (?,?)", [$userId, $maSanpham]);
        jsonOut(['success'=>true,'action'=>'added','message'=>'Đã thêm vào yêu thích']);
    }
}
jsonOut(['success'=>false,'message'=>'Invalid']);
