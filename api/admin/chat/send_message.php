<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Bảo mật: Chỉ cho phép Admin
if (!isAdmin()) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['success' => false, 'message' => 'Quyền hạn không đủ.']);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$ma_phien = $data['ma_phien'] ?? uniqid('adm_');
$admin_user = getCurrentUser();

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn không được để trống.']);
    exit;
}

try {
    $apiKey = GEMINI_API_KEY;
    $model = GEMINI_MODEL;

    if (!$apiKey) {
        throw new Exception("API Key không được tìm thấy. Vui lòng kiểm tra .env");
    }

    // 1. Phân tích ngữ cảnh Admin (Thêm dữ liệu theo ngày)
    $today = date('Y-m-d');
    $stats = [
        'total_revenue' => db()->fetchColumn("SELECT SUM(tong_thanh_toan) FROM donhang WHERE trang_thai = 'da_giao'") ?: 0,
        'today_revenue' => db()->fetchColumn("SELECT SUM(tong_thanh_toan) FROM donhang WHERE trang_thai = 'da_giao' AND DATE(ngay_dat) = ?", [$today]) ?: 0,
        'today_orders' => db()->fetchColumn("SELECT COUNT(*) FROM donhang WHERE DATE(ngay_dat) = ?", [$today]) ?: 0,
        'today_new_users' => db()->fetchColumn("SELECT COUNT(*) FROM users WHERE quyen = 'customer' AND DATE(ngay_lap) = ?", [$today]) ?: 0,
        'pending_orders' => db()->fetchColumn("SELECT COUNT(*) FROM donhang WHERE trang_thai = 'cho_xac_nhan'") ?: 0,
        'low_stock' => db()->fetchColumn("SELECT COUNT(*) FROM bienthe_sanpham WHERE ton_kho <= 5 AND is_active = 1") ?: 0,
        'total_users' => db()->fetchColumn("SELECT COUNT(*) FROM users WHERE quyen = 'customer'") ?: 0
    ];

    // TOP sản phẩm bán chạy trong ngày (Sử dụng bảng chitiet_donhang)
    $top_products = db()->fetchAll("SELECT ten_sanpham, SUM(so_luong) as ban_ra 
        FROM chitiet_donhang ct 
        JOIN donhang d ON ct.ma_donhang = d.ma_donhang 
        WHERE DATE(d.ngay_dat) = ? AND d.trang_thai != 'da_huy'
        GROUP BY ten_sanpham 
        ORDER BY ban_ra DESC LIMIT 3", [$today]);

    $top_products_text = "";
    if (!empty($top_products)) {
        foreach($top_products as $p) {
            $top_products_text .= "- {$p['ten_sanpham']} ({$p['ban_ra']} SP)\n";
        }
    } else {
        $top_products_text = "Chưa có dữ liệu bán hàng hôm nay.";
    }

    // 2. Định nghĩa System Prompt cho Admin
    $system_context = "Bạn là Trợ lý Quản trị (Admin AI) của hệ thống PhoneStore.
    Tên bạn là: 'Trợ lý Admin'.
    
    NGỮ CẢNH HỆ THỐNG TRONG NGÀY (" . date('d/m/Y') . "):
    - Doanh thu hôm nay: " . number_format($stats['today_revenue']) . " VND.
    - Đơn hàng mới hôm nay: " . $stats['today_orders'] . ".
    - Khách hàng mới hôm nay: " . $stats['today_new_users'] . ".
    - Sản phẩm bán chạy hôm nay:\n" . $top_products_text . "
    
    TỔNG QUAN HỆ THỐNG:
    - Tổng doanh thu (đã giao): " . number_format($stats['total_revenue']) . " VND.
    - Đơn hàng đang chờ xác nhận: " . $stats['pending_orders'] . " (CẦN XỬ LÝ GẤP).
    - Sản phẩm sắp hết hàng (<=5): " . $stats['low_stock'] . " (CẦN NHẬP HÀNG).
    - Tổng số khách hàng: " . $stats['total_users'] . ".
    
    KHẢ NĂNG THỰC THI (ACTION CATALOG):
    Bạn có thể đề xuất các hành động kỹ thuật bằng cách trả về một khối JSON ở CUỐI tin nhắn với định dạng:
    [[ACTION: {\"type\": \"UPDATE_ORDER_STATUS\", \"id\": 123, \"status\": \"da_xac_nhan\", \"important\": true}]]
    Các loại hành động (type) hỗ trợ:
    - 'UPDATE_ORDER_STATUS': Cập nhật trạng thái đơn (id, status). Trạng thái gồm: cho_xac_nhan, da_xac_nhan, dang_giao, da_giao, da_huy.
    - 'BAN_USER': Khóa tài khoản user (id).
    - 'UNBAN_USER': Mở khóa tài khoản user (id).
    
    QUY TẮC:
    1. Trả lời chuyên nghiệp, hỗ trợ Admin tối đa.
    2. Với các yêu cầu quan trọng, luôn thêm 'important': true trong JSON Action để UI hiển thị nút xác nhận.
    3. Luôn báo cáo số liệu chính xác từ NGỮ CẢNH HỆ THỐNG TRONG NGÀY khi được hỏi.
    4. Nếu không chắc chắn về ID sản phẩm/đơn hàng, hãy yêu cầu Admin cung cấp.
    5. Cố gắng sử dụng emoji tương ứng với bảng Admin (📊, 🛒, 👥, 📈).";

    // 3. Lấy lịch sử chat admin
    $history = db()->fetchAll("SELECT noi_dung, nguoi_gui FROM tin_nhan WHERE ma_phien = ? ORDER BY ngay_gui ASC LIMIT 20", [$ma_phien]);
    $contents = [];
    foreach ($history as $h) {
        $contents[] = [
            'role' => ($h['nguoi_gui'] === 'user' ? 'user' : 'model'),
            'parts' => [['text' => $h['noi_dung']]]
        ];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

    // 4. Gọi Gemini
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $payload = [
        'contents' => $contents,
        'system_instruction' => ['parts' => [['text' => $system_context]]]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Logging cho debug
    file_put_contents(__DIR__ . '/../../../tmp/admin_chat_debug.log', 
        "[" . date('Y-m-d H:i:s') . "] Admin Chat - Http: $http_code | Error: $error\n" .
        "Response: " . $response . "\n---\n", FILE_APPEND);

    if ($error) throw new Exception("Lỗi AI: " . $error);

    $resData = json_decode($response, true);
    $ai_message = $resData['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi không thể xử lý yêu cầu lúc này.';

    if (isset($resData['error'])) {
        throw new Exception("Lỗi AI: " . ($resData['error']['message'] ?? 'Unknown error'));
    }

    // 5. Lưu lịch sử
    db()->execute("INSERT INTO tin_nhan (ma_user, noi_dung, nguoi_gui, ma_phien) VALUES (?, ?, 'user', ?)", [$admin_user['ma_user'], $message, $ma_phien]);
    db()->execute("INSERT INTO tin_nhan (ma_user, noi_dung, nguoi_gui, ma_phien) VALUES (?, ?, 'ai', ?)", [$admin_user['ma_user'], $ai_message, $ma_phien]);

    echo json_encode([
        'success' => true,
        'message' => $ai_message,
        'ma_phien' => $ma_phien
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
