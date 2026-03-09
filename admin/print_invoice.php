<?php
require_once __DIR__ . '/includes/auth_admin.php';

$id = (int)($_GET['id'] ?? 0);
$order = db()->fetchOne("SELECT dh.*, u.ten_user, u.hovaten, u.email FROM donhang dh JOIN users u ON dh.ma_user = u.ma_user WHERE dh.ma_donhang = ?", [$id]);

if (!$order) {
    die('Không tìm thấy đơn hàng');
}

$items = db()->fetchAll("SELECT * FROM chitiet_donhang WHERE ma_donhang = ?", [$id]);
$itemCount = count($items);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn <?= sanitize($order['ma_donhang_code']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap');
        
        :root {
            --primary-color: #000;
            --secondary-color: #555;
            --border-color: #000;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--primary-color);
            margin: 0;
            padding: 40px;
            background: #fff;
        }
        
        .invoice-wrapper {
            max-width: 900px;
            margin: auto;
        }
        
        /* Header styles matching BIKESTORE sample */
        .invoice-header {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr;
            margin-bottom: 20px;
            align-items: start;
        }
        
        .header-left .brand-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left .logo-icon {
            font-size: 40px;
        }
        
        .header-left .brand-text {
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        
        .header-left .brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 0;
        }
        
        .header-left .brand-slogan {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .header-middle {
            text-align: center;
        }
        
        .header-middle h1 {
            font-size: 26px;
            margin: 0 0 5px 0;
            font-weight: 800;
        }
        
        .header-middle .subtitle {
            font-size: 11px;
            font-style: italic;
            color: var(--secondary-color);
        }
        
        .header-right {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
        }
        
        .header-right .order-meta {
            margin-top: 10px;
            font-size: 11px;
        }
        
        /* Info boxes */
        .info-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-box {
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 4px;
        }
        
        .info-box h3 {
            font-size: 12px;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 5px;
            text-transform: uppercase;
            font-weight: 800;
        }
        
        .info-row {
            display: grid;
            grid-template-columns: 80px 1fr;
            margin-bottom: 6px;
        }
        
        .info-row .label {
            font-weight: 700;
        }
        
        /* Product table */
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-left: 4px solid #000;
            padding-left: 10px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .invoice-table th, .invoice-table td {
            border: 1px solid var(--border-color);
            padding: 10px;
            vertical-align: middle;
        }
        
        .invoice-table th {
            background: #f2f2f2;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .summary-row {
            font-weight: 700;
        }
        
        .summary-row td:first-child {
            border: none;
        }
        
        .grand-total-row {
            font-size: 15px;
            font-weight: 800;
        }
        
        .grand-total-row .total-label {
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Note and Signatures */
        .notes-box {
            background: #f9f9f9;
            border: 1px dashed #ccc;
            padding: 10px;
            font-size: 11px;
            margin-bottom: 30px;
        }
        
        .signature-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            margin-top: 40px;
        }
        
        .sig-block {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 120px;
        }
        
        .sig-title {
            font-weight: 800;
            text-transform: uppercase;
        }
        
        .sig-desc {
            font-size: 10px;
            font-style: italic;
            color: var(--secondary-color);
        }
        
        .sig-name {
            font-weight: 700;
            margin-top: auto;
            text-transform: uppercase;
            font-style: italic;
            font-size: 11px;
        }
        
        .invoice-footer-text {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            border-top: 1px solid #eee;
            padding-top: 15px;
            color: var(--secondary-color);
        }
        
        .print-actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
        }
        
        .btn-print {
            background: #000;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-size: 14px;
        }
        
        @media print {
            body { padding: 0; }
            .print-actions { display: none; }
            .invoice-wrapper { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="invoice-wrapper">
        <!-- HEADER -->
        <div class="invoice-header">
            <div class="header-left">
                <div class="brand-box">
                    <span class="logo-icon">📱</span>
                    <div class="brand-info">
                        <div class="brand-text">
                            <h2 class="brand-name">PHONESTORE</h2>
                            <div class="brand-slogan">Premium Electronics</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="header-middle">
                <h1>HÓA ĐƠN BÁN HÀNG</h1>
                <div class="subtitle">Hệ thống bán lẻ điện thoại cao cấp & Bảo hành uy tín</div>
            </div>
            
            <div class="header-right">
                <div>Quận Ninh Kiều, TP. Cần Thơ</div>
                <div>Hotline: 1800 6789</div>
                <div>Email: support@phonestore.vn</div>
                <div class="order-meta">
                    <strong>Số HD: #<?= sanitize($order['ma_donhang_code']) ?></strong><br>Ngày: <?= date('d/m/Y', strtotime($order['ngay_dat'])) ?>
                </div>
            </div>
        </div>

        <!-- INFO BOXES -->
        <div class="info-container">
            <div class="info-box">
                <h3>👤 Thông tin khách hàng</h3>
                <div class="info-row">
                    <span class="label">Họ tên:</span>
                    <span><?= sanitize($order['ten_nguoi_nhan']) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">SĐT:</span>
                    <span><?= sanitize($order['SDT_nguoi_nhan']) ?></span>
                </div>
            </div>
            <div class="info-box">
                <h3>🚚 Thông tin giao hàng</h3>
                <div class="info-row">
                    <span class="label">Địa chỉ:</span>
                    <span><?= sanitize($order['dia_chi_cu_the']) ?>, <?= sanitize($order['phuong_xa']) ?>, <?= sanitize($order['quan_huyen']) ?>, <?= sanitize($order['tinh_thanh']) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">PT TT:</span>
                    <span><?= strtoupper(sanitize($order['phuong_thuc_TT'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Voucher:</span>
                    <span><?= $order['ma_km'] ?: 'Không có' ?></span>
                </div>
            </div>
        </div>

        <!-- PRODUCT LIST -->
        <div class="section-title">Danh sách sản phẩm (<?= $itemCount ?> mặt hàng)</div>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-center" width="40">STT</th>
                    <th class="text-center" width="80">Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th class="text-center">Màu sắc</th>
                    <th class="text-center" width="40">SL</th>
                    <th class="text-right">Đơn giá (VND)</th>
                    <th class="text-right">Thành tiền (VND)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td class="text-center">#<?= $item['ma_bienthe'] ?></td>
                    <td><strong><?= sanitize($item['ten_sanpham']) ?></strong><br><small><?= $item['ram_gb'] ?>GB / <?= $item['rom_gb'] ?>GB</small></td>
                    <td class="text-center"><?= sanitize($item['mau_sac']) ?></td>
                    <td class="text-center"><?= $item['so_luong'] ?></td>
                    <td class="text-right"><?= number_format($item['don_gia'], 0, ',', '.') ?></td>
                    <td class="text-right"><strong><?= number_format($item['thanh_tien'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
                
                <!-- SUMMARY ROWS -->
                <tr class="summary-row">
                    <td colspan="5"></td>
                    <td class="text-right">Tiền hàng:</td>
                    <td class="text-right"><?= number_format($order['tong_tien_hang'], 0, ',', '.') ?></td>
                </tr>
                <tr class="summary-row">
                    <td colspan="5"></td>
                    <td class="text-right">Phí vận chuyển:</td>
                    <td class="text-right">+<?= number_format($order['phi_giao_hang'], 0, ',', '.') ?></td>
                </tr>
                <?php if ($order['so_tien_giam'] > 0): ?>
                <tr class="summary-row">
                    <td colspan="5"></td>
                    <td class="text-right">Giảm giá:</td>
                    <td class="text-right">-<?= number_format($order['so_tien_giam'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total-row">
                    <td colspan="5"></td>
                    <td class="text-right total-label">TỔNG CỘNG THANH TOÁN:</td>
                    <td class="text-right"><?= number_format($order['tong_thanh_toan'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- NOTES -->
        <div class="notes-box">
            <strong>Ghi chú:</strong> Hàng đã bán không được hoàn trả sau 7 ngày kể từ ngày giao. Vui lòng kiểm tra hàng hóa kỹ trước khi ký nhận. Mọi thắc mắc xin liên hệ Hotline: 1800 6789.
        </div>

        <!-- SIGNATURES -->
        <div class="signature-container">
            <div class="sig-block">
                <div class="sig-title">Khách hàng</div>
                <div class="sig-desc">(Ký, ghi rõ họ tên)</div>
                <div class="sig-name"></div>
            </div>
            <div class="sig-block">
                <div class="sig-title">Người giao hàng</div>
                <div class="sig-desc">(Ký, ghi rõ họ tên)</div>
                <div class="sig-name"></div>
            </div>
            <div class="sig-block">
                <div class="sig-title">Đại diện cửa hàng</div>
                <div class="sig-desc">(Ký, đóng dấu)</div>
                <div class="sig-name">PHONESTORE</div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="invoice-footer-text">
            Cám ơn quý khách đã mua hàng tại <strong>PHONESTORE</strong> — Quận Ninh Kiều, TP. Cần Thơ — 1800 6789
        </div>
    </div>

    <!-- PRINT BUTTON -->
    <div class="print-actions">
        <button onclick="window.print()" class="btn-print">🖨️ In hóa đơn (Ctrl+P)</button>
    </div>

</body>
</html>
