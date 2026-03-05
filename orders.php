<?php
$pageTitle = 'Lịch Sử Đơn Hàng';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = $_SESSION['user_site']['id'];
$page   = max(1, (int)($_GET['page'] ?? 1));
$filter = sanitize($_GET['tt'] ?? '');
$whereExtra = $filter ? "AND dh.trang_thai = '$filter'" : '';

$total = (int)db()->fetchColumn("SELECT COUNT(*) FROM donhang dh WHERE dh.ma_user = ? $whereExtra", [$userId]);
$paging = paginate($total, $page, ORDERS_PER_PAGE);

$orders = db()->fetchAll("
    SELECT dh.*, COUNT(ctdh.ma_ctdh) as so_sp
    FROM donhang dh
    LEFT JOIN chitiet_donhang ctdh ON dh.ma_donhang = ctdh.ma_donhang
    WHERE dh.ma_user = ? $whereExtra
    GROUP BY dh.ma_donhang
    ORDER BY dh.ngay_dat DESC
    LIMIT {$paging['per_page']} OFFSET {$paging['offset']}
", [$userId]);

$statusLabels = [
    'cho_xac_nhan'=>'Chờ xác nhận','da_xac_nhan'=>'Đã xác nhận','dang_dong_goi'=>'Đang đóng gói',
    'dang_giao'=>'Đang giao','da_giao'=>'Đã giao','da_huy'=>'Đã hủy','da_hoan_tien'=>'Đã hoàn tiền',
    'da_tra_hang'=>'Đã trả hàng'
];
?>

<div class="container" style="padding:32px 0 60px;">
    <h1 class="page-title">📋 Đơn Hàng Của Tôi</h1>
    
    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
        <a href="?tt=" class="btn btn-sm <?= !$filter?'btn-primary':'btn-outline' ?>">Tất cả</a>
        <?php foreach ($statusLabels as $k => $v): ?>
        <a href="?tt=<?= $k ?>" class="btn btn-sm <?= $filter===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3 class="empty-title">Chưa có đơn hàng nào</h3>
        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary" style="margin-top:16px;">Mua sắm ngay →</a>
    </div>
    <?php else: ?>
    
    <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($orders as $order): ?>
        <div class="card" style="padding:0;overflow:hidden;" id="order-<?= $order['ma_donhang'] ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);background:var(--bg-secondary);">
                <div style="font-size:14px;">
                    <span style="font-weight:700;color:var(--accent);"><?= sanitize($order['ma_donhang_code']) ?></span>
                    <span style="color:var(--text-muted);margin-left:12px;"><?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?></span>
                </div>
                <span class="order-status-badge status-<?= $order['trang_thai'] ?>"><?= $statusLabels[$order['trang_thai']] ?? $order['trang_thai'] ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;">
                <div>
                    <div style="font-size:14px;color:var(--text-secondary);"><?= $order['so_sp'] ?> sản phẩm</div>
                    <div style="font-size:14px;color:var(--text-muted);">Phương thức: <?= strtoupper($order['phuong_thuc_TT']) ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:18px;font-weight:700;color:var(--accent);"><?= formatPrice($order['tong_thanh_toan']) ?></div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                        <a href="<?= BASE_URL ?>/order_detail.php?id=<?= $order['ma_donhang'] ?>" class="btn btn-outline btn-sm">Chi tiết</a>
                        
                        <?php if ($order['trang_thai'] === 'cho_xac_nhan'): ?>
                            <button onclick="handleOrder(<?= $order['ma_donhang'] ?>, 'cancel')" class="btn btn-danger btn-sm">Hủy đơn</button>
                        <?php endif; ?>

                        <?php if (in_array($order['trang_thai'], ['da_giao', 'da_huy'])): ?>
                            <button onclick="handleOrder(<?= $order['ma_donhang'] ?>, 'rebuy')" class="btn btn-primary btn-sm">Mua lại</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($paging['total_pages'] > 1): ?>
    <div class="pagination" style="margin-top:32px;">
        <?php for ($i = 1; $i <= $paging['total_pages']; $i++): ?>
        <a href="?tt=<?= $filter ?>&page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
async function handleOrder(id, action) {
    if (action === 'cancel' && !confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) return;
    
    try {
        const method = action === 'cancel' ? 'PUT' : 'POST';
        const response = await fetch('<?= BASE_URL ?>/api/orders.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, action: action })
        });
        
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            if (action === 'rebuy') {
                window.location.href = '<?= BASE_URL ?>/cart.php';
            } else {
                window.location.reload();
            }
        } else {
            alert(result.message || 'Có lỗi xảy ra');
        }
    } catch (e) {
        console.error(e);
        alert('Lỗi kết nối server');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
