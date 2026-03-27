<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;
$ma_phien = $_GET['ma_phien'] ?? null;

try {
    $where = "(ma_user = ? OR (ma_user IS NULL AND (nguoi_gui = 'ai' OR nguoi_gui = 'admin')))";
    $params = [$user_id];

    if ($ma_phien) {
        $where .= " AND ma_phien = ?";
        $params[] = $ma_phien;
    } else {
        // Nếu không có ma_phien, chỉ lấy những tin nhắn cũ chưa có ma_phien (nếu muốn)
        // Hoặc trả về rỗng để bắt đầu mới
        $where .= " AND ma_phien IS NULL";
    }

    $messages = db()->fetchAll("
        SELECT * FROM tin_nhan 
        WHERE $where
        ORDER BY ngay_gui ASC 
        LIMIT 100
    ", $params);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
