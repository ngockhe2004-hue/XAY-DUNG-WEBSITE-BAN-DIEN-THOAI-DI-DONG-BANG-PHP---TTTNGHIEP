<?php
ob_start(); // Bật output buffering để tránh lỗi headers already sent
// ============================================================
// CONFIG CHUNG - WEBSITE BÁN ĐIỆN THOẠI
// ============================================================

define('SITE_NAME', 'Phonestore - Điện Thoại Chính Hãng');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'];
$base_url = $protocol . "://" . $domain . "/website%20bandienthoai";
define('BASE_URL', $base_url);
define('ADMIN_EMAIL', 'admin@bandienthoai.vn');

// Load .env (Simple implementation)
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv(__DIR__ . '/../.env');

// Gemini AI Settings
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');
define('GEMINI_MODEL', $_ENV['GEMINI_MODEL'] ?? 'gemini-1.5-flash');

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', BASE_URL . '/uploads/products/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 15);
define('ADMIN_PER_PAGE', 20);

// Session - MỘT session duy nhất cho cả admin lẫn user
// Dữ liệu admin/user phân biệt bằng key prefix trong $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_name('ps_session');
    session_start();
}


// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Helper: Format tiền VNĐ
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' VND';
}

// Helper: Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper: Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// Helper: Flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Generate slug
function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $trans = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
        'ă'=>'a','ắ'=>'a','ặ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'đ'=>'d',
        'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
        'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o',
        'ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
        'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];
    $string = strtr($string, $trans);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Tạo slug duy nhất trong một bảng
 * @param string $table Tên bảng (sanpham, danhmuc, ...)
 * @param string $string Chuỗi gốc (tên sản phẩm, tên danh mục)
 * @param string $idColumn Tên cột ID để loại trừ bản ghi hiện tại khi update
 * @param int|null $idValue Giá trị ID để loại trừ
 * @return string Slug duy nhất
 */
function getUniqueSlug($table, $string, $idColumn = null, $idValue = null) {
    $slug = generateSlug($string);
    $originalSlug = $slug;
    $count = 1;

    while (true) {
        $sql = "SELECT COUNT(*) FROM $table WHERE slug = ?";
        $params = [$slug];

        if ($idColumn && $idValue) {
            $sql .= " AND $idColumn != ?";
            $params[] = $idValue;
        }

        $exists = db()->fetchColumn($sql, $params);

        if (!$exists) {
            return $slug;
        }

        $slug = $originalSlug . '-' . $count;
        $count++;
    }
}

// Helper: Generate order code
function generateOrderCode() {
    return 'DH' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// Helper: Paginate
function paginate($totalItems, $currentPage, $perPage) {
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

// Helper: Star rating HTML
function renderStars($rating, $max = 5) {
    $html = '<span class="stars">';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="star filled">★</i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="star half">★</i>';
        } else {
            $html .= '<i class="star empty">☆</i>';
        }
    }
    $html .= '</span>';
    return $html;
}

// Helper: Time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' năm trước';
    if ($diff->m > 0) return $diff->m . ' tháng trước';
    if ($diff->d > 0) return $diff->d . ' ngày trước';
    if ($diff->h > 0) return $diff->h . ' giờ trước';
    if ($diff->i > 0) return $diff->i . ' phút trước';
    return 'Vừa xong';
}
