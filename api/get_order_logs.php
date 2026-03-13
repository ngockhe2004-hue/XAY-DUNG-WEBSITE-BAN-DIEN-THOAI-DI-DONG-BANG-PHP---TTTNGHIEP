<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_site']['id'];

// Bảo mật: Chỉ cho phép xem log đơn hàng của chính mình
$order = db()->fetchOne("SELECT ma_donhang FROM donhang WHERE ma_donhang = ? AND ma_user = ?", [$orderId, $userId]);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$logs = db()->fetchAll("SELECT * FROM donhang_trangthai_logs WHERE ma_donhang = ? ORDER BY ngay_tao DESC", [$orderId]);

$statusLabels = [
    'cho_xac_nhan'=>'Chờ xác nhận','da_xac_nhan'=>'Đã xác nhận','dang_dong_goi'=>'Đang đóng gói',
    'dang_giao'=>'Đang giao','da_giao'=>'Giao hàng thành công','da_huy'=>'Đã hủy','da_hoan_tien'=>'Đã hoàn tiền',
    'da_tra_hang'=>'Trả hàng'
];

$formattedLogs = array_map(function($l) use ($statusLabels) {
    return [
        'trang_thai' => $l['trang_thai'],
        'label' => $statusLabels[$l['trang_thai']] ?? $l['trang_thai'],
        'mo_ta' => $l['mo_ta'],
        'date' => date('d/m/Y', strtotime($l['ngay_tao'])),
        'time' => date('H:i', strtotime($l['ngay_tao']))
    ];
}, $logs);

echo json_encode(['success' => true, 'logs' => $formattedLogs], JSON_UNESCAPED_UNICODE);
