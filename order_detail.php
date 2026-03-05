<?php
$pageTitle = 'Chi Tiết Đơn Hàng';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$order = db()->fetchOne("SELECT * FROM donhang WHERE ma_donhang = ? AND ma_user = ?", [$id, $_SESSION['user_site']['id']]);
if (!$order) { setFlash('error','Không tìm thấy đơn hàng'); redirect(BASE_URL . '/orders.php'); }

$items = db()->fetchAll("SELECT * FROM chitiet_donhang WHERE ma_donhang = ?", [$id]);
$statusLabels = [
    'cho_xac_nhan'=>'Chờ xác nhận','da_xac_nhan'=>'Đã xác nhận','dang_dong_goi'=>'Đang đóng gói',
    'dang_giao'=>'Đang giao','da_giao'=>'Đã giao','da_huy'=>'Đã hủy','da_hoan_tien'=>'Đã hoàn tiền',
    'da_tra_hang'=>'Đã trả hàng'
];
$steps = ['cho_xac_nhan','da_xac_nhan','dang_dong_goi','dang_giao','da_giao'];
$currentStep = array_search($order['trang_thai'], $steps);
?>

<div class="container" style="padding:32px 0 60px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <a href="<?= BASE_URL ?>/orders.php" style="font-size:13px;color:var(--text-muted);">← Đơn hàng</a>
            <h1 class="page-title" style="margin-top:8px;"><?= sanitize($order['ma_donhang_code']) ?></h1>
            <p style="color:var(--text-secondary);">Đặt lúc <?= date('H:i d/m/Y', strtotime($order['ngay_dat'])) ?></p>
        </div>
        <div style="text-align:right;">
            <span class="order-status-badge status-<?= $order['trang_thai'] ?>" style="display:inline-block;margin-bottom:12px;"><?= $statusLabels[$order['trang_thai']] ?? $order['trang_thai'] ?></span>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <?php if ($order['trang_thai'] === 'cho_xac_nhan'): ?>
                    <button onclick="handleOrder(<?= $order['ma_donhang'] ?>, 'cancel')" class="btn btn-danger btn-sm">Hủy đơn hàng</button>
                <?php endif; ?>

                <?php if (in_array($order['trang_thai'], ['da_giao', 'da_huy'])): ?>
                    <button onclick="handleOrder(<?= $order['ma_donhang'] ?>, 'rebuy')" class="btn btn-primary btn-sm">Mua lại đơn này</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Progress -->
    <?php if (!in_array($order['trang_thai'], ['da_huy', 'da_tra_hang', 'da_hoan_tien'])): ?>
    <div class="card" style="margin-bottom:24px;overflow:hidden;">
        <div style="display:flex;position:relative;padding:20px 0;">
            <div style="position:absolute;top:50%;left:10%;right:10%;height:2px;background:var(--border);transform:translateY(-50%);"></div>
            <?php foreach ($steps as $i => $step): 
                $done = $currentStep !== false && $i <= $currentStep;
                $label = ['Chờ XN','Đã XN','Đóng gói','Đang giao','Đã giao'][$i];
            ?>
            <div style="flex:1;text-align:center;position:relative;z-index:1;">
                <div style="width:36px;height:36px;border-radius:50%;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;font-size:14px;
                    background:<?= $done?'var(--accent)':'var(--border)' ?>;color:<?= $done?'#fff':'var(--text-muted)' ?>;border:2px solid <?= $done?'var(--accent)':'var(--border)' ?>;">
                    <?= $done ? '✓' : ($i+1) ?>
                </div>
                <div style="font-size:12px;color:<?= $done?'var(--accent)':'var(--text-muted)' ?>;font-weight:<?= $done?'600':'400' ?>;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="order-detail-grid">
        <div>
            <!-- Sản phẩm -->
            <div class="card" style="margin-bottom:16px;padding:0;overflow:hidden;">
                <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;">📦 Sản phẩm đặt</div>
                <?php foreach ($items as $item): ?>
                <div style="display:flex;gap:16px;padding:16px 20px;border-bottom:1px solid var(--border);">
                    <div style="width:64px;height:64px;background:var(--bg-secondary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;">📱</div>
                    <div style="flex:1;">
                        <div style="font-weight:600;margin-bottom:4px;"><?= sanitize($item['ten_sanpham'] ?? 'Sản phẩm') ?></div>
                        <div style="font-size:13px;color:var(--text-muted);"><?= $item['ram_gb'] ?? '' ?>RAM · <?= $item['rom_gb'] ?? '' ?>GB · <?= sanitize($item['mau_sac'] ?? '') ?> × <?= $item['so_luong'] ?></div>
                    </div>
                    <div style="font-weight:700;color:var(--accent);"><?= formatPrice($item['thanh_tien']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <!-- Thông tin giao hàng -->
            <div class="card" style="margin-bottom:16px;">
                <h4 style="margin-bottom:12px;">📍 Địa chỉ nhận hàng</h4>
                <p style="font-size:14px;font-weight:600;"><?= sanitize($order['ten_nguoi_nhan']) ?> · <?= sanitize($order['SDT_nguoi_nhan']) ?></p>
                <p style="font-size:14px;color:var(--text-secondary);margin-top:4px;"><?= sanitize($order['dia_chi_cu_the']) ?>, <?= sanitize($order['phuong_xa']) ?>, <?= sanitize($order['quan_huyen']) ?>, <?= sanitize($order['tinh_thanh']) ?></p>
            </div>
            <!-- Tổng tiền -->
            <div class="card">
                <h4 style="margin-bottom:16px;">💰 Thanh toán</h4>
                <?php
                $rows=[['Tạm tính',$order['tong_tien_hang']],['Phí ship',$order['phi_giao_hang']],['Giảm giá',-$order['so_tien_giam']]];
                foreach($rows as [$l,$v]): ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                    <span style="color:var(--text-secondary)"><?= $l ?></span>
                    <span><?= formatPrice($v) ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border);font-weight:700;font-size:17px;">
                    <span>Tổng</span><span style="color:var(--accent)"><?= formatPrice($order['tong_thanh_toan']) ?></span>
                </div>
                <div style="margin-top:10px;"><span class="badge badge-info"><?= strtoupper($order['phuong_thuc_TT']) ?></span></div>
            </div>
        </div>
    </div>
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
