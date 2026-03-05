<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
if (!isAdmin()) { echo json_encode(['success' => false, 'message' => 'Không có quyền']); exit; }

$imgId = (int)($_POST['img_id'] ?? 0);
if (!$imgId) { echo json_encode(['success' => false, 'message' => 'Thiếu ID']); exit; }

$img = db()->fetchOne("SELECT image_url FROM hinhanh_sanpham WHERE ma_anh = ?", [$imgId]);
if (!$img) { echo json_encode(['success' => false, 'message' => 'Ảnh không tồn tại']); exit; }

@unlink(UPLOAD_DIR . $img['image_url']);
db()->execute("DELETE FROM hinhanh_sanpham WHERE ma_anh = ?", [$imgId]);

echo json_encode(['success' => true]);
