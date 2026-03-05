<?php
$pageTitle = 'Tài Khoản Của Tôi';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = $_SESSION['user_site']['id'];
$user   = getCurrentUser();
$tab    = sanitize($_GET['tab'] ?? 'profile');

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        db()->execute("UPDATE users SET hovaten=?, SDT=?, dia_chi=?, gioi_tinh=?, ngay_sinh=? WHERE ma_user=?",
            [sanitize($_POST['hovaten']), sanitize($_POST['sdt']), sanitize($_POST['dia_chi']), sanitize($_POST['gioi_tinh']), $_POST['ngay_sinh'] ?: null, $userId]);
        setFlash('success','Cập nhật thành công!');
    }
    if ($action === 'change_password') {
        $result = updatePassword($userId, $_POST['old_pwd'], $_POST['new_pwd']);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
    }
    if ($action === 'add_address') {
        db()->insert("INSERT INTO diachi_user (ma_user, ho_ten_nguoinhan, SDT_nguoinhan, tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the) VALUES (?,?,?,?,?,?,?)",
            [$userId, sanitize($_POST['hovaten']), sanitize($_POST['sdt']), sanitize($_POST['tinh_thanh']), sanitize($_POST['quan_huyen']), sanitize($_POST['phuong_xa']), sanitize($_POST['dia_chi_cu_the'])]);
        setFlash('success','Đã thêm địa chỉ');
    }
    redirect(BASE_URL . '/profile.php?tab=' . $tab);
}

$orders    = db()->fetchAll("SELECT * FROM donhang WHERE ma_user = ? ORDER BY ngay_dat DESC LIMIT 5", [$userId]);
$addresses = db()->fetchAll("SELECT * FROM diachi_user WHERE ma_user = ? ORDER BY la_macdinh DESC", [$userId]);
$statusLabels=['cho_xac_nhan'=>'Chờ XN','da_xac_nhan'=>'Đã XN','dang_dong_goi'=>'Đóng gói','dang_giao'=>'Đang giao','da_giao'=>'Đã giao','da_huy'=>'Đã hủy'];
$statusBadge =['cho_xac_nhan'=>'warning','da_xac_nhan'=>'info','dang_dong_goi'=>'purple','dang_giao'=>'success','da_giao'=>'success','da_huy'=>'danger'];
?>

