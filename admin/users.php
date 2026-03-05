<?php
require_once __DIR__ . '/includes/auth_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)$_POST['uid'] ?? 0;
    
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'active';
        db()->execute("UPDATE users SET trang_thai=? WHERE ma_user=? AND quyen='customer'", [$status, $uid]);
        setFlash('success','Đã cập nhật trạng thái');
    }
    if ($action === 'delete') {
        // Kiểm tra đơn hàng trước khi xóa
        $hasOrders = db()->fetchColumn("SELECT COUNT(*) FROM donhang WHERE ma_user = ?", [$uid]);
        if ($hasOrders > 0) {
            setFlash('error','Không thể xóa người dùng này vì đã có dữ liệu đơn hàng. Hãy sử dụng Khóa thay thế.');
        } else {
            db()->execute("DELETE FROM users WHERE ma_user=? AND quyen='customer'", [$uid]);
            setFlash('success','Đã xóa người dùng');
        }
    }
    if ($action === 'edit_user') {
        $hovaten = sanitize($_POST['hovaten'] ?? '');
        $email   = sanitize($_POST['email'] ?? '');
        $sdt     = sanitize($_POST['sdt'] ?? '');
        $diachi  = sanitize($_POST['dia_chi'] ?? '');
        db()->execute("UPDATE users SET hovaten=?, email=?, SDT=?, dia_chi=? WHERE ma_user=? AND quyen='customer'", 
            [$hovaten, $email, $sdt, $diachi, $uid]);
        setFlash('success','Đã cập nhật thông tin người dùng');
    }
    redirect(BASE_URL . '/admin/customer_management.php?tab=users');
}

$pageTitle = 'Quản Lý Người Dùng';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}


