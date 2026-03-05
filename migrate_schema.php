<?php
require_once __DIR__ . '/admin/includes/auth_admin.php';
try {
    // Thêm 'da_tra_hang' vào ENUM trang_thai
    db()->execute("ALTER TABLE donhang MODIFY COLUMN trang_thai ENUM('cho_xac_nhan','da_xac_nhan','dang_dong_goi','dang_giao','da_giao','da_huy','cho_hoan_tien','da_hoan_tien','da_tra_hang') NOT NULL DEFAULT 'cho_xac_nhan'");
    echo "Successfully updated donhang.trang_thai schema.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
