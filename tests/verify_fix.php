<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

echo "<h2>--- Bắt đầu kiểm tra sửa lỗi ---</h2>";

// 1. Kiểm tra lưu SKU và giá lớn
$test_id = 1; // Giả sử sản phẩm ID 1 tồn tại
$new_sku = 'TEST-SKU-' . time();
$new_price = 1000000;

try {
    db()->beginTransaction();
    
    // Giả lập logic trong product_form.php
    db()->execute("UPDATE sanpham SET ma_sanpham_code = ?, gia_goc = ? WHERE ma_sanpham = ?", [$new_sku, $new_price, $test_id]);
    
    db()->commit();
    
    // Kiểm tra lại
    $check = db()->fetchOne("SELECT ma_sanpham_code, gia_goc FROM sanpham WHERE ma_sanpham = ?", [$test_id]);
    
    if ($check['ma_sanpham_code'] === $new_sku && (float)$check['gia_goc'] == $new_price) {
        echo "<p style='color:green'>✅ THÀNH CÔNG: SKU và Giá lớn đã được lưu đúng cách.</p>";
    } else {
        echo "<p style='color:red'>❌ THẤT BẠI: Dữ liệu không khớp. SKU: {$check['ma_sanpham_code']}, Giá: {$check['gia_goc']}</p>";
    }
} catch (Exception $e) {
    db()->rollback();
    echo "<p style='color:red'>❌ LỖI: " . $e->getMessage() . "</p>";
}

echo "<h3>--- Kết thúc kiểm tra ---</h3>";
