<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

try {
    // Lấy danh sách các phiên chat duy nhất của người dùng
    // Lấy tin nhắn đầu tiên của mỗi phiên để làm tiêu đề
    $sessions = db()->fetchAll("
        SELECT 
            ma_phien, 
            MAX(ten_phien) as ten_phien,
            MIN(ngay_gui) as thoi_gian_bat_dau,
            (SELECT noi_dung FROM tin_nhan t2 WHERE t2.ma_phien = t1.ma_phien AND t2.nguoi_gui = 'user' ORDER BY t2.ngay_gui ASC LIMIT 1) as tin_nhan_dau
        FROM tin_nhan t1
        WHERE (ma_user = ? OR (ma_user IS NULL AND ? IS NULL))
          AND ma_phien IS NOT NULL
        GROUP BY ma_phien
        ORDER BY thoi_gian_bat_dau DESC
        LIMIT 50
    ", [$user_id, $user_id]);

    echo json_encode(['success' => true, 'sessions' => $sessions]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
