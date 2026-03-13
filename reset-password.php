<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$user = verifyResetToken($token);

$error = '';
$success = '';

if (!$user) {
    $error = 'Liên kết không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Mật khẩu phải chứa ít nhất 6 ký tự';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        $result = resetPasswordWithToken($token, $password);
        if ($result['success']) {
            $success = 'Đổi mật khẩu thành công! Bạn có thể đăng nhập ngay bây giờ.';
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
    <title>Đặt Lại Mật Khẩu | <?= SITE_NAME ?></title>
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
            <h1 style="font-size:28px;font-weight:800;margin-bottom:8px;">Đặt Lại Mật Khẩu</h1>
            <p style="color:var(--text-secondary);margin-bottom:32px;font-size:14px;">Vui lòng nhập mật khẩu mới cho tài khoản của bạn.</p>
            
            <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:var(--danger);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:14px;">
                ⚠️ <?= sanitize($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:var(--success);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:14px;">
                ✅ <?= sanitize($success) ?>
            </div>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-block" style="text-align:center;text-decoration:none;display:block;padding:14px;">
                Đến trang Đăng Nhập →
            </a>
            <?php elseif ($user): ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Nhập mật khẩu mới..." required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control" 
                           placeholder="Nhập lại mật khẩu mới..." required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;padding:14px;">
                    Cập Nhật Mật Khẩu →
                </button>
            </form>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/forgot-password.php" class="btn btn-primary btn-block" style="text-align:center;text-decoration:none;display:block;padding:14px;">
                Yêu cầu lại liên kết →
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
