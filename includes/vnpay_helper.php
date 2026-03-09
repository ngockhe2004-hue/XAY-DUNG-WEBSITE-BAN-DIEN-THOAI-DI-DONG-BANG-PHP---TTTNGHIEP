<?php
/**
 * Helper hỗ trợ tạo URL và kiểm tra dữ liệu VNPay
 */

function createVNPayUrl($orderCode, $amount, $orderInfo = "Thanh toan don hang") {
    $vnp_TmnCode = VNP_TMN_CODE;
    $vnp_HashSecret = VNP_HASH_SECRET;
    $vnp_Url = VNP_URL;
    $vnp_Returnurl = VNP_RETURN_URL;

    $vnp_TxnRef = $orderCode; 
    $vnp_OrderInfo = $orderInfo;
    $vnp_OrderType = 'billpayment';
    $vnp_Amount = number_format($amount * 100, 0, '', ''); // Đảm bảo không bị định dạng khoa học hoặc số thực
    $vnp_Locale = 'vn';
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    if ($vnp_IpAddr == '::1') $vnp_IpAddr = '127.0.0.1'; // Chuẩn hóa IP Localhost

    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef
    );

    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query;
    if (isset($vnp_HashSecret)) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }
    
    return $vnp_Url;
}

function verifyVNPayHash($data) {
    $vnp_HashSecret = VNP_HASH_SECRET;
    $vnp_SecureHash = $data['vnp_SecureHash'] ?? '';
    
    unset($data['vnp_SecureHash']);
    unset($data['vnp_SecureHashType']);
    
    ksort($data);
    $i = 0;
    $hashData = "";
    foreach ($data as $key => $value) {
        if ($i == 1) {
            $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }

    $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
    return $secureHash === $vnp_SecureHash;
}
