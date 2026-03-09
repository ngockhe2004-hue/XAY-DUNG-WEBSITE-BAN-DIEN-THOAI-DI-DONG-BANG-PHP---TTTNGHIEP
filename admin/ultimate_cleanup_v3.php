<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Database Cleanup: Removing Sample Data (Simplified & Robust)</h1>";
    
    // Tắt kiểm tra khóa ngoại
    db()->execute("SET FOREIGN_KEY_CHECKS = 0");

    // Lấy danh sách tất cả các bảng thực sự tồn tại trong DB
    $existingTables = array_column(db()->fetchAll("SHOW TABLES"), "Tables_in_bandienthoai");

    $tablesToClear = [
        'thanhtoan',
        'chitiet_donhang',
        'donhang',
        'danhgia',
        'magiamgia_sudung', // Có thể không tồn tại
        'lichsu_dung_km',
        'sanpham_danhmuc',
        'hinhanh_sanpham',
        'bienthe_sanpham',
        'sanpham',
        'thuonghieu',
        'danhmuc',
        'magiamgia',
        'ma_khuyenmai',
        'diachi_user',
        'audit_log',
        'tin_nhan',
        'banner',
        'dsyeuthich',
        'hinhanh_danhgia',
        'phanhoi_danhgia'
    ];

    foreach ($tablesToClear as $table) {
        if (in_array($table, $existingTables)) {
            echo "<p>Cleaning table: <code>$table</code>...</p>";
            db()->execute("DELETE FROM $table");
            db()->execute("ALTER TABLE $table AUTO_INCREMENT = 1");
        } else {
            echo "<p style='color:orange'>Skip: Table <code>$table</code> does not exist.</p>";
        }
    }

    // Xử lý bảng users: Chỉ giữ lại admin
    echo "<p>Cleaning table: <code>users</code> (keeping admins)...</p>";
    db()->execute("DELETE FROM users WHERE vai_tro != 'admin'");
    
    db()->execute("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h2 style='color:green'>✅ Database cleaned successfully!</h2>";

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
