<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

try {
    $messages = db()->fetchAll("
        SELECT * FROM tin_nhan 
        WHERE ma_user = ? OR (ma_user IS NULL AND (nguoi_gui = 'ai' OR nguoi_gui = 'admin'))
        ORDER BY ngay_gui ASC 
        LIMIT 50
    ", [$user_id]);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
