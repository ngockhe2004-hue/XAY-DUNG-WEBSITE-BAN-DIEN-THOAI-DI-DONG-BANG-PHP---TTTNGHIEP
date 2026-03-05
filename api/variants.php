<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false]); exit; }

$variants = db()->fetchAll("SELECT * FROM bienthe_sanpham WHERE ma_sanpham = ? AND is_active = 1 AND ton_kho > 0 ORDER BY ram_gb, rom_gb LIMIT 1", [$id]);
if ($variants) {
    echo json_encode(['success'=>true,'variant'=>$variants[0]], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success'=>false,'message'=>'Hết hàng'], JSON_UNESCAPED_UNICODE);
}
