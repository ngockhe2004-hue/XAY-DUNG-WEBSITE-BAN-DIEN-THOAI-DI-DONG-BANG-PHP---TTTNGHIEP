<?php
$pageTitle = 'Báo cáo & Thống kê';
require_once __DIR__ . '/includes/auth_admin.php';

// Dữ liệu cho biểu đồ (7 ngày gần nhất)
$chartDays = [];
$revenueData = [];
$refundData = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $displayDate = date('d/m', strtotime($date));
    $chartDays[] = $displayDate;
    
    // Doanh thu (đã giao)
    $rev = db()->fetchColumn("SELECT SUM(tong_thanh_toan) FROM donhang WHERE DATE(ngay_dat) = ? AND trang_thai = 'da_giao'", [$date]) ?: 0;
    $revenueData[] = $rev;
    
    // Hoàn tiền (đã trả hàng)
    $ref = db()->fetchColumn("SELECT SUM(tong_thanh_toan) FROM donhang WHERE DATE(ngay_dat) = ? AND trang_thai = 'da_tra_hang'", [$date]) ?: 0;
    $refundData[] = $ref;
}

// Phân bổ trạng thái đơn hàng
$statusStats = db()->fetchAll("SELECT trang_thai, COUNT(*) as count FROM donhang GROUP BY trang_thai");
$statusData = [];
$statusLabels = [];
$statusColors = [
    'cho_xac_nhan' => '#f59e0b',
    'da_xac_nhan' => '#3b82f6',
    'dang_dong_goi' => '#ef4444',
    'dang_giao' => '#f87171',
    'da_giao' => '#10b981',
    'da_huy' => '#94a3b8',
    'da_tra_hang' => '#dc2626'
];
$statusNameMap = [
    'cho_xac_nhan' => 'Chờ XN',
    'da_xac_nhan' => 'Đã XN',
    'dang_dong_goi' => 'Đóng gói',
    'dang_giao' => 'Đang giao',
    'da_giao' => 'Đã giao',
    'da_huy' => 'Đã hủy',
    'da_tra_hang' => 'Trả hàng'
];

