<?php
ob_start();
require_once __DIR__ . '/includes/auth_admin.php';

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) {
    setFlash('error', 'Không tìm thấy ID người dùng');
    redirect(BASE_URL . '/admin/customer_management.php?tab=users');
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit_user') {
        $hovaten = sanitize($_POST['hovaten'] ?? '');
        $email   = sanitize($_POST['email'] ?? '');
        $sdt     = sanitize($_POST['sdt'] ?? '');
        $diachi  = sanitize($_POST['dia_chi'] ?? '');
        $gioitinh = sanitize($_POST['gioi_tinh'] ?? '');
        $ngaysinh = $_POST['ngay_sinh'] ?: null;
        $trangthai = sanitize($_POST['trang_thai'] ?? 'active');
        
        $quyen     = sanitize($_POST['quyen'] ?? 'customer');
        
        db()->execute("UPDATE users SET hovaten=?, email=?, SDT=?, dia_chi=?, gioi_tinh=?, ngay_sinh=?, trang_thai=?, quyen=? WHERE ma_user=?", 
            [$hovaten, $email, $sdt, $diachi, $gioitinh, $ngaysinh, $trangthai, $quyen, $uid]);
        setFlash('success', 'Đã cập nhật thông tin khách hàng thành công');
    }
}

// Lấy thông tin khách hàng (Bỏ lọc quyen='customer' để có thể xem mọi user)
$user = db()->fetchOne("SELECT * FROM users WHERE ma_user = ?", [$uid]);
if (!$user) {
    setFlash('error', 'Khách hàng không tồn tại');
    redirect(BASE_URL . '/admin/customer_management.php?tab=users');
}

// Thống kê hoạt động
$stats = db()->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(tong_thanh_toan) as total_spent,
        MIN(ngay_dat) as joined_date
    FROM donhang 
    WHERE ma_user = ?
", [$uid]);

$pageTitle = 'Chi Tiết Khách Hàng';
require_once __DIR__ . '/includes/header.php';
?>

<div class="customer-detail-wrapper animate-fade-up">
    <!-- Profile Header Section -->
    <div class="profile-masthead">
        <div class="masthead-left">
            <div class="user-big-avatar">
                <?= mb_strtoupper(mb_substr($user['hovaten'] ?: $user['ten_user'], 0, 1)) ?>
            </div>
            <div class="user-meta-info">
                <h1><?= sanitize($user['hovaten'] ?: $user['ten_user']) ?></h1>
                <p>@<?= sanitize($user['ten_user']) ?> • ID: #<?= $uid ?></p>
            </div>
        </div>
        <div class="masthead-right">
            <a href="customer_management.php?tab=users" class="btn-back">
                <span>←</span>
            </a>
            <div class="status-selector-wrapper">
                <label>TRẠNG THÁI:</label>
                <select name="trang_thai" form="main-edit-form" class="status-select-modern <?= $user['trang_thai'] ?>">
                    <option value="active" <?= $user['trang_thai'] === 'active' ? 'selected' : '' ?>>✓ ACTIVE</option>
                    <option value="pending" <?= $user['trang_thai'] === 'pending' ? 'selected' : '' ?>>⚠ PENDING</option>
                    <option value="banned" <?= $user['trang_thai'] === 'banned' ? 'selected' : '' ?>>✘ BANNED</option>
                </select>
            </div>
        </div>
    </div>

    <div class="detail-grid-container">
        <!-- Sidebar Column -->
        <div class="detail-sidebar">
            <!-- Stats Card -->
            <div class="glass-card stats-mini-grid">
                <div class="card-header">
                    <h3>📊 Thống kê hoạt động</h3>
                </div>
                <div class="stats-items">
                    <div class="stat-mini-item">
                        <span class="value"><?= number_format($stats['total_orders'] ?? 0) ?></span>
                        <span class="label">ĐƠN HÀNG</span>
                    </div>
                    <div class="stat-mini-item">
                        <span class="value"><?= number_format($stats['total_spent'] ?? 0) ?> đ</span>
                        <span class="label">CHI TIÊU</span>
                    </div>
                    <div class="stat-mini-item">
                        <span class="value"><?= $stats['joined_date'] ? date('d/m/Y', strtotime($stats['joined_date'])) : date('d/m/Y', strtotime($user['ngay_lap'])) ?></span>
                        <span class="label">THAM GIA</span>
                    </div>
                    <div class="stat-mini-item highlight">
                        <span class="value"><?= strtoupper($user['trang_thai']) ?></span>
                        <span class="label">TRẠNG THÁI</span>
                    </div>
                </div>
            </div>

            <!-- Address Card -->
            <div class="glass-card address-preview-card">
                <div class="card-header">
                    <h3>📍 ĐỊA CHỈ MẶC ĐỊNH</h3>
                </div>
                <div class="card-body">
                    <p class="address-text"><?= sanitize($user['dia_chi'] ?: 'Chưa cập nhật địa chỉ giao hàng.') ?></p>
                    <button type="button" class="btn-link-action">⚙ QUẢN LÝ ĐỊA CHỈ</button>
                </div>
            </div>
        </div>

        <!-- Main Form Column -->
        <div class="detail-main-content">
            <div class="glass-card">
                <div class="card-header">
                    <h3>👤 Thông tin cá nhân</h3>
                </div>
                <div class="card-body">
                    <form id="main-edit-form" method="POST">
                        <input type="hidden" name="action" value="edit_user">
                        
                        <div class="modern-form-grid">
                            <div class="form-group-modern">
                                <label>Họ và tên</label>
                                <input type="text" name="hovaten" value="<?= sanitize($user['hovaten'] ?: $user['ten_user']) ?>" required placeholder="Nhập họ và tên...">
                            </div>

                            <div class="form-group-modern">
                                <label>Số điện thoại</label>
                                <input type="tel" name="sdt" value="<?= sanitize($user['SDT']) ?>" placeholder="Nhập số điện thoại...">
                            </div>

                            <div class="form-group-modern">
                                <label>Email liên hệ</label>
                                <input type="email" name="email" value="<?= sanitize($user['email']) ?>" required placeholder="Nhập email...">
                            </div>

                            <div class="form-group-modern">
                                <label>Vai trò hệ thống</label>
                                <select name="quyen">
                                    <option value="customer" <?= $user['quyen'] === 'customer' ? 'selected' : '' ?>>CUSTOMER (Khách hàng)</option>
                                    <option value="admin" <?= $user['quyen'] === 'admin' ? 'selected' : '' ?>>ADMIN (Quản trị viên)</option>
                                </select>
                            </div>

                            <div class="form-group-modern">
                                <label>Giới tính</label>
                                <select name="gioi_tinh">
                                    <option value="">--Chọn--</option>
                                    <option value="nam" <?= $user['gioi_tinh'] === 'nam' ? 'selected' : '' ?>>Nam</option>
                                    <option value="nu" <?= $user['gioi_tinh'] === 'nu' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="khac" <?= $user['gioi_tinh'] === 'khac' ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>

                            <div class="form-group-modern">
                                <label>Ngày sinh</label>
                                <input type="date" name="ngay_sinh" value="<?= $user['ngay_sinh'] ?>">
                            </div>

                            <div class="form-group-modern full-width">
                                <label>Địa chỉ thường trú</label>
                                <textarea name="dia_chi" rows="3" placeholder="Nhập địa chỉ đầy đủ..."><?= sanitize($user['dia_chi']) ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions-modern">
                            <button type="button" class="btn-cancel" onclick="location.href='customer_management.php?tab=users'">Hủy bỏ</button>
                            <button type="submit" class="btn-save-modern">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* New Detail Page Styles */
