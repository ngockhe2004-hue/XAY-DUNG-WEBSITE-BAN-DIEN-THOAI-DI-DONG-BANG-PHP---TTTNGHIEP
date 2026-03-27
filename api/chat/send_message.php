<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$ma_phien = $data['ma_phien'] ?? null;
$user_id = isLoggedIn() ? $_SESSION['user_site']['id'] : null;

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung trống']);
    exit;
}

try {
    $current_url = $data['current_url'] ?? '';
    $page_title = $data['page_title'] ?? '';
    $product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? intval($data['product_id']) : null;

    // Lưu tin nhắn từ user
    db()->execute("INSERT INTO tin_nhan (ma_user, noi_dung, nguoi_gui, ma_phien) VALUES (?, ?, 'user', ?)", [$user_id, $message, $ma_phien]);

    // 1. Lấy dữ liệu sản phẩm nổi bật
    $featured = db()->fetchAll("
        SELECT sp.ma_sanpham, sp.ten_sanpham, MIN(bt.gia) as gia_min
        FROM sanpham sp
        JOIN bienthe_sanpham bt ON sp.ma_sanpham = bt.ma_sanpham
        WHERE sp.is_active = 1 AND sp.is_noi_bat = 1 AND bt.is_active = 1
        GROUP BY sp.ma_sanpham
        LIMIT 5
    ");
    $featured_text = "";
    foreach($featured as $sp) {
        $url = BASE_URL . "/product_detail.php?id=" . $sp['ma_sanpham'];
        $featured_text .= "- [{$sp['ten_sanpham']}]({$url}): " . number_format($sp['gia_min'], 0, ',', '.') . " VNĐ\n";
    }

    // 1.5. Lấy danh sách sản phẩm (Giới hạn 30 để tránh tràn Context/Timeout)
    $all_products = db()->fetchAll("
        SELECT sp.ma_sanpham, sp.ten_sanpham, MIN(b.gia) as gia_min 
        FROM sanpham sp 
        JOIN bienthe_sanpham b ON sp.ma_sanpham = b.ma_sanpham 
        WHERE sp.is_active = 1 AND b.is_active = 1
        GROUP BY sp.ma_sanpham
        ORDER BY sp.ma_sanpham DESC
        LIMIT 30
    ");
    $catalog_text = "";
    foreach($all_products as $p) {
        $catalog_text .= "- ID {$p['ma_sanpham']}: {$p['ten_sanpham']} (Từ " . number_format($p['gia_min'], 0, ',', '.') . "đ)\n";
    }

    // 2. Lấy dữ liệu Giỏ hàng, Yêu thích & Đơn hàng (Nếu đã đăng nhập)
    $cart_info = "";
    $wishlist_info = "";
    $order_info = "";
    if ($user_id) {
        $cart_items = db()->fetchAll("
            SELECT sp.ten_sanpham, bt.mau_sac, gh.so_luong 
            FROM chitiet_giohang gh 
            JOIN giohang g ON gh.ma_gio = g.ma_gio
            JOIN bienthe_sanpham bt ON gh.ma_bienthe = bt.ma_bienthe 
            JOIN sanpham sp ON bt.ma_sanpham = sp.ma_sanpham 
            WHERE g.ma_user = ?", [$user_id]);
        foreach($cart_items as $item) $cart_info .= "- {$item['ten_sanpham']} ({$item['mau_sac']}) x{$item['so_luong']}\n";

        $wish_items = db()->fetchAll("
            SELECT sp.ten_sanpham FROM dsyeuthich yt 
            JOIN sanpham sp ON yt.ma_sanpham = sp.ma_sanpham 
            WHERE yt.ma_user = ?", [$user_id]);
        foreach($wish_items as $item) $wishlist_info .= "- {$item['ten_sanpham']}\n";

        $orders = db()->fetchAll("
            SELECT ma_donhang, tong_thanh_toan, DATE_FORMAT(ngay_dat, '%d/%m/%Y') as ngay_dat_fmt
            FROM donhang 
            WHERE ma_user = ? AND trang_thai = 'cho_xac_nhan'
            ORDER BY ma_donhang DESC LIMIT 5
        ", [$user_id]);
        foreach($orders as $o) $order_info .= "- Đơn #{$o['ma_donhang']}: " . number_format($o['tong_thanh_toan'], 0, ',', '.') . "đ (Ngày {$o['ngay_dat_fmt']})\n";
    }

    // 3. Lấy khuyến mãi
    $coupons = db()->fetchAll("SELECT ma_code, ten_km, gia_tri_giam, kieu_giam FROM ma_khuyenmai WHERE is_active = 1 AND NOW() BETWEEN ngay_bat_dau AND ngay_ket_thuc LIMIT 3");
    $promo_text = "";
    foreach($coupons as $c) {
        $val = $c['kieu_giam'] == 'phan_tram' ? "{$c['gia_tri_giam']}%" : number_format($c['gia_tri_giam'], 0, ',', '.') . " VNĐ";
        $promo_text .= "- Mã {$c['ma_code']}: {$c['ten_km']} (Giảm {$val})\n";
    }

    $product_context = "";
    if ($product_id) {
        $sp_hien_tai = db()->fetch("SELECT ten_sanpham, mo_ta FROM sanpham WHERE ma_sanpham = ?", [$product_id]);
        if ($sp_hien_tai) {
            $product_context = "- SẢN PHẨM KHÁCH ĐANG XEM CHI TIẾT: {$sp_hien_tai['ten_sanpham']}\n";
            // Rút gọn mô tả để tránh vượt quá token
            $mo_ta_ngan = substr(strip_tags($sp_hien_tai['mo_ta']), 0, 500); 
            $product_context .= "  + Cấu hình/Mô tả: {$mo_ta_ngan}...\n";
            
            $gia_min = db()->fetch("SELECT MIN(gia) as gia_min FROM bienthe_sanpham WHERE ma_sanpham = ?", [$product_id]);
            if ($gia_min && $gia_min['gia_min']) {
                $product_context .= "  + Giá từ: " . number_format($gia_min['gia_min'], 0, ',', '.') . " VNĐ\n";
            }
        }
    }

    // Persona & Intelligence prompt
    $system_context = "Bạn là PhoneStore Copilot - Trợ lý Ảo kiêm 'người bạn đồng hành' của khách hàng. Phong cách giao tiếp của bạn: Linh hoạt, chân thành, thân thiện (có thể xưng 'mình/bạn' và dùng emoji 😊) khi trò chuyện phiếm. Khi tư vấn chuyên sâu hoặc thực thi Mệnh Lệnh thao tác thì cực kỳ chuyên nghiệp và tối ưu.\n\n";

    // --- BRAND STORY VÀ KHUYẾN MÃI (Khai báo triết lý kinh doanh cho AI) ---
    $system_context .= "### TRIẾT LÝ KINH DOANH VÀ CÂU CHUYỆN THƯƠNG HIỆU (LUÔN GHI NHỚ):\n";
    $system_context .= "Với tư cách là đại diện của PhoneStore, bạn truyền tải thông điệp: 'Chúng tôi không chỉ bán điện thoại, chúng tôi cung cấp công cụ để bạn viết nên câu chuyện của chính mình.'\n";
    $system_context .= "Nếu khách bắt đầu trò chuyện hoặc hỏi về sản phẩm mới/ưu đãi (Ví dụ: 'Siêu ưu đãi hôm nay', 'Sản phẩm mới nhất'), THỈNH THOẢNG bạn có thể mượn câu chuyện 'Sự kết nối vượt thời gian' để kể cho khách nghe (Ví dụ: Chuyện về người thợ chụp ảnh già dùng điện thoại như iPhone 16 Pro Max/ S25 Ultra để kết nối với người thân nửa vòng trái đất...), tạo sự đồng cảm trước khi đưa ra danh sách sản phẩm.\n\n";

    $system_context .= "### MÁCH NHỎ ƯU ĐÃI (Mã Giảm Giá) ĐỂ MỜI KHÁCH:\n";
    $system_context .= "Khi khách hàng hỏi về khuyến mãi, hoặc sau khi bạn giới thiệu xong điện thoại flagship (như iPhone 16 Pro Max hay Samsung S25 Ultra), hãy nhắc khách áp dụng các mã giảm giá sau tại bước Thanh toán:\n";
    $system_context .= "- **WELCOME10**: Giảm ngay 10% nếu là thành viên mới.\n";
    $system_context .= "- **PHONE20**: Giảm 20% (tối đa 2 triệu VNĐ) – rất phù hợp cho dòng cao cấp đỉnh cao.\n";
    $system_context .= "- **SALE50K**: Giảm ngay 50.000 VNĐ cho mọi đơn hàng.\n\n";
    
    $login_status = $user_id ? "Đã đăng nhập (Mã Khách Hàng: {$user_id})" : "Khách vãng lai (CHƯA ĐĂNG NHẬP)";
    
    $system_context .= "NGỮ CẢNH HIỆN TẠI CỦA KHÁCH HÀNG:\n";
    $system_context .= "- Trạng thái đăng nhập: {$login_status}\n";
    $system_context .= "- Khách đang thao tác trên trang: \"{$page_title}\" (URL: {$current_url})\n";
    if (!empty($product_context)) $system_context .= $product_context;
    if (!empty($cart_info)) $system_context .= "- Giỏ hàng của khách chứa:\n{$cart_info}";
    if (!empty($wishlist_info)) $system_context .= "- Danh sách yêu thích của khách:\n{$wishlist_info}";
    if (!empty($order_info)) $system_context .= "- Đơn hàng đang chờ xác nhận (Có thể hủy):\n{$order_info}";
    
    $system_context .= "\nDỮ LIỆU CỬA HÀNG:\n";
    $system_context .= "SẢN PHẨM GỢI Ý (Kèm link chi tiết):\n{$featured_text}\n";
    $system_context .= "DANH MỤC TOÀN BỘ SẢN PHẨM ĐANG BÁN:\n{$catalog_text}\n";
    if (!empty($promo_text)) $system_context .= "KHUYẾN MÃI:\n{$promo_text}\n";

    $system_context .= "\nQUY TẮC PHẢN HỒI NÂNG CAO:\n";
    $system_context .= "1. TRÒ CHUYỆN NHƯ NGƯỜI BẠN MỚI: Nếu khách muốn tâm sự, hỏi thăm, đùa cợt hoặc nói những câu bông đùa, hãy hùa theo và đáp lại thật tự nhiên, hài hước. Khách hàng là bạn bè. Tự động đề nghị: 'Bạn có muốn tôi hỗ trợ so sánh kỹ hơn về camera giữa iPhone 16 Pro Max và Samsung Galaxy S25 Ultra để xem chiếc nào phù hợp với gu thẩm mỹ của bạn hơn không?'.\n";
    $system_context .= "2. TƯ VẤN NGỮ CẢNH TỐT NHẤT: Nếu khách bắt đầu hỏi về điện thoại hoặc công nghệ, chuyển sang chế độ Chuyên gia vui vẻ. Khai thác ngân sách và nhu cầu của khách thay vì quảng cáo suông.\n";
    $system_context .= "3. TRÌNH BÀY BẢNG: Dùng bảng Markdown khi liệt kê trên 2 sản phẩm (Tên, Giá, Link) để dễ nhìn.\n";
    $system_context .= "4. GIAO TIẾP VÀO TRỌNG TÂM KHI 'THỰC THI LỆNH': CHỈ KHI khách yêu cầu thực thi Action (Như: Hủy Đơn, Thêm/Xóa Giỏ Hàng), bạn mới bật chế độ RÚT GỌN TỐI ĐA (Robot Mode) để tránh rườm rà dài dòng mất thời gian của khách.\n";
    $system_context .= "   - Lỗi CẦN TRÁNH khi lệnh Hủy: \"Chào bạn, thật buồn khi bạn đổi ý. Để hủy đơn, phiền bạn đọc giúp mình mã...\"\n";
    $system_context .= "   - Cách nói ĐÚNG khi lệnh Hủy: \"Bạn cho mình xin Mã đơn hàng để mình thao tác hủy luôn nhé.\"\n";
    $system_context .= "   - Cách chốt khi đã Click: \"Mình đã gửi lệnh hủy đơn #{Mã} cho bạn rồi nhé. Xong!\"\n";
    
    $system_context .= "\nQUY TẮC XÁC NHẬN (BẮT BUỘC LOGIC):\n";
    $system_context .= "1. LÀM RÕ TRƯỚC, XÁC NHẬN SAU: Nếu yêu cầu của khách là lộn xộn, bị thiếu thông tin hoặc bạn CẦN HỎI LẠI (Ví dụ: 'Bạn muốn hủy mã đơn hàng nào?'), TUYỆT ĐỐI CHỈ RA CÂU HỎI VĂN BẢN THÔNG THƯỜNG. Không được chèn bất kỳ thẻ [[ACTION: ...]] nào.\n";
    $system_context .= "2. KIỂM TRA QUYỀN: Các tính năng Xóa Giỏ hàng (CLEAR_CART), Thêm Yêu Thích (ADD_WISHLIST), Phân tích Đơn mua và Hủy đơn (CANCEL_ORDER) CHỈ hoạt động khi Khách \"Đã đăng nhập\". Nếu Khách \"CHƯA ĐĂNG NHẬP\", hãy từ chối lịch sự và KHÔNG XUẤT THẺ ACTION.\n";
    $system_context .= "3. CHỈ HIỆN NÚT KHI ĐÃ SẴN SÀNG: Chỉ khi bạn đã làm rõ xong, có ĐỦ tham số, và KHÔNG CẦN HỎI THÊM GÌ NỮA, lúc đó MỚI ĐƯỢC chèn thẻ [[ACTION: ..., \"confirm\": true]] để khách bấm nút.\n";
    $system_context .= "4. THÔNG TIN RÕ RÀNG: Hãy tóm tắt rõ hành động sắp làm (ví dụ: 'Để hủy đơn hàng #456, bạn vui lòng nhấn xác nhận phía dưới.') rồi mới xuất thẻ Action.\n";
    
    $system_context .= "\nHÀNH ĐỘNG TỰ CHỦ (AUTONOMOUS ACTIONS):\n";
    $system_context .= "Bạn thực hiện các thao tác thay cho khách hàng bằng cách chèn một chuỗi JSON ẩn vào cuối câu trả lời.\n";
    $system_context .= "Quy định cú pháp: [[ACTION: {\"type\": \"TÊN_ACTION\", \"payload\": {dữ liệu}, \"confirm\": true/false}]]\n";
    $system_context .= "DANH SÁCH CÁC ACTION ĐƯỢC PHÉP:\n";
    $system_context .= "- Thêm vào giỏ hàng: [[ACTION: {\"type\": \"ADD_CART\", \"payload\": {\"id\": <ma_sanpham>, \"qty\": <số lượng>}, \"confirm\": false}]]\n";
    $system_context .= "- Xóa toàn bộ giỏ hàng (Cần XNG): [[ACTION: {\"type\": \"CLEAR_CART\", \"payload\": {}, \"confirm\": true}]]\n";
    $system_context .= "- Thêm/Xóa khỏi danh sách Yêu thích: [[ACTION: {\"type\": \"ADD_WISHLIST\", \"payload\": {\"id\": <ma_sanpham>}, \"confirm\": false}]]\n";
    $system_context .= "- Chuyển đến trang Giỏ hàng: [[ACTION: {\"type\": \"REDIRECT\", \"payload\": {\"url\": \"cart.php\"}, \"confirm\": false}]]\n";
    $system_context .= "- Chuyển đến trang Thanh toán / Đặt hàng: [[ACTION: {\"type\": \"REDIRECT\", \"payload\": {\"url\": \"checkout.php\"}, \"confirm\": false}]]\n";
    $system_context .= "- Chuyển đến trang Quản lý Địa Chỉ Giao Hàng: [[ACTION: {\"type\": \"REDIRECT\", \"payload\": {\"url\": \"profile.php?tab=addresses\"}, \"confirm\": false}]]\n";
    $system_context .= "- Phân tích/Xem lại Đơn hàng đã mua: [[ACTION: {\"type\": \"REDIRECT\", \"payload\": {\"url\": \"profile.php?tab=orders\"}, \"confirm\": false}]]\n";
    $system_context .= "- Hủy MỘT đơn hàng (QUAN TRỌNG, yêu cầu xác nhận): [[ACTION: {\"type\": \"CANCEL_ORDER\", \"payload\": {\"order_id\": \"MÃ_ĐƠN\"}, \"confirm\": true}]]\n";
    $system_context .= "- Hủy TẤT CẢ đơn hàng chờ xác nhận (QUAN TRỌNG): [[ACTION: {\"type\": \"CANCEL_ALL_ORDERS\", \"payload\": {}, \"confirm\": true}]]\n";
    $system_context .= "LƯU Ý: Những hành động xóa dữ liệu hoặc liên quan đến thanh toán, Hủy đơn (CANCEL_ORDER, CANCEL_ALL_ORDERS, CLEAR_CART), BẮT BUỘC gửi \"confirm\": true để hiện nút bấm!\n";
    
    // 4. Lấy lịch sử trò chuyện (Memory) - 10 tin nhắn gần nhất
    $history = [];
    if ($ma_phien) {
        $msgs = db()->fetchAll("SELECT noi_dung, nguoi_gui FROM tin_nhan WHERE ma_phien = ? ORDER BY ma_tin_nhan DESC LIMIT 10", [$ma_phien]);
        // Đảo ngược thứ tự để đúng dòng thời gian (cũ -> mới)
        $history = array_reverse($msgs);
    }

    // 5. Chuẩn bị Payload cho Gemini
    $contents = [];
    
    // Gửi System Context như một tin nhắn đầu tiên từ "user" nhưng mang tính chất chỉ dẫn
    $contents[] = [
        "role" => "user",
        "parts" => [["text" => "Dưới đây là Ngữ cảnh hệ thống và Quy tắc làm việc của bạn (BẮT BUỘC TUÂN THỦ):\n" . $system_context]]
    ];
    $contents[] = [
        "role" => "model",
        "parts" => [["text" => "Tôi đã hiểu Ngữ cảnh và các Quy tắc. Tôi đã sẵn sàng hỗ trợ khách hàng theo phong cách chuyên nghiệp, ngắn gọn và thực thi Action khi cần thiết."]]
    ];

    // Gửi lịch sử trò chuyện
    foreach ($history as $h) {
        $role = ($h['nguoi_gui'] === 'user') ? 'user' : 'model';
        $contents[] = [
            "role" => $role,
            "parts" => [["text" => $h['noi_dung']]]
        ];
    }

    // Gửi tin nhắn hiện tại (Nếu tin nhắn này chưa có trong db lịch sử lấy ra)
    // Thực tế nó đã được INSERT ở đầu file nên cẩn thận kẻo lặp
    // Để an toàn, ta chỉ lấy lịch sử TRƯỚC tin nhắn hiện tại.
    // Hoặc đơn giản là không gửi tin hiện tại vào Contents vì nó đã có trong lịch sử nếu db lưu xong.
    // Cách chuẩn nhất: Kiểm tra tin nhắn cuối cùng trong lịch sử có trùng với tin nhắn hiện tại không.

    // 6. Gọi Gemini API
    $api_key = GEMINI_API_KEY;
    $model = GEMINI_MODEL;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    $payload = ["contents" => $contents];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Lỗi kết nối AI: " . $error);
    }

    $result_data = json_decode($response, true);
    $ai_response = $result_data['candidates'][0]['content']['parts'][0]['text'] ?? "Dạ, PhoneStore xin lỗi vì sự gián đoạn này. Bạn vui lòng thử lại sau nhé!";
    
    // Lưu phản hồi từ AI
    db()->execute("INSERT INTO tin_nhan (ma_user, noi_dung, nguoi_gui, ma_phien) VALUES (?, ?, 'ai', ?)", [$user_id, $ai_response, $ma_phien]);

    echo json_encode([
        'success' => true, 
        'ai_message' => $ai_response
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
