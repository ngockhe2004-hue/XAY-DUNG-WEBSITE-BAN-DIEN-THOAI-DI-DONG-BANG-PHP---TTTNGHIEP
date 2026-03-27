<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['products'=>[]]); exit; }

$cleanQ = preg_replace('/[+\-><()~*"]/', ' ', $q);
$words = array_filter(explode(' ', $cleanQ), function($w) { return strlen(trim($w)) > 0; });

if (empty($words)) { echo json_encode(['products'=>[]]); exit; }

$ftsTerms = [];
$likeTerms = [];
$whereClause = ['sp.is_active = 1'];
$params = [];

foreach ($words as $word) {
    $word = trim($word);
    if (strlen($word) >= 3) {
        $ftsTerms[] = '+' . $word . '*';
    } else {
        $likeTerms[] = $word;
    }
}

if (!empty($ftsTerms)) {
    $whereClause[] = 'MATCH(sp.ten_sanpham, sp.mo_ta_ngan) AGAINST(? IN BOOLEAN MODE)';
    $params[] = implode(' ', $ftsTerms);
}

foreach ($likeTerms as $lt) {
    $whereClause[] = '(sp.ten_sanpham LIKE ? OR sp.mo_ta_ngan LIKE ?)';
    $params[] = "%$lt%";
    $params[] = "%$lt%";
}

$whereSQL = implode(' AND ', $whereClause);

$products = db()->fetchAll("
    SELECT sp.ma_sanpham, sp.ten_sanpham,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active = 1) as gia_thap
    FROM sanpham sp
    WHERE $whereSQL
    LIMIT 6
", $params);

echo json_encode(['products' => $products], JSON_UNESCAPED_UNICODE);
