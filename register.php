<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = register([
        'ten_user' => $_POST['ten_user'] ?? '',
        'email'    => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'hovaten'  => $_POST['hovaten'] ?? '',
        'sdt'      => $_POST['sdt'] ?? '',
        'dia_chi'  => $_POST['dia_chi'] ?? '',
    ]);
    if ($result['success']) {
        // Auto login
        login($_POST['ten_user'], $_POST['password']);
        setFlash('success', '🎉 Đăng ký thành công! Chào mừng bạn đến PhoneStore!');
        redirect(BASE_URL . '/index.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;background:var(--bg-primary);">
    <div class="form-card" style="max-width:520px;">
        <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted);font-size:13px;display:block;margin-bottom:24px;">← Về trang chủ</a>
        <div style="text-align:center;margin-bottom:28px;">
            <div style="font-size:48px;margin-bottom:8px;">📱</div>
            <h1 class="form-title">Tạo Tài Khoản</h1>
            <p class="form-subtitle">Đăng ký để bắt đầu mua sắm tại PhoneStore</p>
        </div>
        
        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:var(--danger);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:14px;">
            ⚠️ <?= sanitize($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm" novalidate>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập *</label>
                    <input type="text" name="ten_user" class="form-control" 
                           placeholder="vd: nguyenvan123" 
                           value="<?= sanitize($_POST['ten_user'] ?? '') ?>"
                           required minlength="4" maxlength="50" id="ten_user">
                    <div class="form-hint">4-50 ký tự, không dấu</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="hovaten" class="form-control" 
                           placeholder="Nguyễn Văn A"
                           value="<?= sanitize($_POST['hovaten'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" 
                       placeholder="example@email.com"
                       value="<?= sanitize($_POST['email'] ?? '') ?>" required id="email">
            </div>
            
            <div class="form-group">
                <label class="form-label">Số điện thoại</label>
                <input type="tel" name="sdt" class="form-control" 
                       placeholder="0901234567"
                       value="<?= sanitize($_POST['sdt'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Địa chỉ</label>
                <input type="text" name="dia_chi" class="form-control" 
                       placeholder="Số nhà, tên đường, phường/xã..."
                       value="<?= sanitize($_POST['dia_chi'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Mật khẩu *</label>
                <div style="position:relative;">
                    <input type="password" name="password" class="form-control" 
                           placeholder="Tối thiểu 6 ký tự" required minlength="6"
                           id="password" style="padding-right:50px;">
                    <button type="button" onclick="togglePwd('password','eye1')"
                            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:18px;" id="eye1">👁</button>
                </div>
                <div id="pwdStrength" style="margin-top:8px;"></div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Xác nhận mật khẩu *</label>
                <div style="position:relative;">
                    <input type="password" name="password_confirm" class="form-control" 
                           placeholder="Nhập lại mật khẩu" required
                           id="password_confirm" style="padding-right:50px;">
                    <button type="button" onclick="togglePwd('password_confirm','eye2')"
                            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:18px;" id="eye2">👁</button>
                </div>
            </div>
            
            <label style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;cursor:pointer;font-size:13px;color:var(--text-secondary);">
                <input type="checkbox" required style="margin-top:2px;accent-color:var(--accent);">
                Tôi đồng ý với <a href="#" style="color:var(--accent)">Điều khoản dịch vụ</a> và <a href="#" style="color:var(--accent)">Chính sách bảo mật</a>
            </label>
            
            <button type="submit" class="btn btn-primary btn-block" style="padding:14px;" id="submitBtn">
                Tạo Tài Khoản →
            </button>
        </form>
        
        <div style="text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
            <p style="color:var(--text-secondary);font-size:14px;">
                Đã có tài khoản? <a href="<?= BASE_URL ?>/login.php" style="color:var(--accent);font-weight:600;">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePwd(id, btnId) {
    const p = document.getElementById(id);
    const b = document.getElementById(btnId);
    if (p.type === 'password') { p.type = 'text'; b.textContent = '🙈'; }
    else { p.type = 'password'; b.textContent = '👁'; }
}

// Password strength
document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    const el = document.getElementById('pwdStrength');
    if (!val) { el.innerHTML = ''; return; }
    let strength = 0;
    if (val.length >= 6) strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;
    const labels = ['','Yếu','Yếu','Trung bình','Mạnh','Rất mạnh'];
    const colors = ['','#ef4444','#f59e0b','#f59e0b','#22c55e','#6c63ff'];
    el.innerHTML = `<div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
        <div style="height:100%;width:${strength*20}%;background:${colors[strength]};transition:all 0.3s;border-radius:2px;"></div>
    </div>
    <div style="font-size:12px;color:${colors[strength]};margin-top:4px;">${labels[strength]}</div>`;
});

// Validate confirm password on submit
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('password_confirm').value;
    if (p1 !== p2) {
        e.preventDefault();
        document.getElementById('password_confirm').style.borderColor = 'var(--danger)';
        alert('Mật khẩu xác nhận không khớp!');
    }
});
</script>
</body>
</html>
