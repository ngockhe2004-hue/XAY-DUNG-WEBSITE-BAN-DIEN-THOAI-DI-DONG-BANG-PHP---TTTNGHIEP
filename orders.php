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
                <?php 
                $statusMapping = [
                    'cho_xac_nhan' => 'warning', 'da_xac_nhan' => 'info', 'dang_dong_goi' => 'purple',
                    'dang_giao' => 'info', 'da_giao' => 'success', 'da_huy' => 'danger',
                    'da_hoan_tien' => 'purple', 'da_tra_hang' => 'purple'
                ];
                $badgeType = $statusMapping[$order['trang_thai']] ?? 'gray';
                ?>
                <span class="badge badge-<?= $badgeType ?>"><?= $statusLabels[$order['trang_thai']] ?? $order['trang_thai'] ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;">
                <div>
                    <div style="font-size:14px;color:var(--text-secondary);"><?= $order['so_sp'] ?> sản phẩm</div>
                    <div style="font-size:14px;color:var(--text-muted);">Phương thức: <?= strtoupper($order['phuong_thuc_TT']) ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:18px;font-weight:700;color:var(--accent);"><?= formatPrice($order['tong_thanh_toan']) ?></div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                        <button onclick='trackOrder(<?= json_encode([
                            "id" => $order["ma_donhang"],
                            "code" => $order["ma_donhang_code"],
                            "status" => $order["trang_thai"]
                        ]) ?>)' class="btn btn-primary btn-sm" style="background:var(--info);border-color:var(--info);">Theo dõi</button>
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

    <!-- Modal Theo Dõi Timeline Mới -->
    <div id="trackOrderModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(8px);padding:20px;">
        <div style="background:var(--bg-card);width:100%;max-width:500px;border-radius:24px;padding:32px;position:relative;box-shadow:0 20px 50px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
            <button onclick="closeTrackModal()" style="position:absolute;top:20px;right:20px;background:var(--bg-secondary);border:none;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-secondary);font-size:20px;transition:0.2s;">&times;</button>
            
            <div style="text-align:center;margin-bottom:30px;">
                <h2 id="modalOrderCode" style="font-size:24px;margin-bottom:8px;background:linear-gradient(45deg, var(--accent), #ff8a00);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:800;">Theo Dõi</h2>
                <p style="color:var(--text-secondary);font-size:14px;">Trạng thái vận chuyển chi tiết</p>
            </div>
            
            <div id="timelineContainer" style="position:relative;margin-left:20px;padding-left:30px;border-left:2px dashed var(--border);">
                <!-- Timeline inject here -->
            </div>

            <div id="cancelWarning" style="display:none;margin-top:20px;padding:12px;background:rgba(239,68,68,0.1);border-radius:12px;color:var(--danger);font-size:13px;text-align:center;">
                ⚠️ Đơn hàng đã bị hủy.
            </div>
            <button onclick="closeTrackModal()" class="btn btn-primary" style="width:100%;margin-top:24px;border-radius:12px;">Đóng</button>
        </div>
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

function closeTrackModal() { document.getElementById('trackOrderModal').style.display = 'none'; }

window.onclick = function(event) {
    if (event.target == document.getElementById('trackOrderModal')) closeTrackModal();
}

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
            if (action === 'rebuy') window.location.href = '<?= BASE_URL ?>/cart.php';
            else window.location.reload();
        } else alert(result.message || 'Có lỗi xảy ra');
    } catch (e) { alert('Lỗi kết nối server'); }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
