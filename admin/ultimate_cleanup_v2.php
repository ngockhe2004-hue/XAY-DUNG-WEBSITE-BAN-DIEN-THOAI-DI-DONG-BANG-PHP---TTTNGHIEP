<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Database Cleanup: Removing Sample Data (Retry with DELETE)</h1>";
    
    // Tắt kiểm tra khóa ngoại
    db()->execute("SET FOREIGN_KEY_CHECKS = 0");

    $tablesToDelete = [
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
        'diachi_user',
        'audit_log',
        'tin_nhan',
        'banner',
        'dsyeuthich',
        'hinhanh_danhgia',
        'phanhoi_danhgia',
        'lichsu_dung_km'
    ];

    foreach ($tablesToDelete as $table) {
        echo "<p>Deleting from table: <code>$table</code>...</p>";
        db()->execute("DELETE FROM $table");
        // Reset auto increment
        db()->execute("ALTER TABLE $table AUTO_INCREMENT = 1");
    }

    // Xử lý bảng users: Chỉ giữ lại admin
    echo "<p>Cleaning table: <code>users</code> (keeping admins)...</p>";
    db()->execute("DELETE FROM users WHERE vai_tro != 'admin'");
    
    db()->execute("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h2 style='color:green'>✅ Database cleaned successfully!</h2>";
    echo "<p>All sample records have been removed. IDs have been reset.</p>";

    // Xóa ảnh
    $uploadDir = __DIR__ . '/../uploads/products/';
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
    echo "<h2 style='color:red'>❌ Error during cleanup:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
