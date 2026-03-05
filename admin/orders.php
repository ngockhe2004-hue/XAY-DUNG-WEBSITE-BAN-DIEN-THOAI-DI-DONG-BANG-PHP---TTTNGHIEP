<?php
require_once __DIR__ . '/includes/auth_admin.php';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Migration: Đảm bảo ENUM có 'da_tra_hang'
    try {
        db()->execute("ALTER TABLE donhang MODIFY COLUMN trang_thai ENUM('cho_xac_nhan','da_xac_nhan','dang_dong_goi','dang_giao','da_giao','da_huy','cho_hoan_tien','da_hoan_tien','da_tra_hang') NOT NULL DEFAULT 'cho_xac_nhan'");
    } catch (Exception $e) {}

    if ($_POST['action'] === 'update_status') {
        $id = (int)$_POST['id'];
        $tt = sanitize($_POST['trang_thai']);
        $allowed = ['cho_xac_nhan','da_xac_nhan','dang_dong_goi','dang_giao','da_giao','da_huy', 'da_tra_hang'];
        if (in_array($tt, $allowed)) {
            $old = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang = ?", [$id]);
            
            // Logic: Auto-update payment to 'da_thanh_toan' if status becomes 'da_giao'
            $paymentStatus = $old['trang_thai_TT'];
            if ($tt === 'da_giao') {
                $paymentStatus = 'da_thanh_toan';
                // Đồng bộ sang bảng thanhtoan
                $manualId = 'AUTO-' . strtoupper(substr(uniqid(), -6));
                db()->execute("UPDATE thanhtoan SET trang_thai = 'success', ngay_thanhtoan = NOW(), ma_giao_dich = COALESCE(ma_giao_dich, ?) WHERE ma_donhang = ?", [$manualId, $id]);
            } elseif ($tt === 'da_huy') {
                $paymentStatus = 'that_bai';
                db()->execute("UPDATE thanhtoan SET trang_thai = 'failed' WHERE ma_donhang = ?", [$id]);
            } elseif ($tt === 'da_tra_hang') {
                $paymentStatus = 'da_hoan_tien';
                db()->execute("UPDATE thanhtoan SET trang_thai = 'refunded' WHERE ma_donhang = ?", [$id]);
            }
            
            db()->execute("UPDATE donhang SET trang_thai = ?, trang_thai_TT = ? WHERE ma_donhang = ?", [$tt, $paymentStatus, $id]);
            // Log
            db()->insert("INSERT INTO audit_log (ma_admin, hanh_dong, bang_lien_quan, ma_ban_ghi, du_lieu_cu, du_lieu_moi) VALUES (?,?,?,?,?,?)",
                [$_SESSION['admin_site']['id'], 'UPDATE_ORDER_STATUS', 'donhang', $id,
                 json_encode(['trang_thai'=>$old['trang_thai'], 'trang_thai_TT'=>$old['trang_thai_TT']]), 
                 json_encode(['trang_thai'=>$tt, 'trang_thai_TT'=>$paymentStatus])]);
            setFlash('success', 'Đã cập nhật trạng thái đơn hàng và thanh toán');
        }
    }
    if ($_POST['action'] === 'confirm_payment') {
        $id = (int)$_POST['id'];
        $old = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang = ?", [$id]);
        if ($old) {
            db()->execute("UPDATE donhang SET trang_thai_TT = 'da_thanh_toan' WHERE ma_donhang = ?", [$id]);
            // Đồng bộ sang bảng thanhtoan nếu có bản ghi
            $manualId = 'MANUAL-' . strtoupper(substr(uniqid(), -6));
            db()->execute("UPDATE thanhtoan SET trang_thai = 'success', ngay_thanhtoan = NOW(), ma_giao_dich = COALESCE(ma_giao_dich, ?) WHERE ma_donhang = ?", [$manualId, $id]);
            
            db()->insert("INSERT INTO audit_log (ma_admin, hanh_dong, bang_lien_quan, ma_ban_ghi, du_lieu_cu, du_lieu_moi) VALUES (?,?,?,?,?,?)",
                [$_SESSION['admin_site']['id'], 'CONFIRM_PAYMENT_MANUAL', 'donhang', $id,
                 json_encode(['trang_thai_TT'=>$old['trang_thai_TT']]), 
                 json_encode(['trang_thai_TT'=>'da_thanh_toan'])]);
            setFlash('success', 'Xác nhận thu tiền thành công');
        }
    }
    if ($_POST['action'] === 'refund_order') {
        $id = (int)$_POST['id'];
        db()->execute("UPDATE donhang SET trang_thai = 'da_tra_hang', trang_thai_TT = 'da_hoan_tien' WHERE ma_donhang = ?", [$id]);
        db()->execute("UPDATE thanhtoan SET trang_thai = 'refunded' WHERE ma_donhang = ?", [$id]);
        setFlash('success', 'Đã chuyển trạng thái sang Trả hàng & Hoàn tiền');
    }
    if ($_POST['action'] === 'delete_order') {
        $id = (int)$_POST['id'];
        db()->execute("DELETE FROM chitiet_donhang WHERE ma_donhang = ?", [$id]);
        db()->execute("DELETE FROM thanhtoan WHERE ma_donhang = ?", [$id]);
        db()->execute("DELETE FROM donhang WHERE ma_donhang = ?", [$id]);
        setFlash('success', 'Đã xóa đơn hàng thành công');
    }
    redirect(BASE_URL . '/admin' . (isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '/sales_management.php?tab=orders'));
}