foreach ($statusStats as $ss) {
    if (isset($statusNameMap[$ss['trang_thai']])) {
        $statusLabels[] = $statusNameMap[$ss['trang_thai']];
        $statusData[] = $ss['count'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php

// Thống kê thẻ (Stats)
$totalRevenue   = db()->fetchColumn("SELECT COALESCE(SUM(tong_thanh_toan),0) FROM donhang WHERE trang_thai = 'da_giao'");
$refundRevenue  = db()->fetchColumn("SELECT COALESCE(SUM(tong_thanh_toan),0) FROM donhang WHERE trang_thai = 'da_tra_hang' OR trang_thai_TT = 'da_hoan_tien'");
$totalOrders    = db()->fetchColumn("SELECT COUNT(*) FROM donhang");
$failedOrders   = db()->fetchColumn("SELECT COUNT(*) FROM donhang WHERE trang_thai = 'da_huy'");
$totalUsers     = db()->fetchColumn("SELECT COUNT(*) FROM users WHERE quyen = 'customer'");
$totalProducts  = db()->fetchColumn("SELECT COUNT(*) FROM sanpham WHERE is_active = 1");
$lowStockCount  = db()->fetchColumn("SELECT COUNT(*) FROM bienthe_sanpham WHERE ton_kho <= 5 AND is_active = 1");

// Recent transactions (merged view)
$recentTransactions = db()->fetchAll("
    SELECT dh.ma_donhang, dh.ma_donhang_code, dh.trang_thai, dh.tong_thanh_toan, dh.ngay_dat, dh.phuong_thuc_TT, dh.SDT_nguoi_nhan,
           u.ten_user, u.hovaten
    FROM donhang dh JOIN users u ON dh.ma_user = u.ma_user
    ORDER BY dh.ngay_dat DESC LIMIT 10
");

// Top products
$topProducts = db()->fetchAll("
    SELECT sp.ten_sanpham, sp.ma_sanpham, sp.tong_da_ban,
           (SELECT MIN(gia) FROM bienthe_sanpham WHERE ma_sanpham = sp.ma_sanpham AND is_active=1) as gia_min,
           (SELECT image_url FROM hinhanh_sanpham h WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1 LIMIT 1) as anh
    FROM sanpham sp WHERE sp.is_active = 1 ORDER BY sp.tong_da_ban DESC LIMIT 5
");

// Low stock products
$lowStockProducts = db()->fetchAll("
    SELECT sp.ten_sanpham, bt.mau_sac, bt.ram_gb as ram, bt.rom_gb as rom, bt.ton_kho
    FROM bienthe_sanpham bt JOIN sanpham sp ON bt.ma_sanpham = sp.ma_sanpham
    WHERE bt.ton_kho <= 5 AND bt.is_active = 1
    ORDER BY bt.ton_kho ASC LIMIT 5
");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📊 TỔNG QUAN HỆ THỐNG</h1>
        <p class="page-desc">Theo dõi hoạt động kinh doanh và dòng tiền thời gian thực</p>
    </div>
    <button class="btn btn-primary" onclick="location.reload()">
        <span class="icon">🔄</span> LÀM MỚI DỮ LIỆU
    </button>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">💰</div>
        <div class="stat-info">
            <div class="label">Doanh thu thực</div>
            <div class="value"><?= number_format($totalRevenue,0,',','.') ?> VND</div>
            <div class="change" style="color: #10b981;">↑ Ổn định</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">🔄</div>
        <div class="stat-info">
            <div class="label">Tổng hoàn tiền</div>
            <div class="value"><?= number_format($refundRevenue,0,',','.') ?> VND</div>
            <div class="change" style="color: #ef4444;">Lưu ý rủi ro</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">📦</div>
        <div class="stat-info">
            <div class="label">Tổng đơn hàng</div>
            <div class="value"><?= number_format($totalOrders) ?></div>
            <div class="change" style="color: #3b82f6;"><?= $failedOrders ?> đơn đã hủy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">👥</div>
        <div class="stat-info">
            <div class="label">Khách hàng</div>
            <div class="value"><?= number_format($totalUsers) ?></div>
            <div class="change" style="color: #8b5cf6;">Đang tăng trưởng</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">🏷️</div>
        <div class="stat-info">
            <div class="label">Sản phẩm</div>
            <div class="value"><?= number_format($totalProducts) ?></div>
            <div class="change" style="color: #f59e0b;"><?= $lowStockCount ?> sắp hết hàng</div>
        </div>
    </div>
</div>

    <!-- Charts Section -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 35px;">
        <div class="section-card animate-fade-up">
            <div class="section-card-header">
                <h3>📈 DOANH THU & HOÀN TIỀN (7 NGÀY)</h3>
            </div>
            <div class="section-card-body">
                <canvas id="revenueChart" height="110"></canvas>
            </div>
        </div>
        <div class="section-card animate-fade-up" style="animation-delay: 0.1s;">
            <div class="section-card-header">
                <h3>📊 TRẠNG THÁI ĐƠN HÀNG</h3>
            </div>
            <div class="section-card-body">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="dash-grid">
    <!-- Recent Transactions -->
    <div class="section-card">
        <div class="section-card-header">
            <h3>💳 GIAO DỊCH MỚI NHẤT</h3>
            <a href="sales_management.php" class="btn btn-outline btn-sm">XEM TẤT CẢ</a>
        </div>
        <div class="section-card-body" style="padding: 0;">
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>MÃ ĐƠN</th>
                            <th>KHÁCH HÀNG</th>
                            <th>SỐ TIỀN</th>
                            <th>PT TT</th>
                            <th>TRẠNG THÁI</th>
                            <th>NGÀY GIỜ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $t): ?>
                        <tr>
                            <td><span style="font-weight: 800; color: var(--accent);">#<?= $t['ma_donhang'] ?></span></td>
                            <td>
                                <div style="font-weight: 700;"><?= sanitize($t['hovaten'] ?: $t['ten_user']) ?></div>
                                <div style="font-size: 11px; color: var(--txt3);"><?= $t['SDT_nguoi_nhan'] ?></div>
                            </td>
                            <td><span style="font-weight: 800; color: var(--accent);"><?= number_format($t['tong_thanh_toan'],0,',','.') ?> VND</span></td>
                            <td><span class="badge badge-info"><?= strtoupper($t['phuong_thuc_TT']) ?></span></td>
                            <td>
                                <?php 
                                $stMap = [
                                    'cho_xac_nhan' => ['badge-warning', 'CHỜ XN'],
                                    'da_xac_nhan' => ['badge-info', 'ĐÃ XN'],
                                    'dang_giao' => ['badge-info', 'ĐANG GIAO'],
                                    'da_giao' => ['badge-success', 'HOÀN THÀNH'],
                                    'da_huy' => ['badge-danger', 'ĐÃ HỦY'],
                                    'da_tra_hang' => ['badge-purple', 'TRẢ HÀNG']
                                ];
                                $s = $stMap[$t['trang_thai']] ?? ['badge-gray', $t['trang_thai']];
                                ?>
                                <span class="badge <?= $s[0] ?>"><?= $s[1] ?></span>
                            </td>
                            <td>
                                <div style="font-weight: 700;"><?= date('j/n/Y', strtotime($t['ngay_dat'])) ?></div>
                                <div style="font-size: 11px; color: var(--txt3);"><?= date('H:i', strtotime($t['ngay_dat'])) ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Side Column -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <!-- Top Products -->
        <div class="section-card">
            <div class="section-card-header">
                <h3>🏆 TOP BÁN CHẠY</h3>
            </div>
            <div class="section-card-body" style="padding: 10px 20px;">
                <?php foreach ($topProducts as $p): ?>
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid var(--border);">
                    <img src="<?= BASE_URL . '/uploads/products/' . ($p['anh'] ? basename($p['anh']) : 'default.jpg') ?>" 
                         style="width: 48px; height: 48px; border-radius: 12px; object-fit: contain; background: #f8fafc; border: 1px solid var(--border);">
                    <div style="flex: 1; overflow: hidden;">
                        <div style="font-weight: 800; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--txt);"><?= sanitize($p['ten_sanpham']) ?></div>
                        <div style="font-size: 11px; color: var(--txt3); font-weight: 600;">Đã bán <?= $p['tong_da_ban'] ?> lượt</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 900; color: var(--accent);"><?= number_format($p['gia_min']/1000000, 1) ?>M</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Stock Alerts -->
        <div class="section-card" style="border-top: 4px solid var(--danger);">
            <div class="section-card-header">
                <h3>⚠️ CẢNH BÁO KHO</h3>
                <span class="badge badge-danger"><?= $lowStockCount ?> MẶT HÀNG</span>
            </div>
            <div class="section-card-body" style="padding: 20px;">
                <?php if ($lowStockProducts): ?>
                    <?php foreach ($lowStockProducts as $lp): ?>
                    <div style="margin-bottom: 15px; background: #fef2f2; padding: 12px 18px; border-radius: 15px; border: 1px solid rgba(239, 68, 68, 0.05);">
                        <div style="font-size: 13px; font-weight: 800; color: #991b1b;"><?= sanitize($lp['ten_sanpham']) ?></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                            <span style="font-size: 11px; color: #b91c1c; font-weight: 600;"><?= $lp['mau_sac'] ?> | <?= $lp['ram'] ?>G/<?= $lp['rom'] ?>G</span>
                            <span class="badge badge-danger" style="padding: 2px 8px; font-size: 10px;">Còn: <?= $lp['ton_kho'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <span style="font-size: 40px;">🚀</span>
                        <p style="margin-top: 10px; font-weight: 700; color: var(--txt3);">Kho hàng ổn định!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ doanh thu
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartDays) ?>,
            datasets: [{
                label: 'Doanh thu (VND)',
                data: <?= json_encode($revenueData) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4
            }, {
                label: 'Hoàn tiền (VND)',
                data: <?= json_encode($refundData) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, font: { weight: '700' } } }
            },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // Biểu đồ trạng thái
    const stCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(stCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusLabels) ?>,
            datasets: [{
                data: <?= json_encode($statusData) ?>,
                backgroundColor: [
                    '#f59e0b', '#3b82f6', '#d70018', '#f87171', '#10b981', '#94a3b8', '#dc2626'
                ],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, font: { weight: '600' } } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