<div class="container" style="padding:32px 0 60px;">
    <div class="profile-layout">
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar-box">
                <div class="profile-avatar"><?= mb_strtoupper(mb_substr($user['hovaten'] ?? $user['ten_user'], 0, 1)) ?></div>
                <div class="profile-name"><?= sanitize($user['hovaten'] ?: $user['ten_user']) ?></div>
                <div class="profile-role"><?= $user['quyen'] === 'admin' ? '👑 Admin' : '🛒 Khách hàng' ?></div>
            </div>
            <nav class="profile-nav">
                <a href="?tab=profile"   class="<?= $tab==='profile'?'active':'' ?>">👤 Thông tin cá nhân</a>
                <a href="?tab=password"  class="<?= $tab==='password'?'active':'' ?>">🔒 Đổi mật khẩu</a>
                <a href="?tab=addresses" class="<?= $tab==='addresses'?'active':'' ?>">📍 Địa chỉ</a>
                <a href="?tab=orders"    class="<?= $tab==='orders'?'active':'' ?>">📦 Đơn hàng</a>
                <a href="<?= BASE_URL ?>/wishlist.php">❤️ Yêu thích</a>
                <a href="<?= BASE_URL ?>/logout.php" style="color:var(--danger);">⏏️ Đăng xuất</a>
            </nav>
        </div>

        <!-- Content -->
        <div class="profile-content">
            
            <?php if ($tab === 'profile'): ?>
            <h2 class="tab-title">👤 Thông Tin Cá Nhân</h2>
            <form method="POST" class="card" style="padding:28px;">
                <input type="hidden" name="action" value="update_profile">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" name="hovaten" class="form-control" value="<?= sanitize($user['hovaten'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['ten_user']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="sdt" class="form-control" value="<?= sanitize($user['SDT'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="dia_chi" class="form-control" value="<?= sanitize($user['dia_chi'] ?? '') ?>" placeholder="Nhập địa chỉ của bạn">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giới tính</label>
                        <select name="gioi_tinh" class="form-control">
                            <option value="">--Chọn--</option>
                            <option value="nam" <?= ($user['gioi_tinh'] ?? '') === 'nam' ? 'selected' : '' ?>>Nam</option>
                            <option value="nu"  <?= ($user['gioi_tinh'] ?? '') === 'nu'  ? 'selected' : '' ?>>Nữ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="ngay_sinh" class="form-control" value="<?= $user['ngay_sinh'] ?? '' ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:8px;">💾 Lưu thay đổi</button>
            </form>

            <?php elseif ($tab === 'password'): ?>
            <h2 class="tab-title">🔒 Đổi Mật Khẩu</h2>
            <form method="POST" class="card" style="padding:28px;max-width:400px;">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Mật khẩu cũ *</label>
                    <input type="password" name="old_pwd" class="form-control" required placeholder="Nhập mật khẩu hiện tại">
                </div>
                <div class="form-group">
                    <label class="form-label">Mật khẩu mới *</label>
                    <input type="password" name="new_pwd" class="form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                </div>
                <div class="form-group">
                    <label class="form-label">Xác nhận mật khẩu mới *</label>
                    <input type="password" name="confirm_pwd" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                </div>
                <button type="submit" class="btn btn-primary">🔒 Đổi mật khẩu</button>
            </form>

            <?php elseif ($tab === 'addresses'): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 class="tab-title" style="margin:0">📍 Địa Chỉ Giao Hàng</h2>
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('addAddrModal').style.display='flex'">+ Thêm địa chỉ</button>
            </div>
            <?php if ($addresses): ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($addresses as $addr): ?>
                <div class="card" style="padding:16px;display:flex;justify-content:space-between;align-items:center;">
                    <div style="font-size:14px;">
                        <strong><?= sanitize($addr['ho_ten_nguoinhan']) ?></strong> · <?= sanitize($addr['SDT_nguoinhan']) ?>
                        <?php if ($addr['la_macdinh']): ?><span class="badge" style="background:rgba(108,99,255,0.15);color:var(--accent);margin-left:8px;">Mặc định</span><?php endif; ?>
                        <div style="color:var(--text-secondary);margin-top:4px;"><?= sanitize($addr['dia_chi_cu_the']) ?>, <?= sanitize($addr['phuong_xa']) ?>, <?= sanitize($addr['quan_huyen']) ?>, <?= sanitize($addr['tinh_thanh']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:var(--text-secondary);">Chưa có địa chỉ nào.</p>
            <?php endif; ?>
            <div id="addAddrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:500;align-items:center;justify-content:center;">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px;width:90%;max-width:480px;">
                    <h3 style="margin-bottom:20px;">+ Thêm Địa Chỉ Mới</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_address">
                        <div class="form-group"><label class="form-label">Họ tên người nhận</label><input type="text" name="hovaten" class="form-control" required value="<?= sanitize($user['hovaten']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Số điện thoại</label><input type="tel" name="sdt" class="form-control" required value="<?= sanitize($user['SDT']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Tỉnh/Thành</label><input type="text" name="tinh_thanh" class="form-control" required placeholder="TP. Hồ Chí Minh"></div>
                        <div class="form-group"><label class="form-label">Quận/Huyện</label><input type="text" name="quan_huyen" class="form-control"></div>
                        <div class="form-group"><label class="form-label">Phường/Xã</label><input type="text" name="phuong_xa" class="form-control"></div>
                        <div class="form-group"><label class="form-label">Địa chỉ cụ thể</label><input type="text" name="dia_chi_cu_the" class="form-control" required></div>
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('addAddrModal').style.display='none'">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu địa chỉ</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'orders'): ?>
            <h2 class="tab-title">📦 Đơn Hàng Gần Đây</h2>
            <?php if ($orders): ?>
            <?php foreach ($orders as $o): ?>
            <div class="card" style="padding:0;overflow:hidden;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-secondary);border-bottom:1px solid var(--border);">
                    <span style="font-weight:700;color:var(--accent);"><?= sanitize($o['ma_donhang_code']) ?></span>
                    <span class="badge badge-<?= $statusBadge[$o['trang_thai']] ?? 'gray' ?>"><?= $statusLabels[$o['trang_thai']] ?? $o['trang_thai'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                    <span style="color:var(--text-secondary);font-size:13px;"><?= date('d/m/Y H:i',strtotime($o['ngay_dat'])) ?></span>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <span style="font-weight:700;color:var(--accent);"><?= formatPrice($o['tong_thanh_toan']) ?></span>
                        <a href="<?= BASE_URL ?>/order_detail.php?id=<?= $o['ma_donhang'] ?>" class="btn btn-outline btn-sm">Chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/orders.php" class="btn btn-outline" style="display:block;text-align:center;">Xem tất cả đơn hàng →</a>
            <?php else: ?>
            <p style="color:var(--text-secondary);">Chưa có đơn hàng nào.</p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
