<?php
$pageTitle = 'Chi Tiết Đơn Hàng';
require_once __DIR__ . '/includes/auth_admin.php';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$order = db()->fetchOne("SELECT dh.*, u.ten_user, u.hovaten, u.email FROM donhang dh JOIN users u ON dh.ma_user = u.ma_user WHERE dh.ma_donhang = ?", [$id]);
if (!$order) { echo '<p>Không tìm thấy đơn hàng</p>'; require_once __DIR__.'/includes/footer.php'; exit; }

$items = db()->fetchAll("SELECT * FROM chitiet_donhang WHERE ma_donhang = ?", [$id]);
$statusLabels=['cho_xac_nhan'=>'Chờ XN','da_xac_nhan'=>'Đã XN','dang_dong_goi'=>'Đóng gói','dang_giao'=>'Đang giao','da_giao'=>'Đã giao','da_huy'=>'Đã hủy'];
?>
<div style="display:flex;gap:20px;align-items:start;">
    <div style="flex:2;">
        <div class="page-header" style="margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <h1 class="page-title"><?= sanitize($order['ma_donhang_code']) ?></h1>
                <a href="<?= BASE_URL ?>/admin/print_invoice.php?id=<?= $id ?>" target="_blank" class="btn btn-outline" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    🖨️ In hóa đơn
                </a>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/admin/orders.php" style="display:flex;gap:8px;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="redirect_to" value="/order_detail.php?id=<?= $id ?>">
                <select name="trang_thai" class="filter-select">
                    <?php foreach ($statusLabels as $k=>$v): ?>
                        <?php $isLocked = in_array($order['trang_thai'], ['da_giao','da_huy']); ?>
                        <option value="<?= $k ?>" <?= $order['trang_thai']===$k?'selected':'' ?> <?= $isLocked && $order['trang_thai']!==$k?'disabled':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Xác nhận cập nhật trạng thái?')">Cập nhật</button>
            </form>
        </div>
        <div class="section-card" style="margin-bottom:16px;">
            <table class="admin-table">
                <thead><tr><th>Sản phẩm</th><th>Biến thể</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><div style="font-weight:600;"><?= sanitize($item['ten_sanpham']) ?></div></td>
                        <td style="color:var(--txt2);"><?= $item['ram_gb'] ?>+<?= $item['rom_gb'] ?>GB, <?= sanitize($item['mau_sac']) ?></td>
                        <td><?= $item['so_luong'] ?></td>
                        <td><?= number_format($item['don_gia'],0,',','.') ?>VND</td>
                        <td style="font-weight:600;"><?= number_format($item['thanh_tien'],0,',','.') ?>VND</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div style="flex:1;">
        <div class="section-card" style="margin-bottom:12px;">
            <div class="section-card-header"><h3>👤 Khách hàng</h3></div>
            <div class="section-card-body" style="font-size:14px;">
                <p><strong><?= sanitize($order['ten_nguoi_nhan']) ?></strong> · <?= sanitize($order['SDT_nguoi_nhan']) ?></p>
                <p style="color:var(--txt2);margin-top:6px;"><?= sanitize($order['dia_chi_cu_the']) ?>, <?= sanitize($order['phuong_xa']) ?>, <?= sanitize($order['quan_huyen']) ?>, <?= sanitize($order['tinh_thanh']) ?></p>
            </div>
        </div>
        <div class="section-card">
            <div class="section-card-header"><h3>💰 Thanh toán</h3></div>
            <div class="section-card-body" style="font-size:14px;">
                <?php
                $rows=[['Tạm tính',$order['tong_tien_hang']],['Phí ship',$order['phi_giao_hang']],['Giảm giá',-$order['so_tien_giam']],['Tổng cộng',$order['tong_thanh_toan']]];
                foreach($rows as [$l,$v]): ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;<?= $l==='Tổng cộng'?'font-weight:700;font-size:16px;border-top:1px solid var(--border);padding-top:8px;':''?>">
                    <span style="color:var(--txt2)"><?= $l ?></span>
                    <span><?= number_format($v,0,',','.') ?>VND</span>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
                    <span class="badge badge-<?= $order['trang_thai_TT']==='da_thanh_toan'?'success':'warning' ?>">
                        <?= $order['trang_thai_TT']==='da_thanh_toan'?'Đã thanh toán':'Chưa thanh toán' ?>
                    </span>
                    <span style="font-size:12px; font-weight:600; color:var(--txt3);"><?= strtoupper($order['phuong_thuc_TT']) ?></span>
                </div>
                
                <?php if ($order['trang_thai_TT'] !== 'da_thanh_toan'): ?>
                <form method="POST" action="<?= BASE_URL ?>/admin/orders.php" style="margin-top:16px;">
                    <input type="hidden" name="action" value="confirm_payment">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="redirect_to" value="/order_detail.php?id=<?= $id ?>">
                    <button type="submit" class="btn btn-success btn-block" style="width:100%;" onclick="return confirm('Xác nhận đã thu tiền cho đơn hàng này?')">
                        💵 Xác nhận thu tiền
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