$q = sanitize($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page']??1));
$where = "quyen = 'customer'";
$params = [];
if ($q) { $where .= " AND (ten_user LIKE ? OR email LIKE ? OR hovaten LIKE ?)"; $params = ["%$q%","%$q%","%$q%"]; }
$total = (int)db()->fetchColumn("SELECT COUNT(*) FROM users WHERE $where", $params);
$paging = paginate($total,$page,ADMIN_PER_PAGE);
$users = db()->fetchAll("SELECT *, (SELECT COUNT(*) FROM donhang WHERE ma_user=users.ma_user) as so_don FROM users WHERE $where ORDER BY ngay_lap DESC LIMIT {$paging['per_page']} OFFSET {$paging['offset']}", $params);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">👥 QUẢN LÝ NGƯỜI DÙNG</h1>
        <p class="page-desc">Hệ thống đang phục vụ <strong><?= $total ?></strong> thành viên thân thiết</p>
    </div>
</div>

<!-- Simple Filter -->
<div class="section-card" style="margin-bottom: 30px; padding: 20px;">
    <form method="GET" class="filter-bar" style="margin-bottom: 0;">
        <input name="q" class="form-control" placeholder="🔍 Tìm theo Tên đăng nhập, Email hoặc Họ tên..." value="<?= sanitize($q) ?>" style="flex: 1;">
        <button type="submit" class="btn btn-primary">TÌM KIẾM</button>
        <a href="customer_management.php?tab=users" class="btn btn-outline">LÀM MỚI</a>
    </form>
</div>

<!-- Modern User Table -->
<div class="section-card">
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="text-align:left; padding-left: 30px;">HÀNH VI & DANH TÍNH</th>
                    <th>EMAIL LIÊN HỆ</th>
                    <th>SĐT / ĐỊA CHỈ</th>
                    <th>NGÀY GIA NHẬP</th>
                    <th>GIAO DỊCH</th>
                    <th>TRẠNG THÁI</th>
                    <th style="padding-right: 30px;">XỬ LÝ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="text-align:left; padding-left: 30px;">
                        <div style="display:flex; gap:15px; align-items:center;">
                            <div style="width:44px; height:44px; border-radius:15px; background: var(--purple-grad); color: #fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:18px; box-shadow: 0 8px 15px rgba(139, 92, 246, 0.2);">
                                <?= mb_strtoupper(mb_substr($u['hovaten'] ?: $u['ten_user'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 800; color: var(--txt);"><?= sanitize($u['hovaten'] ?: $u['ten_user']) ?></div>
                                <div style="font-size: 11px; font-weight: 700; color: var(--accent);">@<?= sanitize($u['ten_user']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; font-size: 13px; color: var(--txt2);"><?= sanitize($u['email']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 700; font-size: 13px;"><?= sanitize($u['SDT'] ?? '---') ?></div>
                        <div style="font-size: 11px; color: var(--txt3); font-weight: 600;"><?= sanitize($u['dia_chi'] ?: 'Chưa cập nhật') ?></div>
                    </td>
                    <td style="font-weight: 700; font-size: 13px; color: var(--txt2);">
                        <?= date('d/m/Y', strtotime($u['ngay_lap'])) ?>
                    </td>
                    <td>
                        <span class="badge badge-purple" style="font-size: 11px;"><?= $u['so_don'] ?> ĐƠN HÀNG</span>
                    </td>
                    <td>
                        <?php
                        $stMap = [
                            'active'   => ['badge-success', 'ĐANG HOẠT ĐỘNG'],
                            'pending'  => ['badge-warning', 'ĐANG CHỜ'],
                            'banned'   => ['badge-danger', 'ĐÃ KHÓA'],
                            'inactive' => ['badge-gray', 'VÔ HIỆU']
                        ];
                        $curSt = $stMap[$u['trang_thai']] ?? ['badge-gray', strtoupper($u['trang_thai'])];
                        ?>
                        <form method="POST" style="display:inline;" onchange="this.submit()">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="uid" value="<?= $u['ma_user'] ?>">
                            <select name="status" class="badge <?= $curSt[0] ?>" 
                                    style="border: 1px solid var(--border); background: #fff; cursor: pointer; text-align: center; appearance: none; -webkit-appearance: none; font-family: inherit;">
                                <option value="active" <?= $u['trang_thai'] === 'active' ? 'selected' : '' ?>>HOẠT ĐỘNG</option>
                                <option value="pending" <?= $u['trang_thai'] === 'pending' ? 'selected' : '' ?>>ĐANG CHỜ</option>
                                <option value="banned" <?= $u['trang_thai'] === 'banned' ? 'selected' : '' ?>>BỊ KHÓA</option>
                            </select>
                        </form>
                    </td>
                    <td style="padding-right: 30px;">
                        <div style="display:flex; gap:10px; justify-content: center;">
                            <button class="btn-icon btn-outline" onclick='openEditUser(<?= json_encode($u) ?>)' title="Chỉnh sửa thông tin" style="color: var(--purple); border-color: var(--purple-light);">✏️</button>
                            <form method="POST" style="display:contents;" onsubmit="return confirm('⚠️ Chắc chắn muốn XÓA khách hàng này?\nDữ liệu không thể khôi phục.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="uid" value="<?= $u['ma_user'] ?>">
                                <button type="submit" class="btn-icon btn-outline" style="color: var(--danger); border-color: #fee2e2;" title="Xóa tài khoản">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- Modern Edit User Modal -->
<div id="editUserModal" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="section-card animate-fade-up" style="width:95%; max-width:550px; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 30px 60px rgba(0,0,0,0.2);">
        <div class="section-card-header" style="background: var(--purple-grad); color: #fff;">
            <h3 style="color: #fff;">👤 CHỈNH SỬA THÀNH VIÊN</h3>
            <button onclick="document.getElementById('editUserModal').style.display='none'" style="background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div class="section-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="uid" id="edit_uid">
                
                <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Họ và tên khách hàng</label>
                        <input type="text" name="hovaten" id="edit_hovaten" class="form-control" required style="background: #f8fafc;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email liên hệ</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required style="background: #f8fafc;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="sdt" id="edit_sdt" class="form-control" style="background: #f8fafc;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Địa chỉ nhận hàng</label>
                        <textarea name="dia_chi" id="edit_diachi" class="form-control" rows="3" style="resize:none; background: #f8fafc;"></textarea>
                    </div>
                </div>
                
                <div style="display:flex; gap:12px; margin-top:30px; justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('editUserModal').style.display='none'">HỦY BỎ</button>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">LƯU THAY ĐỔI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditUser(u) {
    document.getElementById('edit_uid').value = u.ma_user;
    document.getElementById('edit_hovaten').value = u.hovaten || u.ten_user || '';
    document.getElementById('edit_email').value = u.email || '';
    document.getElementById('edit_sdt').value = u.SDT || '';
    document.getElementById('edit_diachi').value = u.dia_chi || '';
    document.getElementById('editUserModal').style.display = 'flex';
}
</script>

<?php if (!isset($is_included_mode)): ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
