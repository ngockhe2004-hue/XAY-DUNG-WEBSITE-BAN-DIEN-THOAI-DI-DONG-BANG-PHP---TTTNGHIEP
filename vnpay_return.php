<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/vnpay_config.php';
require_once __DIR__ . '/includes/vnpay_helper.php';

$vnp_Data = $_GET;
$vnp_SecureHash = $vnp_Data['vnp_SecureHash'];
unset($vnp_Data['vnp_SecureHash']);

$isValidHash = verifyVNPayHash($_GET);

$orderCode = $_GET['vnp_TxnRef'];
$vnp_ResponseCode = $_GET['vnp_ResponseCode'];
$vnp_TransactionNo = $_GET['vnp_TransactionNo'];
$vnp_Amount = $_GET['vnp_Amount'] / 100;

$order = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang_code = ?", [$orderCode]);

if ($isValidHash) {
    if ($vnp_ResponseCode == '00') {
        // Thanh toán thành công
        // Lưu ý: Việc cập nhật SQL chính xác nhất nên nằm ở IPN. 
        // Ở đây chúng ta cập nhật để User thấy kết quả ngay.
        if ($order && $order['trang_thai_TT'] !== 'da_thanh_toan') {
            db()->execute("UPDATE donhang SET trang_thai_TT = 'da_thanh_toan', trang_thai = 'dang_dong_goi' WHERE ma_donhang_code = ?", [$orderCode]);
            db()->execute("UPDATE thanhtoan SET trang_thai = 'success', ma_giao_dich = ? WHERE ma_donhang = ?", [$vnp_TransactionNo, $order['ma_donhang']]);
        }
        $success = true;
        $msg = "Thanh toán thành công đơn hàng #{$orderCode}";
    } else {
        $success = false;
        $msg = "Thanh toán thất bại hoặc đã bị hủy. (Mã lỗi: {$vnp_ResponseCode})";
    }
} else {
    $success = false;
    $msg = "Chữ ký không hợp lệ. Vui lòng liên hệ hỗ trợ.";
}

$pageTitle = 'Kết quả thanh toán VNPay';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding:60px 0; text-align:center;">
    <div class="card" style="max-width:500px; margin:0 auto; padding:40px;">
        <div style="font-size:64px; margin-bottom:20px;">
            <?= $success ? '✅' : '❌' ?>
        </div>
        <h2 style="margin-bottom:16px;"><?= $success ? 'Thanh Toán Thành Công!' : 'Thanh Toán Thất Bại' ?></h2>
        <p style="color:var(--text-secondary); margin-bottom:30px;"><?= $msg ?></p>
        
        <?php if ($order): ?>
        <div style="background:var(--bg-secondary); padding:20px; border-radius:var(--radius-md); margin-bottom:30px; text-align:left;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>Mã đơn hàng:</span>
                <strong>#<?= $orderCode ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span>Số tiền:</span>
                <strong style="color:var(--accent);"><?= formatPrice($vnp_Amount) ?></strong>
            </div>
        </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <a href="<?= BASE_URL ?>/order_detail.php?id=<?= $order['ma_donhang'] ?? '' ?>" class="btn btn-primary">Xem đơn hàng</a>
            <a href="<?= BASE_URL ?>" class="btn btn-outline">Về trang chủ</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
