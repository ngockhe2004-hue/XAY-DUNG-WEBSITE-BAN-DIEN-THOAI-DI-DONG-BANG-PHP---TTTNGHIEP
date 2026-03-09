<?php
/**
 * VNPay IPN - Server to Server
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/vnpay_config.php';
require_once __DIR__ . '/../includes/vnpay_helper.php';

header('Content-Type: application/json');

/*
 * Ghi log IPN để debug (Tùy chọn)
 * file_put_contents('vnpay_ipn_log.txt', date('Y-m-d H:i:s') . ' - ' . json_encode($_GET) . PHP_EOL, FILE_APPEND);
 */

$inputData = $_GET;
$isValidHash = verifyVNPayHash($inputData);

if ($isValidHash) {
    $orderCode = $inputData['vnp_TxnRef'];
    $vnp_Amount = $inputData['vnp_Amount'] / 100;
    $vnp_ResponseCode = $inputData['vnp_ResponseCode'];
    $vnp_TransactionNo = $inputData['vnp_TransactionNo'];
    
    // 1. Kiểm tra đơn hàng trong DB
    $order = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang_code = ?", [$orderCode]);
    
    if (!$order) {
        echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
        exit;
    }
    
    // 2. Kiểm tra số tiền
    if ($order['tong_thanh_toan'] != $vnp_Amount) {
        echo json_encode(['RspCode' => '04', 'Message' => 'Invalid amount']);
        exit;
    }
    
    // 3. Kiểm tra trạng thái đơn hàng (Tránh update đè nếu đã hoàn thành)
    if ($order['trang_thai_TT'] !== 'chua_thanh_toan' && $order['trang_thai_TT'] !== 'pending') {
        echo json_encode(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        exit;
    }
    
    // 4. Cập nhật trạng thái
    if ($vnp_ResponseCode == '00') {
        // Thành công
        db()->execute("UPDATE donhang SET trang_thai_TT = 'da_thanh_toan', trang_thai = 'dang_dong_goi' WHERE ma_donhang_code = ?", [$orderCode]);
        db()->execute("UPDATE thanhtoan SET trang_thai = 'success', ma_giao_dich = ? WHERE ma_donhang = ?", [$vnp_TransactionNo, $order['ma_donhang']]);
    } else {
        // Thất bại
        db()->execute("UPDATE donhang SET trang_thai_TT = 'that_bai' WHERE ma_donhang_code = ?", [$orderCode]);
        db()->execute("UPDATE thanhtoan SET trang_thai = 'failed' WHERE ma_donhang = ?", [$order['ma_donhang']]);
    }
    
    echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
} else {
    echo json_encode(['RspCode' => '97', 'Message' => 'Invalid signature']);
}