.customer-detail-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

.profile-masthead {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.masthead-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-big-avatar {
    width: 80px;
    height: 80px;
    border-radius: 24px;
    background: var(--grad-bg);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 900;
    box-shadow: 0 10px 25px rgba(215, 0, 24, 0.2);
}

.user-meta-info h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 2px;
    color: var(--txt);
}

.user-meta-info p {
    color: var(--txt3);
    font-weight: 600;
    font-size: 14px;
}

.masthead-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.btn-back {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--txt2);
    font-weight: bold;
    font-size: 20px;
    box-shadow: var(--shadow);
    transition: 0.2s;
}

.btn-back:hover {
    background: var(--bg);
    transform: translateX(-3px);
}

.status-selector-wrapper {
    background: #fff;
    padding: 8px 16px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.status-selector-wrapper label {
    font-size: 10px;
    font-weight: 800;
    color: var(--txt3);
}

.status-select-modern {
    border: none;
    font-weight: 800;
    font-size: 12px;
    padding: 6px 15px;
    border-radius: 10px;
    cursor: pointer;
    outline: none;
}

.status-select-modern.active { background: #dcfce7; color: #15803d; }
.status-select-modern.pending { background: #fef9c3; color: #a16207; }
.status-select-modern.banned { background: #fee2e2; color: #b91c1c; }

/* Grid Layout */
.detail-grid-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 25px;
    align-items: start;
}

.glass-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--border);
}

.card-header h3 {
    font-size: 14px;
    font-weight: 800;
    color: var(--txt2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Stats Mini Grid */
.stats-items {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat-mini-item {
    background: var(--bg);
    padding: 15px;
    border-radius: 15px;
    display: flex;
    flex-direction: column;
}

.stat-mini-item .value {
    font-size: 16px;
    font-weight: 900;
    color: var(--txt);
}

.stat-mini-item .label {
    font-size: 10px;
    font-weight: 700;
    color: var(--txt3);
    margin-top: 4px;
}

.stat-mini-item.highlight {
    background: #eff6ff;
}

.stat-mini-item.highlight .value {
    color: var(--info);
}

/* Address Card */
.address-preview-card .card-body {
    padding: 25px;
}

.address-text {
    font-size: 15px;
    font-weight: 600;
    color: var(--txt2);
    line-height: 1.6;
    margin-bottom: 20px;
}

.btn-link-action {
    background: none;
    border: none;
    color: var(--accent);
    font-weight: 800;
    font-size: 11px;
    cursor: pointer;
    padding: 0;
}

/* Modern Form */
.modern-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.form-group-modern {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group-modern.full-width {
    grid-column: span 2;
}

.form-group-modern label {
    font-size: 12px;
    font-weight: 700;
    color: var(--txt2);
}

.form-group-modern input, 
.form-group-modern select, 
.form-group-modern textarea {
    padding: 12px 16px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--bg);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    transition: 0.3s;
}

.form-group-modern input:focus, 
.form-group-modern select:focus, 
.form-group-modern textarea:focus {
    outline: none;
    background: #fff;
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(215, 0, 24, 0.05);
}

.form-actions-modern {
    margin-top: 40px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 25px;
    border-top: 1px solid var(--border);
}

.btn-save-modern {
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 12px 35px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
    transition: 0.3s;
}

.btn-save-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

.btn-cancel {
    background: none;
    border: 1px solid var(--border);
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 700;
    color: var(--txt2);
    cursor: pointer;
    transition: 0.3s;
}

.btn-cancel:hover {
    background: var(--bg);
}

@media (max-width: 992px) {
    .detail-grid-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
ob_end_flush();
?>
