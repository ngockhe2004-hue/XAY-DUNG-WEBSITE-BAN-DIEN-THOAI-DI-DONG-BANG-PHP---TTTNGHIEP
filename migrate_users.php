<?php
require_once __DIR__ . '/config/database.php';
try {
    db()->execute("ALTER TABLE users ADD COLUMN gioi_tinh ENUM('nam', 'nu', 'khac') DEFAULT NULL AFTER dia_chi");
    db()->execute("ALTER TABLE users ADD COLUMN ngay_sinh DATE DEFAULT NULL AFTER gioi_tinh");
    echo "Thành công: Đã thêm cột gioi_tinh và ngay_sinh vào bảng users.";
} catch (Exception $e) {
    echo "Lỗi hoặc đã tồn tại: " . $e->getMessage();
}
?>
