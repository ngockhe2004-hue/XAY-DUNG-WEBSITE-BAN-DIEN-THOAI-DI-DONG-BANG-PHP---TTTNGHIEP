<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Chỉ redirect nếu ADMIN đã đăng nhập (admin không cần login lại)
if (!empty($_SESSION['admin_site']['logged_in']) && _verifyNamespaceToken('admin_site')) {
    redirect(BASE_URL . '/admin/index.php');
}
// Lưu ý: KHAI User đã đăng nhập mà không redirect — để Admin có thể login song song.
// Nếu user đang login mà muốn về trang chủ họ có thể nhấn nút "← Về trang chủ".
$userAlreadyLoggedIn = !empty($_SESSION['user_site']['logged_in']) && _verifyNamespaceToken('user_site');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Lỗi xác thực bảo mật (CSRF). Vui lòng thử lại.';
    } else {
        $result = login(sanitize($_POST['username'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            $user = $result['user'];
            $defaultRedirect = BASE_URL . ($user['quyen'] === 'admin' ? '/admin/index.php' : '/index.php');
            redirect($_GET['redirect'] ?? $defaultRedirect);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;background:var(--bg-primary);">
    <div style="width:100%;max-width:900px;display:grid;grid-template-columns:1fr 1fr;gap:0;border-radius:var(--radius-xl);overflow:hidden;box-shadow:var(--shadow-lg);">
        
        <!-- Left Panel -->
        <div style="background:var(--gradient-main);padding:60px 40px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;">
            <div style="font-size:72px;margin-bottom:16px;">📱</div>
            <h2 style="color:#fff;font-size:28px;font-weight:800;margin-bottom:12px;">PhoneStore</h2>
            <p style="color:rgba(255,255,255,0.85);font-size:15px;line-height:1.7;">Điện thoại chính hãng<br>Giá tốt nhất thị trường<br>Bảo hành uy tín 12 tháng</p>
            <div style="margin-top:32px;display:flex;flex-direction:column;gap:10px;width:100%">
                <div style="background:rgba(255,255,255,0.15);border-radius:var(--radius-md);padding:12px;color:#fff;font-size:14px;">🏆 1000+ sản phẩm chính hãng</div>
                <div style="background:rgba(255,255,255,0.15);border-radius:var(--radius-md);padding:12px;color:#fff;font-size:14px;">🚚 Giao hàng trong 2-4 giờ</div>
                <div style="background:rgba(255,255,255,0.15);border-radius:var(--radius-md);padding:12px;color:#fff;font-size:14px;">🔄 Đổi trả miễn phí 30 ngày</div>
            </div>
        </div>
        
        <!-- Right Panel - Form -->
        <div style="background:var(--bg-card);padding:60px 40px;">
            <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted);font-size:13px;display:block;margin-bottom:32px;">← Về trang chủ</a>
            <h1 style="font-size:28px;font-weight:800;margin-bottom:8px;">Đăng Nhập</h1>
            <p style="color:var(--text-secondary);margin-bottom:32px;font-size:14px;">Chào mừng trở lại! Đăng nhập để tiếp tục mua sắm.</p>
            
            <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:var(--danger);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:14px;">
                ⚠️ <?= sanitize($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <?php csrfInput(); ?>
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập hoặc Email</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Nhập tên đăng nhập..." 
                           value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>" 
                           required autocomplete="username" id="username">
                </div>
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <label class="form-label" style="margin:0">Mật khẩu</label>
                        <a href="<?= BASE_URL ?>/forgot-password.php" style="font-size:13px;color:var(--accent)">Quên mật khẩu?</a>
                    </div>
                    <div style="position:relative;">
                        <input type="password" name="password" class="form-control" 
                               placeholder="Nhập mật khẩu..." required id="password"
                               style="padding-right:50px;">
                        <button type="button" onclick="togglePwd()" 
                                style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:18px;" id="eyeBtn">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;padding:14px;">
                    Đăng Nhập →
                </button>
            </form>
            
            <div style="text-align:center;margin-top:28px;padding-top:24px;border-top:1px solid var(--border);">
                <p style="color:var(--text-secondary);font-size:14px;">
                    Chưa có tài khoản? 
                    <a href="<?= BASE_URL ?>/register.php" style="color:var(--accent);font-weight:600;">Đăng ký ngay</a>
                </p>
            </div>
            
            <!-- Demo credentials hint -->
            <div style="margin-top:20px;padding:14px;background:var(--bg-secondary);border-radius:var(--radius-md);font-size:13px;color:var(--text-muted);">
                <div style="font-weight:600;color:var(--text-secondary);margin-bottom:6px;">💡 Tài khoản demo:</div>
                <!-- <div>Admin: <code style="color:var(--accent)">admin</code> / <code style="color:var(--accent)">Admin@123</code></div> -->
            </div>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const p = document.getElementById('password');
    const b = document.getElementById('eyeBtn');
    if (p.type === 'password') { p.type = 'text'; b.textContent = '🙈'; }
    else { p.type = 'password'; b.textContent = '👁'; }
}
</script>
</body>
</html>
