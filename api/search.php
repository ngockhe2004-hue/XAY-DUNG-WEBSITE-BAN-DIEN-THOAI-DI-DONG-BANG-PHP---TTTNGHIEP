<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
$q = sanitize($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['products'=>[]]); exit; }

$products = db()->fetchAll("
    SELECT sp.ma_sanpham, sp.ten_sanpham,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active = 1) as gia_thap
    FROM sanpham sp
    WHERE sp.is_active = 1 AND (sp.ten_sanpham LIKE ? OR sp.mo_ta_ngan LIKE ?)
    LIMIT 6
", ["%$q%", "%$q%"]);

echo json_encode(['products' => $products], JSON_UNESCAPED_UNICODE);
