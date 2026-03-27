<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function jsonOut($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) jsonOut(false, 'Vui lòng đăng nhập');

$userId = $_SESSION['user_site']['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle PUT - Cancel order
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = (int)($input['id'] ?? 0);
    $action = $input['action'] ?? '';

    if ($action === 'cancel') {
        $order = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang = ? AND ma_user = ?", [$orderId, $userId]);
        if (!$order) jsonOut(false, 'Không tìm thấy đơn hàng');
        
        if ($order['trang_thai'] !== 'cho_xac_nhan') {
            jsonOut(false, 'Chỉ có thể hủy đơn hàng đang chờ xác nhận');
        }

        try {
            db()->beginTransaction();
            
            // Cập nhật trạng thái đơn hàng
            db()->execute("UPDATE donhang SET trang_thai = 'da_huy' WHERE ma_donhang = ?", [$orderId]);
            
            // Ghi log chi tiết
            db()->insert("INSERT INTO donhang_trangthai_logs (ma_donhang, trang_thai, mo_ta) VALUES (?,?,?)",
                [$orderId, 'da_huy', 'Khách hàng đã yêu cầu hủy đơn hàng này']);
            
            // Hoàn lại tồn kho
            $items = db()->fetchAll("SELECT * FROM chitiet_donhang WHERE ma_donhang = ?", [$orderId]);
            foreach ($items as $item) {
                db()->execute("UPDATE bienthe_sanpham SET ton_kho = ton_kho + ? WHERE ma_bienthe = ?", [$item['so_luong'], $item['ma_bienthe']]);
            }
            
            db()->commit();
            jsonOut(true, 'Đã hủy đơn hàng thành công');
        } catch (Exception $e) {
            db()->rollback();
            jsonOut(false, 'Lỗi: ' . $e->getMessage());
        }
    }

    if ($action === 'cancel_all') {
        $orders = db()->fetchAll("SELECT * FROM donhang WHERE ma_user = ? AND trang_thai = 'cho_xac_nhan'", [$userId]);
        if (empty($orders)) jsonOut(false, 'Không có đơn hàng nào đang chờ xác nhận để hủy');

        try {
            db()->beginTransaction();
            $count = 0;
            foreach ($orders as $order) {
                db()->execute("UPDATE donhang SET trang_thai = 'da_huy' WHERE ma_donhang = ?", [$order['ma_donhang']]);
                db()->insert("INSERT INTO donhang_trangthai_logs (ma_donhang, trang_thai, mo_ta) VALUES (?,?,?)",
                    [$order['ma_donhang'], 'da_huy', 'Hủy hàng loạt bằng PhoneStore Copilot']);
                
                $items = db()->fetchAll("SELECT * FROM chitiet_donhang WHERE ma_donhang = ?", [$order['ma_donhang']]);
                foreach ($items as $item) {
                    db()->execute("UPDATE bienthe_sanpham SET ton_kho = ton_kho + ? WHERE ma_bienthe = ?", [$item['so_luong'], $item['ma_bienthe']]);
                }
                $count++;
            }
            db()->commit();
            jsonOut(true, "Đã hủy thành công $count đơn hàng");
        } catch (Exception $e) {
            db()->rollback();
            jsonOut(false, 'Lỗi: ' . $e->getMessage());
        }
    }
}

// Handle POST - Rebuy
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $orderId = (int)($input['id'] ?? 0);
    $action = $input['action'] ?? '';

    if ($action === 'rebuy') {
        $order = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang = ? AND ma_user = ?", [$orderId, $userId]);
        if (!$order) jsonOut(false, 'Không tìm thấy đơn hàng');

        $items = db()->fetchAll("SELECT * FROM chitiet_donhang WHERE ma_donhang = ?", [$orderId]);
        if (empty($items)) jsonOut(false, 'Đơn hàng không có sản phẩm');

        $gioId = getOrCreateCart();
        $skipped = [];
        $addedCount = 0;

        try {
            foreach ($items as $item) {
                // Kiểm tra biến thể còn tồn tại và còn hàng không
                $bienthe = db()->fetchOne("SELECT * FROM bienthe_sanpham WHERE ma_bienthe = ? AND is_active = 1", [$item['ma_bienthe']]);
                if (!$bienthe || $bienthe['ton_kho'] <= 0) {
                    $skipped[] = $item['ten_sanpham'];
                    continue;
                }

                $soLuong = min($item['so_luong'], $bienthe['ton_kho']);
                
                // Kiểm tra xem đã có trong giỏ chưa
                $existing = db()->fetchOne("SELECT * FROM chitiet_giohang WHERE ma_gio = ? AND ma_bienthe = ?", [$gioId, $item['ma_bienthe']]);
                
                if ($existing) {
                    db()->execute("UPDATE chitiet_giohang SET so_luong = so_luong + ? WHERE ma_ctgh = ?", [$soLuong, $existing['ma_ctgh']]);
                } else {
                    db()->insert("INSERT INTO chitiet_giohang (ma_gio, ma_bienthe, so_luong, gia_tai_luc_them) VALUES (?,?,?,?)",
                        [$gioId, $item['ma_bienthe'], $soLuong, $bienthe['gia']]);
                }
                $addedCount++;
            }

            $msg = "Đã thêm $addedCount mục vào giỏ hàng.";
            if (!empty($skipped)) {
                $msg .= " Bỏ qua " . count($skipped) . " sản phẩm do hết hàng: " . implode(', ', $skipped);
            }
            
            jsonOut(true, $msg, ['cart_count' => (int)db()->fetchColumn("SELECT COALESCE(SUM(so_luong),0) FROM chitiet_giohang WHERE ma_gio = ?", [$gioId])]);
        } catch (Exception $e) {
            jsonOut(false, 'Lỗi: ' . $e->getMessage());
        }
    }
}

jsonOut(false, 'Yêu cầu không hợp lệ');
