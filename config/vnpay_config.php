<?php
/**
 * Cấu hình VNPay Sandbox
 */
define('VNP_TMN_CODE', '2O3T8UI4'); // Mã định danh định danh website (Website ID)
define('VNP_HASH_SECRET', 'WTGQ1ZYNN9PSWE23L5UOFL83FNDULB17'); // Chuỗi bí mật (Secret Key)
define('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'); // URL thanh toán của VNPay
define('VNP_RETURN_URL', BASE_URL . '/vnpay_return.php'); // URL nhận kết quả trả về
define('VNP_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api.transaction'); // URL API (cho IPN/Truy vấn)
