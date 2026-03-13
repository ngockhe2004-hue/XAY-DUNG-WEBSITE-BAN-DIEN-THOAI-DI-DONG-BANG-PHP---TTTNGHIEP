<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung trống']);
    exit;
}

try {
    // Lưu tin nhắn từ user
    db()->execute("INSERT INTO tin_nhan (ma_user, noi_dung, nguoi_gui) VALUES (?, ?, 'user')", [$user_id, $message]);
    
    // 1. Lấy dữ liệu sản phẩm nổi bật
    $featured = db()->fetchAll("SELECT ten_sanpham, gia_thap_nhat FROM v_sanpham_tongquan WHERE is_active = 1 AND is_noi_bat = 1 LIMIT 5");
    $featured_text = "";
    foreach($featured as $sp) {
        $featured_text .= "- {$sp['ten_sanpham']}: " . number_format($sp['gia_thap_nhat'], 0, ',', '.') . " VNĐ\n";
    }

    // 2. Lấy dữ liệu hàng mới về
    $new_arrivals = db()->fetchAll("SELECT ten_sanpham, gia_thap_nhat FROM v_sanpham_tongquan WHERE is_active = 1 AND is_hang_moi = 1 LIMIT 3");
    $new_text = "";
    foreach($new_arrivals as $sp) {
        $new_text .= "- {$sp['ten_sanpham']}: " . number_format($sp['gia_thap_nhat'], 0, ',', '.') . " VNĐ\n";
    }

    // 3. Lấy khuyến mãi đang chạy
    $coupons = db()->fetchAll("SELECT ma_code, ten_km, gia_tri_giam, kieu_giam FROM ma_khuyenmai WHERE is_active = 1 AND NOW() BETWEEN ngay_bat_dau AND ngay_ket_thuc LIMIT 3");
    $promo_text = "";
    foreach($coupons as $c) {
        $val = $c['kieu_giam'] == 'phan_tram' ? "{$c['gia_tri_giam']}%" : number_format($c['gia_tri_giam'], 0, ',', '.') . " VNĐ";
        $promo_text .= "- Mã {$c['ma_code']}: {$c['ten_km']} (Giảm {$val})\n";
    }

    // Build Prompt
    $system_context = "Bạn là trợ lý ảo thông minh của PhoneStore. Hãy sử dụng dữ liệu thực tế sau để tư vấn và tiếp thị cho khách hàng:\n\n";
    $system_context .= "SẢN PHẨM NỔI BẬT ĐANG BÁN:\n{$featured_text}\n";
    $system_context .= "HÀNG MỚI VỀ:\n{$new_text}\n";
    if (!empty($promo_text)) {
        $system_context .= "KHUYẾN MÃI ĐANG DIỄN RA:\n{$promo_text}\n";
    }
    $system_context .= "\nQuy tắc: Thân thiện, chuyên nghiệp, chủ động gợi ý sản phẩm phù hợp nếu khách hỏi. Trả lời ngắn gọn bằng tiếng Việt.";

    // Gọi Gemini API
    $api_key = GEMINI_API_KEY;
    $model = GEMINI_MODEL;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $system_context . "\n\nCâu hỏi khách hàng: " . $message]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Lỗi kết nối AI: " . $error);
    }

    $result_data = json_decode($response, true);
    $ai_response = $result_data['candidates'][0]['content']['parts'][0]['text'] ?? "Dạ, PhoneStore xin lỗi vì sự gián đoạn này. Bạn vui lòng thử lại sau nhé!";
    
    // Lưu phản hồi từ AI
    db()->execute("INSERT INTO tin_nhan (ma_user, noi_dung, nguoi_gui) VALUES (?, ?, 'ai')", [$user_id, $ai_response]);

    echo json_encode([
        'success' => true, 
        'ai_message' => $ai_response
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
