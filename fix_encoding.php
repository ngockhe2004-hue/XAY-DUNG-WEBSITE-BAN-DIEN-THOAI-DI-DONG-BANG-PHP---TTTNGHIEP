<?php
// Fix encoding cho database - chạy 1 lần tại: http://localhost/website bandienthoai/fix_encoding.php
require_once __DIR__ . '/config/database.php';

$db = db();

// Xóa dữ liệu cũ bị encoding sai, giữ lại sản phẩm và biến thể (vì đúng rồi)
$db->execute("SET FOREIGN_KEY_CHECKS=0");
$db->execute("TRUNCATE TABLE danhmuc");
$db->execute("TRUNCATE TABLE thuonghieu");
$db->execute("SET FOREIGN_KEY_CHECKS=1");

// Thêm lại thương hiệu với UTF-8 đúng
$brands = [
    ['Apple',   'apple',   'Mỹ',       'Hãng điện thoại cao cấp số 1 thế giới'],
    ['Samsung', 'samsung', 'Hàn Quốc', 'Nhà sản xuất điện thoại Android hàng đầu'],
    ['Xiaomi',  'xiaomi',  'Trung Quốc','Điện thoại giá tốt, cấu hình mạnh'],
    ['OPPO',    'oppo',    'Trung Quốc','Chuyên về camera và thiết kế'],
    ['Vivo',    'vivo',    'Trung Quốc','Camera selfie hàng đầu'],
    ['Realme',  'realme',  'Trung Quốc','Điện thoại gaming tầm trung'],
    ['OnePlus', 'oneplus', 'Trung Quốc','Flagship Android tốc độ cao'],
    ['Google',  'google',  'Mỹ',       'Điện thoại Pixel AI hàng đầu'],
];
foreach ($brands as $b) {
    $db->insert("INSERT INTO thuonghieu (ten_thuonghieu, slug, quoc_gia, mo_ta, is_active) VALUES (?,?,?,?,1)", $b);
}

// Thêm lại danh mục
$cats = [
    ['iPhone', 'iphone', 'Điện thoại Apple iPhone', 1],
    ['Samsung Galaxy', 'samsung-galaxy', 'Điện thoại Samsung Galaxy', 2],
    ['Điện thoại Xiaomi', 'dien-thoai-xiaomi', 'Điện thoại Xiaomi', 3],
    ['OPPO', 'dien-thoai-oppo', 'Điện thoại OPPO', 4],
    ['Flagship', 'flagship', 'Flagship cao cấp trên 15 triệu', 5],
    ['Tầm trung', 'tam-trung', 'Điện thoại tầm trung 5-15 triệu', 6],
    ['Phổ thông', 'pho-thong', 'Điện thoại phổ thông dưới 5 triệu', 7],
    ['Gaming Phone', 'gaming-phone', 'Điện thoại chơi game', 8],
];
foreach ($cats as $c) {
    $db->insert("INSERT INTO danhmuc (ten_danhmuc, slug, mo_ta, thu_tu, is_active) VALUES (?,?,?,?,1)", $c);
}

// Cập nhật ma_danhmuc và ma_thuonghieu cho sản phẩm
$updates = [
    ['iphone-16-pro-max',       'iphone',           'apple'],
    ['iphone-16',               'iphone',           'apple'],
    ['samsung-galaxy-s25-ultra','samsung-galaxy',   'samsung'],
    ['samsung-galaxy-a55',      'samsung-galaxy',   'samsung'],
    ['xiaomi-14-ultra',         'dien-thoai-xiaomi','xiaomi'],
    ['oppo-find-x8-pro',        'dien-thoai-oppo',  'oppo'],
    ['realme-gt-6',             'gaming-phone',      'realme'],
    ['google-pixel-9-pro',      'flagship',         'google'],
];
foreach ($updates as [$slug, $catSlug, $brandSlug]) {
    $cat   = $db->fetchOne("SELECT ma_danhmuc FROM danhmuc WHERE slug=?", [$catSlug]);
    $brand = $db->fetchOne("SELECT ma_thuonghieu FROM thuonghieu WHERE slug=?", [$brandSlug]);
    if ($cat && $brand) {
        $db->execute("UPDATE sanpham SET ma_danhmuc=?, ma_thuonghieu=? WHERE slug=?",
            [$cat['ma_danhmuc'], $brand['ma_thuonghieu'], $slug]);
    }
}

// Mã khuyến mãi
$db->execute("SET FOREIGN_KEY_CHECKS=0");
$db->execute("TRUNCATE TABLE ma_khuyenmai");
$db->execute("SET FOREIGN_KEY_CHECKS=1");
$kms = [
    ['WELCOME10', 'Chào mừng thành viên mới', 'phan_tram', 10, 500000, 1000000, 1000],
    ['SALE50K',   'Giảm 50k mọi đơn',          'so_tien',  50000, null, 500000, 5000],
    ['PHONE20',   'Giảm 20% tối đa 2 triệu',   'phan_tram', 20, 2000000, 5000000, 500],
];
foreach ($kms as $km) {
    $db->insert("INSERT INTO ma_khuyenmai (ma_code, ten_km, kieu_giam, gia_tri_giam, giam_toi_da, don_toi_thieu, so_lan_toi_da, ngay_bat_dau, ngay_ket_thuc, is_active) VALUES (?,?,?,?,?,?,?,NOW(),DATE_ADD(NOW(),INTERVAL 1 YEAR),1)", $km);
}

echo '<h2 style="font-family:sans-serif;color:green;padding:20px;">✅ Fix encoding hoàn tất!</h2>';
echo '<p style="font-family:sans-serif;padding:0 20px;">Danh mục: ' . $db->fetchColumn("SELECT COUNT(*) FROM danhmuc") . '</p>';
echo '<p style="font-family:sans-serif;padding:0 20px;">Thương hiệu: ' . $db->fetchColumn("SELECT COUNT(*) FROM thuonghieu") . '</p>';
echo '<p style="font-family:sans-serif;padding:0 20px;">Sản phẩm: ' . $db->fetchColumn("SELECT COUNT(*) FROM sanpham") . '</p>';
echo '<p style="font-family:sans-serif;padding:0 20px;"><a href="/">→ Về trang chủ</a> | <a href="javascript:void(0)" onclick="fetch(\'/website bandienthoai/fix_encoding.php\').then(()=>location.href=\'http://localhost/website%20bandienthoai/index.php\')">→ Về trang chủ</a></p>';
echo '<script>setTimeout(()=>location.href="http://localhost/website%20bandienthoai/index.php",2000)</script>';