$pageTitle = 'Quản Lý Đơn Hàng';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}


// Filter
$tt = sanitize($_GET['tt'] ?? '');
$q  = sanitize($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page']??1));
$where = ['1=1'];
$params = [];
if ($tt) { $where[] = 'dh.trang_thai = ?'; $params[] = $tt; }
if ($q)  { $where[] = '(dh.ma_donhang_code LIKE ? OR u.ten_user LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$whereSQL = implode(' AND ', $where);

$total = (int)db()->fetchColumn("SELECT COUNT(*) FROM donhang dh JOIN users u ON dh.ma_user = u.ma_user WHERE $whereSQL", $params);
$paging = paginate($total, $page, ADMIN_PER_PAGE);

$orders = db()->fetchAll("
    SELECT dh.*, u.ten_user, u.hovaten, u.SDT,
           (SELECT COUNT(*) FROM chitiet_donhang WHERE ma_donhang = dh.ma_donhang) as so_sp
    FROM donhang dh JOIN users u ON dh.ma_user = u.ma_user
    WHERE $whereSQL ORDER BY dh.ngay_dat DESC
    LIMIT {$paging['per_page']} OFFSET {$paging['offset']}
", $params);

$statusLabels = ['cho_xac_nhan'=>'Chờ XN','da_xac_nhan'=>'Đã XN','dang_dong_goi'=>'Đóng gói','dang_giao'=>'Đang giao','da_giao'=>'Đã giao','da_tra_hang'=>'Trả hàng','da_huy'=>'Đã hủy'];
$statusBadge  = ['cho_xac_nhan'=>'warning','da_xac_nhan'=>'info','dang_dong_goi'=>'purple','dang_giao'=>'success','da_giao'=>'success','da_tra_hang'=>'purple','da_huy'=>'danger'];
$payMethodLabels = [
    'cod' => 'COD',
    'chuyen_khoan' => 'CK',
    'momo' => 'MoMo',
    'vnpay' => 'VNPay',
    'zalopay' => 'ZaloPay',
    'visa_mastercard' => 'Visa'
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🛒 QUẢN LÝ ĐƠN HÀNG</h1>
        <p class="page-desc">Theo dõi và xử lý vận đơn cho <strong><?= $total ?></strong> đơn hàng</p>
    </div>
</div>

<!-- Smart Status Tabs -->
<div class="section-card" style="margin-bottom: 30px; padding: 15px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items: center;">
        <a href="sales_management.php?tab=orders&tt=" class="btn <?= !$tt ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 12px; font-size: 11px;">
            💎 TẤT CẢ (<?= db()->fetchColumn("SELECT COUNT(*) FROM donhang") ?>)
        </a>
        <?php foreach ($statusLabels as $k => $v): 
            $cnt = db()->fetchColumn("SELECT COUNT(*) FROM donhang WHERE trang_thai = ?", [$k]);
            $isActive = ($tt === $k);
            $icon = [
                'cho_xac_nhan' => '⏳',
                'da_xac_nhan' => '✅',
                'dang_dong_goi' => '📦',
                'dang_giao' => '🚚',
                'da_giao' => '🏁',
                'da_tra_hang' => '↩️',
                'da_huy' => '❌'
            ][$k] ?? '•';
        ?>
        <a href="sales_management.php?tab=orders&tt=<?= $k ?>" class="btn <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" 
           style="border-radius: 12px; font-size: 11px; border-color: <?= $isActive ? '' : 'var(--border)' ?>;">
            <?= $icon ?> <?= mb_strtoupper($v) ?> (<?= $cnt ?>)
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Filter Bar -->
<div class="section-card" style="margin-bottom: 30px; padding: 20px;">
    <form method="GET" class="filter-bar" style="margin-bottom: 0;">
        <input type="hidden" name="tt" value="<?= sanitize($tt) ?>">
        <input type="text" name="q" class="form-control" placeholder="🔍 Tìm theo Mã đơn hàng hoặc Tên khách hàng..." value="<?= sanitize($q) ?>" style="flex: 2;">
        <button type="submit" class="btn btn-primary">TÌM KIẾM</button>
        <a href="sales_management.php?tab=orders" class="btn btn-outline">LÀM MỚI</a>
    </form>
</div>

<!-- Modern Order Table -->
<div class="section-card">
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="padding-left: 30px;">MÃ ĐƠN</th>
                    <th>KHÁCH HÀNG</th>
                    <th>TỔNG THANH TOÁN</th>
                    <th>PHƯƠNG THỨC</th>
                    <th>TRẠNG THÁI</th>
                    <th>THANH TOÁN</th>
                    <th style="padding-right: 30px;">HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td style="padding-left: 30px;">
                        <span style="font-weight: 800; color: var(--accent); font-family: 'JetBrains Mono', monospace; letter-spacing: 0.5px;">
                            #<?= sanitize($o['ma_donhang_code']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="client-info-cell" style="align-items: center;">
                            <div class="client-name" style="font-weight: 800; color: var(--txt);"><?= sanitize($o['hovaten'] ?: $o['ten_user']) ?></div>
                            <div style="font-size: 11px; font-weight: 700; color: var(--txt3); margin-top: 2px;">
                                📱 <?= sanitize($o['SDT_nguoi_nhan']) ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 900; color: var(--txt); font-size: 15px;">
                            <?= number_format($o['tong_thanh_toan'],0,',','.') ?> VND
                        </div>
                    </td>
                    <td>
                        <?php 
                        $ptttLabel = $payMethodLabels[$o['phuong_thuc_TT']] ?? strtoupper($o['phuong_thuc_TT']);
                        $isOnline = ($o['phuong_thuc_TT'] !== 'cod');
                        ?>
                        <span class="badge <?= $isOnline ? 'badge-info' : 'badge-warning' ?>">
                            <?= $ptttLabel ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $statusMapping = [
                            'cho_xac_nhan' => ['badge-warning', '⏳ CHỜ XN'],
                            'da_xac_nhan' => ['badge-info', '✅ ĐÃ XN'],
                            'da_huy' => ['badge-danger', '❌ ĐÃ HỦY'],
                            'da_giao' => ['badge-success', '🏁 HOÀN THÀNH'],
                            'da_tra_hang' => ['badge-purple', '↩️ TRẢ HÀNG'],
                            'dang_giao' => ['badge-info', '🚚 ĐANG GIAO'],
                            'dang_dong_goi' => ['badge-purple', '📦 ĐÓNG GÓI'],
                        ];
                        $st = $statusMapping[$o['trang_thai']] ?? ['badge-gray', strtoupper($o['trang_thai'])];
                        ?>
                        <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
                    </td>
                    <td>
                        <?php 
                        $payMapping = [
                            'da_thanh_toan' => ['badge-success', '💰 ĐÃ THANH TOÁN'],
                            'da_hoan_tien' => ['badge-purple', '🔄 ĐÃ HOÀN TIỀN'],
                            'cho_thanh_toan' => ['badge-warning', '⏳ CHƯA TT'],
                            'that_bai' => ['badge-danger', '❌ THẤT BẠI'],
                        ];
                        $ps = $payMapping[$o['trang_thai_TT']] ?? ['badge-gray', strtoupper($o['trang_thai_TT'])];
                        ?>
                        <span class="badge <?= $ps[0] ?>"><?= $ps[1] ?></span>
                    </td>
                    <td style="padding-right: 30px;">
                        <div style="display:flex; gap:10px; justify-content: center; align-items: center;">
                            <!-- Form cập nhật trạng thái -->
                            <form method="POST" style="display:inline-flex;">
                                <input type="hidden" name="id" value="<?= $o['ma_donhang'] ?>">
                                <input type="hidden" name="action" value="update_status">
                                <?php $isLocked = in_array($o['trang_thai'], ['da_giao', 'da_huy', 'da_tra_hang']); ?>
                                <select name="trang_thai" class="badge" 
                                        style="border: 1px solid var(--border); background: #fff; cursor: pointer; text-align: center; font-family: inherit;"
                                        onchange="if(confirm('Xác nhận thay đổi trạng thái đơn hàng?')) this.form.submit();"
                                        <?= $isLocked ? 'disabled' : '' ?>>
                                    <?php foreach ($statusLabels as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $o['trang_thai']===$k?'selected':'' ?>><?= mb_strtoupper($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <a href="<?= BASE_URL ?>/admin/order_detail.php?id=<?= $o['ma_donhang'] ?>" class="btn-icon btn-outline" title="Chi tiết đơn hàng" style="display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                                👁️
                            </a>

                            <?php if ($o['trang_thai'] === 'da_giao'): ?>
                                <form method="POST" style="display:contents;">
                                    <input type="hidden" name="id" value="<?= $o['ma_donhang'] ?>">
                                    <button type="submit" name="action" value="refund_order" class="btn-icon btn-outline" title="Hoàn tiền / Trả hàng" style="color: var(--warning); border-color: #fffbeb;" onclick="return confirm('Xác nhận hoàn tiền cho đơn hàng này?')">
                                        ↩️
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($o['trang_thai'] === 'da_tra_hang' || $o['trang_thai'] === 'da_huy'): ?>
                                <form method="POST" style="display:contents;">
                                    <input type="hidden" name="id" value="<?= $o['ma_donhang'] ?>">
                                    <button type="submit" name="action" value="delete_order" class="btn-icon btn-outline" title="Xóa vĩnh viễn" style="color: var(--danger); border-color: #fef2f2;" onclick="return confirm('Xác nhận XÓA VĨNH VIỄN đơn hàng này?')">
                                        🗑️
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paging['total_pages'] > 1): ?>
    <div class="pagination" style="padding: 30px;">
        <?php for ($i = 1; $i <= min($paging['total_pages'], 10); $i++): ?>
        <a href="sales_management.php?tab=orders&tt=<?= $tt ?>&q=<?= urlencode($q) ?>&page=<?= $i ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
