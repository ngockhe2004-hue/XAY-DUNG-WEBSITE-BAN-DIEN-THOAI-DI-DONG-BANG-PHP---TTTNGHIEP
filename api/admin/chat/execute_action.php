<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Bảo mật nghiêm ngặt
if (!isAdmin()) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['success' => false, 'message' => 'Lỗi bảo mật.']);
    exit;
}

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';

try {
    switch ($type) {
        case 'UPDATE_ORDER_STATUS':
            $id = (int)$data['id'];
            $status = sanitize($data['status']);
            db()->execute("UPDATE donhang SET trang_thai = ? WHERE ma_donhang = ?", [$status, $id]);
            echo json_encode(['success' => true, 'message' => "Đã cập nhật đơn hàng #$id sang trạng thái: $status"]);
            break;

        case 'BAN_USER':
            $id = (int)$data['id'];
            db()->execute("UPDATE users SET trang_thai = 'banned' WHERE ma_user = ? AND quyen = 'customer'", [$id]);
            echo json_encode(['success' => true, 'message' => "Đã khóa tài khoản người dùng ID: $id"]);
            break;
            
        case 'UNBAN_USER':
            $id = (int)$data['id'];
            db()->execute("UPDATE users SET trang_thai = 'active' WHERE ma_user = ? AND quyen = 'customer'", [$id]);
            echo json_encode(['success' => true, 'message' => "Đã mở khóa tài khoản người dùng ID: $id"]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Loại hành động không hợp lệ: $type"]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Lỗi thực thi: " . $e->getMessage()]);
}
