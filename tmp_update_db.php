<?php
require_once __DIR__ . '/config/database.php';

try {
    $sql = "ALTER TABLE donhang MODIFY COLUMN trang_thai ENUM('cho_xac_nhan','da_xac_nhan','dang_dong_goi','dang_giao','da_giao','da_huy','cho_hoan_tien','da_hoan_tien','da_tra_hang') NOT NULL DEFAULT 'cho_xac_nhan'";
    db()->execute($sql);
    echo "Thành công: Đã cập nhật trạng thái đơn hàng.";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
unlink(__FILE__); // Tự xóa script
