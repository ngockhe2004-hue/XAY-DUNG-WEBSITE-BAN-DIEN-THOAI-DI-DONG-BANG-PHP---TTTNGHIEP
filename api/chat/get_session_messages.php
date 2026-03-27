<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$ma_phien = $_GET['ma_phien'] ?? null;
$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

if (!$ma_phien) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã phiên']);
    exit;
}

try {
    $messages = db()->fetchAll("
        SELECT * FROM tin_nhan 
        WHERE ma_phien = ? 
        AND (ma_user = ? OR ma_user IS NULL)
        ORDER BY ngay_gui ASC
    ", [$ma_phien, $user_id]);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
