<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

try {
    // Xóa tin nhắn dựa trên user_id (nếu có) hoặc tất cả nếu là khách (phiên làm việc hiện tại không lưu sâu cho khách)
    // Trong thực tế, bạn có thể muốn dùng session_id cho khách.
    if ($user_id) {
        db()->execute("DELETE FROM tin_nhan WHERE ma_user = ?", [$user_id]);
    } else {
        // Nếu là khách, có thể xóa dựa trên một logic khác hoặc bỏ qua
        // Ở đây tạm thời không xóa để tránh ảnh hưởng khách khác nếu dùng chung ID null
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
