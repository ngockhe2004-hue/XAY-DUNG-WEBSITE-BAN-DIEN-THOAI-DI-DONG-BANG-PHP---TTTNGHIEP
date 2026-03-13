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
        $hovaten = sanitize($_POST['hovaten']);
        $sdt = sanitize($_POST['sdt']);
        $diachi = sanitize($_POST['dia_chi']);
        $gioitinh = sanitize($_POST['gioi_tinh']);
        $ngaysinh = $_POST['ngay_sinh'] ?: null;

        db()->execute("UPDATE users SET hovaten=?, SDT=?, dia_chi=?, gioi_tinh=?, ngay_sinh=? WHERE ma_user=?",
            [$hovaten, $sdt, $diachi, $gioitinh, $ngaysinh, $userId]);
        
        // Sync to default address if exists
        $defaultAddrId = db()->fetchColumn("SELECT ma_diachi FROM diachi_user WHERE ma_user=? AND la_macdinh=1", [$userId]);
        if ($defaultAddrId) {
            db()->execute("UPDATE diachi_user SET ho_ten_nguoinhan=?, SDT_nguoinhan=?, dia_chi_cu_the=? WHERE ma_diachi=?",
                [$hovaten, $sdt, $diachi, $defaultAddrId]);
        }
        setFlash('success','Cập nhật thành công!');
    }
    if ($action === 'change_password') {
        $result = updatePassword($userId, $_POST['old_pwd'], $_POST['new_pwd']);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
    }
    if ($action === 'add_address') {
        // Reset địa chỉ mặc định cũ
        db()->execute("UPDATE diachi_user SET la_macdinh = 0 WHERE ma_user = ?", [$userId]);
        
        // Thêm địa chỉ mới và đặt làm mặc định
        db()->insert("INSERT INTO diachi_user (ma_user, ho_ten_nguoinhan, SDT_nguoinhan, tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the, la_macdinh) 
             VALUES (?,?,?,?,?,?,?,1)",
            [$userId, sanitize($_POST['hovaten']), sanitize($_POST['sdt']), sanitize($_POST['tinh_thanh']), sanitize($_POST['quan_huyen']), sanitize($_POST['phuong_xa']), sanitize($_POST['dia_chi_cu_the'])]);
        setFlash('success', 'Đã thêm địa chỉ và đặt làm mặc định');
    }
    if ($action === 'edit_address') {
        $addrId = (int)($_POST['ma_diachi'] ?? 0);
        db()->execute("UPDATE diachi_user SET ho_ten_nguoinhan=?, SDT_nguoinhan=?, tinh_thanh=?, quan_huyen=?, phuong_xa=?, dia_chi_cu_the=? WHERE ma_diachi=? AND ma_user=?",
            [sanitize($_POST['hovaten']), sanitize($_POST['sdt']), sanitize($_POST['tinh_thanh']), sanitize($_POST['quan_huyen']), sanitize($_POST['phuong_xa']), sanitize($_POST['dia_chi_cu_the']), $addrId, $userId]);
        setFlash('success', 'Đã cập nhật địa chỉ');
    }
    if ($action === 'delete_address') {
        $addrId = (int)($_POST['ma_diachi'] ?? 0);
        // Kiểm tra xem địa chỉ có phải mặc định không
        $isDefault = db()->fetchColumn("SELECT la_macdinh FROM diachi_user WHERE ma_diachi=? AND ma_user=?", [$addrId, $userId]);
        
        db()->execute("DELETE FROM diachi_user WHERE ma_diachi=? AND ma_user=?", [$addrId, $userId]);
        
        // Nếu xóa địa chỉ mặc định, set địa chỉ đầu tiên còn lại làm mặc định
        if ($isDefault) {
            $nextAddrId = db()->fetchColumn("SELECT ma_diachi FROM diachi_user WHERE ma_user=? LIMIT 1", [$userId]);
            if ($nextAddrId) {
                db()->execute("UPDATE diachi_user SET la_macdinh = 1 WHERE ma_diachi = ?", [$nextAddrId]);
            }
        }
        setFlash('success', 'Đã xóa địa chỉ');
    }
    if ($action === 'set_default') {
        $addrId = (int)($_POST['ma_diachi'] ?? 0);
        db()->execute("UPDATE diachi_user SET la_macdinh = 0 WHERE ma_user = ?", [$userId]);
        db()->execute("UPDATE diachi_user SET la_macdinh = 1 WHERE ma_diachi = ? AND ma_user = ?", [$addrId, $userId]);
        setFlash('success', 'Đã đặt địa chỉ mặc định');
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
                    <div style="font-size:14px;flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <strong><?= sanitize($addr['ho_ten_nguoinhan']) ?></strong> · <?= sanitize($addr['SDT_nguoinhan']) ?>
                            <?php if ($addr['la_macdinh']): ?>
                                <span class="badge" style="background:rgba(108,99,255,0.15);color:var(--accent);">Mặc định</span>
                            <?php endif; ?>
                        </div>
                        <div style="color:var(--text-secondary);"><?= sanitize($addr['dia_chi_cu_the']) ?>, <?= sanitize($addr['phuong_xa']) ?>, <?= sanitize($addr['quan_huyen']) ?>, <?= sanitize($addr['tinh_thanh']) ?></div>
                        
                        <div style="display:flex;gap:12px;margin-top:12px;">
                            <button class="btn-text" style="color:var(--accent);font-size:13px;font-weight:600;padding:0;cursor:pointer;background:none;border:none;" 
                                    onclick='openEditModal(<?= json_encode($addr) ?>)'>✏️ Sửa</button>
                            
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Xác nhận xóa địa chỉ này?')">
                                <input type="hidden" name="action" value="delete_address">
                                <input type="hidden" name="ma_diachi" value="<?= $addr['ma_diachi'] ?>">
                                <button type="submit" class="btn-text" style="color:var(--danger);font-size:13px;font-weight:600;padding:0;cursor:pointer;background:none;border:none;">🗑️ Xóa</button>
                            </form>
                            
                            <?php if (!$addr['la_macdinh']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="ma_diachi" value="<?= $addr['ma_diachi'] ?>">
                                <button type="submit" class="btn-text" style="color:var(--info);font-size:13px;font-weight:600;padding:0;cursor:pointer;background:none;border:none;">📌 Đặt mặc định</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:var(--text-secondary);">Chưa có địa chỉ nào.</p>
            <?php endif; ?>
            <!-- Add Address Modal -->
            <div id="addAddrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:500;align-items:center;justify-content:center;">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px;width:90%;max-width:480px;">
                    <h3 style="margin-bottom:20px;">+ Thêm Địa Chỉ Mới</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_address">
                        <div class="form-group"><label class="form-label">Họ tên người nhận</label><input type="text" name="hovaten" class="form-control" required value="<?= sanitize($user['hovaten']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Số điện thoại</label><input type="tel" name="sdt" class="form-control" required value="<?= sanitize($user['SDT']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Tỉnh/Thành</label><select name="tinh_thanh" id="add_tinh_thanh" class="form-control" required></select></div>
                        <div class="form-group"><label class="form-label">Quận/Huyện</label><select name="quan_huyen" id="add_quan_huyen" class="form-control" required></select></div>
                        <div class="form-group"><label class="form-label">Phường/Xã</label><select name="phuong_xa" id="add_phuong_xa" class="form-control" required></select></div>
                        <div class="form-group"><label class="form-label">Địa chỉ cụ thể</label><input type="text" name="dia_chi_cu_the" class="form-control" required></div>
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('addAddrModal').style.display='none'">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu địa chỉ</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Address Modal -->
            <div id="editAddrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:500;align-items:center;justify-content:center;">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px;width:90%;max-width:480px;">
                    <h3 style="margin-bottom:20px;">✏️ Chỉnh Sửa Địa Chỉ</h3>
                    <form method="POST" id="editAddrForm">
                        <input type="hidden" name="action" value="edit_address">
                        <input type="hidden" name="ma_diachi" id="edit_ma_diachi">
                        <div class="form-group"><label class="form-label">Họ tên người nhận</label><input type="text" name="hovaten" id="edit_hovaten" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Số điện thoại</label><input type="tel" name="sdt" id="edit_sdt" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Tỉnh/Thành</label><select name="tinh_thanh" id="edit_tinh_thanh" class="form-control" required></select></div>
                        <div class="form-group"><label class="form-label">Quận/Huyện</label><select name="quan_huyen" id="edit_quan_huyen" class="form-control" required></select></div>
                        <div class="form-group"><label class="form-label">Phường/Xã</label><select name="phuong_xa" id="edit_phuong_xa" class="form-control" required></select></div>
                        <div class="form-group"><label class="form-label">Địa chỉ cụ thể</label><input type="text" name="dia_chi_cu_the" id="edit_dia_chi_cu_the" class="form-control" required></div>
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('editAddrModal').style.display='none'">Hủy</button>
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            let addSelector, editSelector;
            
            document.addEventListener('DOMContentLoaded', () => {
                // Initialize selectors
                addSelector = initAddressSelector({
                    provinceSelector: '#add_tinh_thanh',
                    districtSelector: '#add_quan_huyen',
                    wardSelector: '#add_phuong_xa'
                });

                editSelector = initAddressSelector({
                    provinceSelector: '#edit_tinh_thanh',
                    districtSelector: '#edit_quan_huyen',
                    wardSelector: '#edit_phuong_xa'
                });
            });

            function openEditModal(addr) {
                document.getElementById('edit_ma_diachi').value = addr.ma_diachi;
                document.getElementById('edit_hovaten').value   = addr.ho_ten_nguoinhan;
                document.getElementById('edit_sdt').value       = addr.SDT_nguoinhan;
                document.getElementById('edit_dia_chi_cu_the').value = addr.dia_chi_cu_the;
                
                // Pre-fill dropdowns
                if (editSelector) {
                    editSelector.setValues(addr.tinh_thanh, addr.quan_huyen, addr.phuong_xa);
                }
                
                document.getElementById('editAddrModal').style.display = 'flex';
            }
            </script>

            <?php elseif ($tab === 'orders'): ?>
            <h2 class="tab-title">📦 Đơn Hàng Gần Đây</h2>
            <?php if ($orders): ?>
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
                <?php foreach ($orders as $o): ?>
                <div class="card" style="padding:0;overflow:hidden;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-secondary);border-bottom:1px solid var(--border);">
                        <span style="font-weight:700;color:var(--accent);"><?= sanitize($o['ma_donhang_code']) ?></span>
                        <span class="badge badge-<?= $statusBadge[$o['trang_thai']] ?? 'gray' ?>"><?= $statusLabels[$o['trang_thai']] ?? $o['trang_thai'] ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                        <span style="color:var(--text-secondary);font-size:13px;"><?= date('d/m/Y H:i',strtotime($o['ngay_dat'])) ?></span>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <span style="font-weight:700;color:var(--accent);"><?= formatPrice($o['tong_thanh_toan']) ?></span>
                            <button onclick='trackOrder(<?= json_encode([
                                "id" => $o["ma_donhang"],
                                "code" => $o["ma_donhang_code"],
                                "status" => $o["trang_thai"]
                            ]) ?>)' class="btn btn-primary btn-sm" style="background:var(--info);border-color:var(--info);padding:6px 12px;font-size:12px;">Theo dõi</button>
                            <a href="<?= BASE_URL ?>/order_detail.php?id=<?= $o['ma_donhang'] ?>" class="btn btn-outline btn-sm" style="padding:6px 12px;font-size:12px;">Chi tiết</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= BASE_URL ?>/orders.php" class="btn btn-outline" style="display:block;text-align:center;">Xem tất cả đơn hàng →</a>

            <!-- Modal Theo Dõi Đơn Hàng (Timeline) -->
            <div id="trackOrderModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(8px);padding:20px;">
                <div style="background:var(--bg-card);width:100%;max-width:500px;border-radius:24px;padding:32px;position:relative;box-shadow:0 20px 50px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
                    <button onclick="closeTrackModal()" style="position:absolute;top:20px;right:20px;background:var(--bg-secondary);border:none;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-secondary);font-size:20px;transition:0.2s;">&times;</button>
                    
                    <div style="text-align:center;margin-bottom:30px;">
                        <h2 id="modalOrderCode" style="font-size:24px;margin-bottom:8px;background:linear-gradient(45deg, var(--accent), #ff8a00);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:800;">Theo Dõi</h2>
                        <p style="color:var(--text-secondary);font-size:14px;">Trạng thái vận chuyển chi tiết</p>
                    </div>
                    
                    <div id="timelineContainer" style="position:relative;margin-left:20px;padding-left:30px;border-left:2px dashed var(--border);">
                        <!-- Timeline items -->
                    </div>

                    <div id="cancelWarning" style="display:none;margin-top:20px;padding:12px;background:rgba(239,68,68,0.1);border-radius:12px;color:var(--danger);font-size:13px;text-align:center;">
                        ⚠️ Đơn hàng đã bị hủy.
                    </div>
                    <button onclick="closeTrackModal()" class="btn btn-primary" style="width:100%;margin-top:24px;border-radius:12px;">Đóng</button>
                </div>
            </div>

            <script>
            async function trackOrder(orderData) {
                const modal = document.getElementById('trackOrderModal');
                const container = document.getElementById('timelineContainer');
                document.getElementById('modalOrderCode').textContent = 'Theo Dõi: ' + orderData.code;
                
                container.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:20px;">Đang tải...</p>';
                modal.style.display = 'flex';

                try {
                    const res = await fetch(`<?= BASE_URL ?>/api/get_order_logs.php?id=${orderData.id}`);
                    const data = await res.json();
                    
                    if (data.success) {
                        container.innerHTML = '';
                        const icons = {
                            'cho_xac_nhan': '⏳', 'da_xac_nhan': '✅', 'dang_dong_goi': '📦',
                            'dang_giao': '🚚', 'da_giao': '🏁', 'da_huy': '❌', 'da_tra_hang': '↩️'
                        };
                        
                        data.logs.forEach((log, i) => {
                            const isFirst = i === 0;
                            const dotColor = isFirst ? 'var(--accent)' : 'var(--border)';
                            const item = document.createElement('div');
                            item.style.position = 'relative';
                            item.style.marginBottom = '24px';
                            item.innerHTML = `
                                <div style="position:absolute;left:-41px;top:0;width:20px;height:20px;background:var(--bg-card);border:2px solid ${dotColor};border-radius:50%;display:flex;align-items:center;justify-content:center;z-index:2;">
                                    <div style="width:8px;height:8px;background:${dotColor};border-radius:50%;"></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;gap:10px;">
                                    <div>
                                        <div style="font-weight:700;color:${isFirst?'var(--accent)':'var(--text-primary)'};">${icons[log.trang_thai]||'•'} ${log.label}</div>
                                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">${log.mo_ta}</div>
                                    </div>
                                    <div style="text-align:right;white-space:nowrap;">
                                        <div style="font-size:12px;font-weight:700;">${log.time}</div>
                                        <div style="font-size:11px;color:var(--text-muted);">${log.date}</div>
                                    </div>
                                </div>
                            `;
                            container.appendChild(item);
                        });
                    }
                } catch (e) { container.innerHTML = 'Lỗi tải dữ liệu'; }
                document.getElementById('cancelWarning').style.display = (orderData.status === 'da_huy') ? 'block' : 'none';
            }

            function closeTrackModal() {
                document.getElementById('trackOrderModal').style.display = 'none';
            }

            window.onclick = function(event) {
                const trackModal = document.getElementById('trackOrderModal');
                const addModal = document.getElementById('addAddrModal');
                const editModal = document.getElementById('editAddrModal');
                if (event.target == trackModal) closeTrackModal();
                if (event.target == addModal) addModal.style.display = 'none';
                if (event.target == editModal) editModal.style.display = 'none';
            }
            </script>
            <?php else: ?>
            <p style="color:var(--text-secondary);">Chưa có đơn hàng nào.</p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
