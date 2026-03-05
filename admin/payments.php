<?php
$pageTitle = 'Thanh Toán';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = ADMIN_PER_PAGE;
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['q'] ?? '');
$status  = $_GET['tt'] ?? '';
$method  = $_GET['pt'] ?? '';

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = "(dh.ma_donhang_code LIKE ? OR u.ten_user LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($status) { $where[] = "tt.trang_thai=?"; $params[] = $status; }
if ($method) { $where[] = "tt.phuong_thuc=?"; $params[] = $method; }

$whereStr = implode(' AND ', $where);
$total = db()->fetchColumn("SELECT COUNT(*) FROM thanhtoan tt LEFT JOIN donhang dh ON tt.ma_donhang=dh.ma_donhang LEFT JOIN users u ON dh.ma_user=u.ma_user WHERE $whereStr", $params);
$payments = db()->fetchAll("
    SELECT tt.*, dh.ma_donhang_code, dh.trang_thai as don_tt, u.ten_user, u.hovaten
    FROM thanhtoan tt
    LEFT JOIN donhang dh ON tt.ma_donhang = dh.ma_donhang
    LEFT JOIN users u ON dh.ma_user = u.ma_user
    WHERE $whereStr ORDER BY tt.ngay_thanhtoan DESC LIMIT $perPage OFFSET $offset", $params);

$totalPages = ceil($total / $perPage);
$ptLabel = ['cod'=>'COD','momo'=>'MoMo','vnpay'=>'VNPay','chuyen_khoan'=>'Chuyển khoản','zalopay'=>'ZaloPay','visa_mastercard'=>'Visa/Master'];
$ttColor = ['pending'=>'warning','success'=>'success','failed'=>'danger','refunded'=>'info'];
$ttLabel = ['pending'=>'Chờ TT','success'=>'Đã TT','failed'=>'Thất bại','refunded'=>'Hoàn tiền'];
?>
<!-- Payments Layout Section -->
<div class="page-header">
    <div>
        <h1 class="page-title">💳 QUẢN LÝ THANH TOÁN</h1>
        <p class="page-desc">Theo dõi và kiểm soát các giao dịch tài chính của hệ thống</p>
    </div>
    <div style="font-size: 13px; color: var(--txt2); background: var(--card); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow);">
        Tổng cộng: <strong style="color: var(--accent); font-size: 16px;"><?= number_format($total) ?></strong> giao dịch
    </div>
</div>

<!-- Filter Section -->
<div class="section-card animate-fade-up" style="margin-bottom: 25px; padding: 20px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="tab" value="payments">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
            <label class="form-label">TÌM KIẾM</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Mã đơn, username..." class="form-control">
        </div>
        <div class="form-group" style="margin-bottom: 0; width: 180px;">
            <label class="form-label">TRẠNG THÁI</label>
            <select name="tt" class="form-control">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($ttLabel as $v => $l): ?>
                <option value="<?= $v ?>" <?= $status==$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; width: 180px;">
            <label class="form-label">PHƯƠNG THỨC</label>
            <select name="pt" class="form-control">
                <option value="">Tất cả PTTT</option>
                <?php foreach ($ptLabel as $v => $l): ?>
                <option value="<?= $v ?>" <?= $method==$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">🔍 LỌC</button>
            <a href="sales_management.php?tab=payments" class="btn btn-outline">LÀM MỚI</a>
        </div>
    </form>
</div>

<!-- Table Section -->
<div class="section-card animate-fade-up">
    <div class="section-card-body" style="padding: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="padding-left: 20px; text-align:center;">Mã giao dịch</th>
                    <th style="text-align:center;">Mã đơn hàng</th>
                    <th style="text-align:center;">Khách hàng</th>
                    <th style="text-align:center;">Số tiền</th>
                    <th style="text-align:center;">Phương thức</th>
                    <th style="text-align:center;">Trạng thái</th>
                    <th style="padding-right: 20px; text-align:center;">Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments): foreach ($payments as $p): ?>
                <tr>
                    <td style="padding-left: 20px; text-align:center;">
                        <code style="background: var(--card2); color: var(--accent); padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                            <?= sanitize($p['ma_giao_dich'] ?: 'COD-MANUAL') ?>
                        </code>
                    </td>
                    <td style="text-align:center;">
                        <a href="<?= BASE_URL ?>/admin/order_detail.php?id=<?= $p['ma_donhang'] ?>" style="font-weight: 600; text-decoration: none; border-bottom: 1px dashed var(--border);">
                            <?= sanitize($p['ma_donhang_code']) ?>
                        </a>
                    </td>
                    <td style="text-align:center;">
                        <div style="font-weight: 700; color: var(--txt);"><?= sanitize($p['hovaten'] ?: $p['ten_user']) ?></div>
                        <div style="font-size: 11px; color: var(--txt3);">@<?= sanitize($p['ten_user']) ?></div>
                    </td>
                    <td style="text-align:center;">
                        <span style="color: var(--accent); font-weight: 800; font-size: 15px;">
                            <?= number_format($p['so_tien'], 0, ',', '.') ?>
                        </span>
                        <span style="font-size: 11px; color: var(--txt3); margin-left: 2px;">VND</span>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; align-items: center; gap: 6px; justify-content: center;">
                            <span class="badge badge-purple" style="font-size: 12px;"><?= $ptLabel[$p['phuong_thuc']] ?? $p['phuong_thuc'] ?></span>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-<?= $ttColor[$p['trang_thai']] ?? 'gray' ?>" style="text-transform: uppercase;">
                            <?= $ttLabel[$p['trang_thai']] ?? $p['trang_thai'] ?>
                        </span>
                    </td>
                    <td style="padding-right: 20px; text-align:center;">
                        <div style="font-size: 12px; color: var(--txt2);"><?= date('H:i', strtotime($p['ngay_thanhtoan'])) ?></div>
                        <div style="font-size: 11px; color: var(--txt3);"><?= date('d/m/Y', strtotime($p['ngay_thanhtoan'])) ?></div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px; color: var(--txt3);">
                        <div style="font-size: 40px; margin-bottom: 10px;">📂</div>
                        Chưa có dữ liệu giao dịch nào được tìm thấy
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="sales_management.php?tab=payments&q=<?= urlencode($search) ?>&tt=<?= $status ?>&pt=<?= $method ?>&page=<?= $i ?>"
       class="page-btn <?= $i==$page ? 'active' : '' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php if (!isset($is_included_mode)): ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
