<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$ma_phien = $data['ma_phien'] ?? null;
$ten_moi = $data['ten_moi'] ?? '';
$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

if (!$ma_phien || empty($ten_moi)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    $res = db()->execute("UPDATE tin_nhan SET ten_phien = ? WHERE ma_phien = ? AND (ma_user = ? OR (ma_user IS NULL AND ? IS NULL))", [$ten_moi, $ma_phien, $user_id, $user_id]);
    echo json_encode(['success' => true, 'updated' => $res]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
