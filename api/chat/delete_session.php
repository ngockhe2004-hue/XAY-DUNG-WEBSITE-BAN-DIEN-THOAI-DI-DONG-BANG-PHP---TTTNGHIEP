<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$ma_phien = $data['ma_phien'] ?? null;
$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

if (!$ma_phien) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã phiên']);
    exit;
}

try {
    // Chỉ được xóa phiên của chính mình hoặc phiên khách (nếu không có user_id)
    $res = db()->execute("DELETE FROM tin_nhan WHERE ma_phien = ? AND (ma_user = ? OR (ma_user IS NULL AND ? IS NULL))", [$ma_phien, $user_id, $user_id]);
    echo json_encode(['success' => true, 'deleted' => $res]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
