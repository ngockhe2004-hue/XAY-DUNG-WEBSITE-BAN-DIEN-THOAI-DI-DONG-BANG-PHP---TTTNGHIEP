<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function jsonOut($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

if (!isLoggedIn()) jsonOut(['success'=>false,'message'=>'Vui lòng đăng nhập']);

$userId = $_SESSION['user_site']['id'];
$gioId  = getOrCreateCart();
$method = $_SERVER['REQUEST_METHOD'];

// ===== GET - Lấy giỏ hàng =====
if ($method === 'GET') {
    $items = db()->fetchAll("
        SELECT ctgh.*, b.gia, b.ton_kho, sp.ten_sanpham, b.mau_sac, b.ram_gb, b.rom_gb
        FROM chitiet_giohang ctgh
        JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
        JOIN sanpham sp ON b.ma_sanpham = sp.ma_sanpham
        WHERE ctgh.ma_gio = ?
    ", [$gioId]);
    $total = array_sum(array_map(fn($i) => $i['gia'] * $i['so_luong'], $items));
    jsonOut(['success'=>true,'items'=>$items,'total'=>$total,'count'=>count($items)]);
}

// ===== POST - Thêm vào giỏ =====
if ($method === 'POST') {
    if (!$gioId) jsonOut(['success'=>false,'message'=>'Không thể tạo giỏ hàng. Vui lòng đăng nhập lại.']);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $maBienthe = (int)($input['ma_bienthe'] ?? 0);
    $soLuong   = max(1, (int)($input['so_luong'] ?? 1));

    if (!$maBienthe) jsonOut(['success'=>false,'message'=>'Thiếu biến thể']);

    // Kiểm tra biến thể
    $bienthe = db()->fetchOne("SELECT * FROM bienthe_sanpham WHERE ma_bienthe = ? AND is_active = 1", [$maBienthe]);
    if (!$bienthe) jsonOut(['success'=>false,'message'=>'Biến thể không tồn tại']);

    // Kiểm tra tồn kho
    $existing = db()->fetchOne("SELECT * FROM chitiet_giohang WHERE ma_gio = ? AND ma_bienthe = ?", [$gioId, $maBienthe]);
    $totalQty  = $soLuong + ($existing['so_luong'] ?? 0);
    if ($totalQty > $bienthe['ton_kho']) {
        jsonOut(['success'=>false,'message'=>"Chỉ còn {$bienthe['ton_kho']} sản phẩm trong kho"]);
    }

    try {
        if ($existing) {
            db()->execute("UPDATE chitiet_giohang SET so_luong = so_luong + ?, gia_tai_luc_them = ? WHERE ma_ctgh = ?",
                [$soLuong, $bienthe['gia'], $existing['ma_ctgh']]);
        } else {
            db()->insert("INSERT INTO chitiet_giohang (ma_gio, ma_bienthe, so_luong, gia_tai_luc_them) VALUES (?,?,?,?)",
                [$gioId, $maBienthe, $soLuong, $bienthe['gia']]);
        }
        $cartCount = (int)db()->fetchColumn("SELECT COALESCE(SUM(so_luong),0) FROM chitiet_giohang WHERE ma_gio = ?", [$gioId]);
        jsonOut(['success'=>true,'message'=>'Đã thêm vào giỏ hàng','cart_count'=>$cartCount]);
    } catch (Exception $e) {
        jsonOut(['success'=>false,'message'=>'Lỗi: ' . $e->getMessage()]);
    }
}

// ===== PUT - Cập nhật số lượng =====
if ($method === 'PUT') {
    $input    = json_decode(file_get_contents('php://input'), true);
    $maCtgh   = (int)($input['ma_ctgh'] ?? 0);
    $soLuong  = max(1, (int)($input['so_luong'] ?? 1));

    // Verify ownership
    $item = db()->fetchOne("
        SELECT ctgh.*, b.gia, b.ton_kho, b.ma_bienthe
        FROM chitiet_giohang ctgh 
        JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
        WHERE ctgh.ma_ctgh = ? AND ctgh.ma_gio = ?
    ", [$maCtgh, $gioId]);

    if (!$item) jsonOut(['success'=>false,'message'=>'Không tìm thấy sản phẩm trong giỏ']);
    if ($soLuong > $item['ton_kho']) {
        jsonOut(['success'=>false,'message'=>"Chỉ còn {$item['ton_kho']} sản phẩm"]);
    }

    db()->execute("UPDATE chitiet_giohang SET so_luong = ? WHERE ma_ctgh = ?", [$soLuong, $maCtgh]);

    $cartTotal = (float)db()->fetchColumn("
        SELECT COALESCE(SUM(ctgh.so_luong * b.gia), 0)
        FROM chitiet_giohang ctgh JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
        WHERE ctgh.ma_gio = ?
    ", [$gioId]);
    $cartCount = (int)db()->fetchColumn("SELECT COALESCE(SUM(so_luong),0) FROM chitiet_giohang WHERE ma_gio = ?", [$gioId]);

    jsonOut(['success'=>true,'cart_total'=>$cartTotal,'cart_count'=>$cartCount,'unit_price'=>$item['gia']]);
}

// ===== DELETE - Xóa sản phẩm / xóa tất cả =====
if ($method === 'DELETE') {
    if (!empty($_GET['clear'])) {
        db()->execute("DELETE FROM chitiet_giohang WHERE ma_gio = ?", [$gioId]);
        jsonOut(['success'=>true,'cart_count'=>0,'cart_total'=>0]);
    }
    $maCtgh = (int)($_GET['ma_ctgh'] ?? 0);
    if (!$maCtgh) jsonOut(['success'=>false,'message'=>'Thiếu ID']);
    db()->execute("DELETE FROM chitiet_giohang WHERE ma_ctgh = ? AND ma_gio = ?", [$maCtgh, $gioId]);
    $cartTotal = (float)db()->fetchColumn("
        SELECT COALESCE(SUM(ctgh.so_luong * b.gia),0)
        FROM chitiet_giohang ctgh JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
        WHERE ctgh.ma_gio = ?
    ", [$gioId]);
    $cartCount = (int)db()->fetchColumn("SELECT COALESCE(SUM(so_luong),0) FROM chitiet_giohang WHERE ma_gio = ?", [$gioId]);
    jsonOut(['success'=>true,'cart_total'=>$cartTotal,'cart_count'=>$cartCount]);
}

jsonOut(['success'=>false,'message'=>'Invalid request']);
