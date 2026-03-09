<?php
// ============================================================
// AUTH FUNCTIONS - Token Validation
// Mỗi namespace (user_site / admin_site) có một token riêng
// được lưu song song trong $_SESSION và trong cookie.
// Khi load trang → so khớp token → không khớp → logout namespace đó
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ============================================================
// HELPER NỘI BỘ
// ============================================================
function _isAdminPage(): bool {
    return strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false
        || strpos($_SERVER['SCRIPT_NAME'], '/admin\\') !== false;
}

function _generateToken(): string {
    return bin2hex(random_bytes(32));
}

/** Đặt token vào session + cookie cho namespace chỉ định */
function _setNamespaceToken(string $ns, string $token): void {
    $_SESSION[$ns]['token'] = $token;
    $cookieName = 'tk_' . $ns; // ví dụ: tk_user_site, tk_admin_site
    setcookie($cookieName, $token, [
        'expires'  => 0,            // hết hạn khi đóng browser
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Xác minh token của namespace. Trả về false nếu không khớp → xóa namespace */
function _verifyNamespaceToken(string $ns): bool {
    if (empty($_SESSION[$ns]['logged_in'])) return false;

    $cookieName   = 'tk_' . $ns;
    $sessionToken = $_SESSION[$ns]['token'] ?? '';
    $cookieToken  = $_COOKIE[$cookieName] ?? '';

    if (!$sessionToken || !$cookieToken || !hash_equals($sessionToken, $cookieToken)) {
        // Token không khớp → xóa namespace để ép logout
        unset($_SESSION[$ns]);
        return false;
    }
    return true;
}

// ============================================================
// LOGIN / REGISTER / LOGOUT
// ============================================================
function login(string $username, string $password): array {
    $user = db()->fetchOne(
        "SELECT * FROM users WHERE (ten_user = ? OR email = ?) AND trang_thai != 'banned'",
        [$username, $username]
    );
    if (!$user)
        return ['success' => false, 'message' => 'Tài khoản không tồn tại hoặc đã bị khóa'];
    if ($user['trang_thai'] === 'inactive')
        return ['success' => false, 'message' => 'Tài khoản đã bị vô hiệu hóa'];
    if (!password_verify($password, $user['password_hash']))
        return ['success' => false, 'message' => 'Mật khẩu không đúng'];

    $ns    = $user['quyen'] === 'admin' ? 'admin_site' : 'user_site';
    $token = _generateToken();

    $_SESSION[$ns] = [
        'id'        => $user['ma_user'],
        'username'  => $user['ten_user'],
        'email'     => $user['email'],
        'hovaten'   => $user['hovaten'],
        'quyen'     => $user['quyen'],
        'logged_in' => true,
        'token'     => $token,
    ];

    _setNamespaceToken($ns, $token); // lưu token vào cookie riêng

    return ['success' => true, 'user' => $user];
}

function register($data): array {
    if (empty($data['ten_user']) || empty($data['email']) || empty($data['password']))
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
    if (strlen($data['password']) < 6)
        return ['success' => false, 'message' => 'Mật khẩu phải ít nhất 6 ký tự'];
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'message' => 'Email không hợp lệ'];

    $exists = db()->fetchOne("SELECT ma_user FROM users WHERE ten_user = ? OR email = ?",
        [$data['ten_user'], $data['email']]);
    if ($exists)
        return ['success' => false, 'message' => 'Tên đăng nhập hoặc email đã được sử dụng'];

    $id = db()->insert(
        "INSERT INTO users (ten_user, email, password_hash, hovaten, SDT, dia_chi) VALUES (?,?,?,?,?,?)",
        [sanitize($data['ten_user']), sanitize($data['email']),
         password_hash($data['password'], PASSWORD_BCRYPT),
         sanitize($data['hovaten'] ?? ''), sanitize($data['sdt'] ?? ''),
         sanitize($data['dia_chi'] ?? '')]
    );

    // Nếu đăng ký thành công và có địa chỉ, tự động tạo một bản ghi địa chỉ mặc định trong diachi_user
    if ($id && !empty($data['dia_chi'])) {
        db()->insert(
            "INSERT INTO diachi_user (ma_user, ho_ten_nguoinhan, SDT_nguoinhan, tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the, la_macdinh) 
             VALUES (?,?,?,?,?,?,?,?)",
            [$id, sanitize($data['hovaten'] ?? $data['ten_user']), sanitize($data['sdt'] ?? ''), 'Chưa xác định', 'Chưa xác định', 'Chưa xác định', sanitize($data['dia_chi']), 1]
        );
    }

    return $id ? ['success' => true, 'user_id' => $id]
               : ['success' => false, 'message' => 'Đăng ký thất bại'];
}

function logout(): void {
    $ns = _isAdminPage() ? 'admin_site' : 'user_site';
    unset($_SESSION[$ns]);
    // Hủy cookie token của namespace này
    setcookie('tk_' . $ns, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    redirect(BASE_URL . '/login.php');
}

// ============================================================
// KIỂM TRA QUYỀN (có token validation)
// ============================================================
function isLoggedIn(): bool {
    $ns = _isAdminPage() ? 'admin_site' : 'user_site';
    return _verifyNamespaceToken($ns);
}

function isAdmin(): bool {
    return _verifyNamespaceToken('admin_site');
}

function isCustomer(): bool {
    return _verifyNamespaceToken('user_site') &&
           ($_SESSION['user_site']['quyen'] ?? '') === 'customer';
}

function requireLogin($redirect = null): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Vui lòng đăng nhập để tiếp tục');
        redirect(BASE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        redirect(BASE_URL . '/login.php');
    }
}

// ============================================================
// THÔNG TIN USER
// ============================================================
function getCurrentUser(): ?array {
    $ns = _isAdminPage() ? 'admin_site' : 'user_site';
    if (!_verifyNamespaceToken($ns)) return null;
    $id = $_SESSION[$ns]['id'] ?? null;
    if (!$id) return null;
    return db()->fetchOne("SELECT * FROM users WHERE ma_user = ?", [$id]);
}

function updatePassword($userId, $oldPass, $newPass): array {
    $user = db()->fetchOne("SELECT password_hash FROM users WHERE ma_user = ?", [$userId]);
    if (!$user || !password_verify($oldPass, $user['password_hash']))
        return ['success' => false, 'message' => 'Mật khẩu cũ không đúng'];
    db()->execute("UPDATE users SET password_hash = ? WHERE ma_user = ?",
        [password_hash($newPass, PASSWORD_BCRYPT), $userId]);
    return ['success' => true];
}

// ============================================================
// CART HELPERS (dùng user_site)
// ============================================================
function getCartCount(): int {
    if (!_verifyNamespaceToken('user_site')) return 0;
    $uid = $_SESSION['user_site']['id'] ?? null;
    if (!$uid) return 0;
    $gio = db()->fetchOne("SELECT ma_gio FROM giohang WHERE ma_user = ?", [$uid]);
    if (!$gio) return 0;
    return (int) db()->fetchColumn("SELECT COALESCE(SUM(so_luong),0) FROM chitiet_giohang WHERE ma_gio = ?", [$gio['ma_gio']]);
}

function getOrCreateCart() {
    // isLoggedIn() đã xác thực token trước khi gọi hàm này
    $uid = $_SESSION['user_site']['id'] ?? null;
    if (!$uid) return null;
    $gio = db()->fetchOne("SELECT ma_gio FROM giohang WHERE ma_user = ?", [$uid]);
    if (!$gio) return db()->insert("INSERT INTO giohang (ma_user) VALUES (?)", [$uid]);
    return $gio['ma_gio'];
}
