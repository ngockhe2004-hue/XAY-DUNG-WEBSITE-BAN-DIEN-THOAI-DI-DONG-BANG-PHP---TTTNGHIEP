<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'Vui lòng nhập địa chỉ email';
    } else {
        $token = generateResetToken($email);
        if ($token) {
            $resetLink = BASE_URL . "/reset-password.php?token=" . $token;
            // Trong thực tế sẽ gửi email ở đây. Demo thì hiển thị link.
            $success = "Yêu cầu đã được gửi. Vui lòng kiểm tra email của bạn (Demo: xem link bên dưới).";
        } else {
            $error = 'Email không tồn tại trong hệ thống';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu | <?= SITE_NAME ?></title>
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
            <a href="<?= BASE_URL ?>/login.php" style="color:var(--text-muted);font-size:13px;display:block;margin-bottom:32px;">← Quay lại đăng nhập</a>
            <h1 style="font-size:28px;font-weight:800;margin-bottom:8px;">Quên Mật Khẩu</h1>
            <p style="color:var(--text-secondary);margin-bottom:32px;font-size:14px;">Nhập email của bạn để nhận liên kết đặt lại mật khẩu.</p>
            
            <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:var(--danger);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:14px;">
                ⚠️ <?= sanitize($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:var(--success);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:14px;">
                ✅ <?= sanitize($success) ?>
            </div>
            <?php if ($resetLink): ?>
                <div style="margin-top:10px;padding:14px;background:var(--bg-secondary);border-radius:var(--radius-md);font-size:13px;word-break:break-all;">
                    <div style="font-weight:600;color:var(--text-secondary);margin-bottom:6px;">🔗 Link đặt lại mật khẩu (Demo):</div>
                    <a href="<?= $resetLink ?>" style="color:var(--accent)"><?= $resetLink ?></a>
                </div>
            <?php endif; ?>
            <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="example@gmail.com" 
                           value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>" 
                           required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;padding:14px;">
                    Gửi Yêu Cầu →
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
