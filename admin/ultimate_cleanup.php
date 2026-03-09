<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Database Cleanup: Removing Sample Data</h1>";
    db()->beginTransaction();

    // Tắt kiểm tra khóa ngoại để TRUNCATE dễ dàng
    db()->execute("SET FOREIGN_KEY_CHECKS = 0");

    $tablesToTruncate = [
        'thanhtoan',
        'chitiet_donhang',
        'donhang',
        'danhgia',
        'magiamgia_sudung',
        'sanpham_danhmuc',
        'hinhanh_sanpham',
        'bienthe_sanpham',
        'sanpham',
        'thuonghieu',
        'danhmuc',
        'magiamgia',
        'diachi_user'
    ];

    foreach ($tablesToTruncate as $table) {
        echo "<p>Truncating table: <code>$table</code>...</p>";
        db()->execute("TRUNCATE TABLE $table");
    }

    // Xử lý bảng users: Chỉ giữ lại admin
    echo "<p>Cleaning table: <code>users</code> (keeping admins)...</p>";
    db()->execute("DELETE FROM users WHERE vai_tro != 'admin'");
    
    // Reset AUTO_INCREMENT cho users (không TRUNCATE được vì có thể có admin)
    // Nhưng thực tế delete xong thì ID admin vẫn giữ nguyên, tốt hơn nên giữ nguyên để tránh xung đột.

    db()->execute("SET FOREIGN_KEY_CHECKS = 1");
    db()->commit();

    echo "<h2 style='color:green'>✅ Database cleaned successfully!</h2>";
    echo "<p>All sample products, orders, and customers (except admins) have been removed.</p>";

    // Thông báo về việc xóa ảnh mẫu
    $uploadDir = __DIR__ . '/../uploads/products/';
    echo "<h2>Cleaning Uploads Path: $uploadDir</h2>";
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'index.php') {
                if (@unlink($file)) $count++;
            }
        }
        echo "<p>Deleted $count product image files.</p>";
    }

} catch (Exception $e) {
    db()->rollback();
    echo "<h2 style='color:red'>❌ Error during cleanup:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
