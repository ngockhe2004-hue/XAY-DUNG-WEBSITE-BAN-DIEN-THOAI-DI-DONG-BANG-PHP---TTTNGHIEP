<?php
require_once __DIR__ . '/admin/includes/auth_admin.php';
$o = db()->fetchOne("SELECT ma_donhang, trang_thai, trang_thai_TT FROM donhang WHERE ma_donhang = 6");
echo "Order #6:\n";
echo "trang_thai: [" . ($o['trang_thai'] ?? 'NULL') . "]\n";
echo "trang_thai_TT: [" . ($o['trang_thai_TT'] ?? 'NULL') . "]\n";
?>
