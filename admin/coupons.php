<?php
require_once __DIR__ . '/includes/auth_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            trim($_POST['ma_code']),
            trim($_POST['ten_km']),
            $_POST['kieu_giam'],
            (float)$_POST['gia_tri_giam'],
            !empty($_POST['giam_toi_da']) ? (float)$_POST['giam_toi_da'] : null,
            (float)($_POST['don_toi_thieu'] ?? 0),
            !empty($_POST['so_lan_toi_da']) ? (int)$_POST['so_lan_toi_da'] : null,
            $_POST['ngay_bat_dau'],
            $_POST['ngay_ket_thuc'],
            isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($action === 'add') {
            try {
                db()->insert("INSERT INTO ma_khuyenmai (ma_code,ten_km,kieu_giam,gia_tri_giam,giam_toi_da,don_toi_thieu,so_lan_toi_da,ngay_bat_dau,ngay_ket_thuc,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)", $data);
                setFlash('success', 'Thêm mã khuyến mãi thành công!');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi: Mã code đã tồn tại.');
            }
        } else {
            $id = (int)$_POST['id'];
            $data[] = $id;
            db()->execute("UPDATE ma_khuyenmai SET ma_code=?,ten_km=?,kieu_giam=?,gia_tri_giam=?,giam_toi_da=?,don_toi_thieu=?,so_lan_toi_da=?,ngay_bat_dau=?,ngay_ket_thuc=?,is_active=? WHERE ma_km=?", $data);
            setFlash('success', 'Cập nhật mã KM thành công!');
        }
    }
    elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        db()->execute("UPDATE ma_khuyenmai SET is_active=!is_active WHERE ma_km=?", [$id]);
        setFlash('success', 'Đã cập nhật trạng thái!');
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db()->execute("DELETE FROM ma_khuyenmai WHERE ma_km=?", [$id]);
        setFlash('success', 'Đã xóa mã khuyến mãi!');
    }
    redirect(BASE_URL . '/admin/sales_management.php?tab=coupons');
}

$pageTitle = 'Mã Khuyến Mãi';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}

$coupons = db()->fetchAll("SELECT * FROM ma_khuyenmai ORDER BY ngay_tao DESC");
?>

<style>
/* CSS NHÚNG TRỰC TIẾP ĐỂ ĐẢM BẢO HIỂN THỊ */
.coupon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 30px;
    margin-top: 30px;
}
.coupon-card {
    background: #fff;
    border-radius: 30px !important;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(139, 92, 246, 0.08) !important;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    border: 1px solid rgba(139, 92, 246, 0.03) !important;
    position: relative;
    text-align: left;
}
.coupon-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 30px 60px rgba(139, 92, 246, 0.15) !important;
}
.coupon-card-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    padding: 35px 30px;
    position: relative;
    color: #fff;
}
.coupon-card-header .code {
    font-size: 28px;
    font-weight: 800;
    margin: 0;
    letter-spacing: 1.5px;
    color: #fff;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.status-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 14px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 800;
    margin-top: 15px;
    backdrop-filter: blur(4px);
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 10px #4ade80;
}
.discount-badge {
    position: absolute;
    top: 35px;
    right: 30px;
    background: #fff;
    color: #8b5cf6;
    width: 72px;
    height: 72px;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.discount-badge .val { font-size: 20px; font-weight: 900; line-height: 1; }
.discount-badge .unit { font-size: 10px; font-weight: 800; opacity: 0.6; margin-top: 3px; }

.coupon-card-body { padding: 30px; }
.coupon-info-row {
    background: #f8fafc;
    padding: 20px 24px;
    border-radius: 24px;
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}
.coupon-info-row .icon {
    font-size: 20px;
    background: #fff;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
}
.coupon-info-row .text .label { display: block; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
.coupon-info-row .text .value { font-size: 15px; font-weight: 800; color: #1e293b; }

.coupon-usage { margin-bottom: 30px; }
.coupon-usage .usage-header { display: flex; justify-content: space-between; margin-bottom: 12px; }
.coupon-usage .usage-header .label { font-size: 13px; font-weight: 800; color: #1e293b; }
.coupon-usage .usage-header .count { font-size: 14px; font-weight: 800; color: #8b5cf6; }
.coupon-progress { height: 12px; background: #f1f5f9; border-radius: 100px; overflow: hidden; }
.coupon-progress-bar { height: 100%; background: linear-gradient(90deg, #3b82f6 0%, #a855f7 100%); border-radius: 100px; }

.coupon-expiry { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; padding-top: 10px; }
.coupon-expiry .label-group { font-size: 13px; font-weight: 800; color: #64748b; display: flex; align-items: center; gap: 8px; }
.coupon-expiry .date-box { background: #f1f5f9; padding: 8px 16px; border-radius: 12px; font-size: 14px; font-weight: 800; color: #1e293b; }

.coupon-card-footer {
    display: flex;
    justify-content: center;
    gap: 40px;
    padding-top: 25px;
    border-top: 1px solid #f1f5f9;
}
.btn-action-text {
    background: none; border: none; color: #94a3b8; font-size: 14px; font-weight: 800;
    display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.3s;
}
.btn-action-text:hover { color: #8b5cf6; }
.btn-action-text.delete:hover { color: #ef4444; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">🎟️ QUẢN LÝ MÃ KHUYẾN MÃI</h1>
        <p class="page-desc">Tạo và quản lý các chương trình ưu đãi cho khách hàng</p>
    </div>
    <button class="btn btn-primary" onclick="toggleCouponForm('add')">
        <span class="icon">+</span> TẠO MÃ KM
    </button>
</div>

<!-- New Dynamic Coupon Form Section -->
<div id="couponFormCard" class="section-card animate-fade-up" style="display: none; margin-bottom: 30px; border-top: 4px solid var(--purple);">
    <div class="section-card-header">
        <h3 id="formTitle">✨ TẠO MÃ KHUYẾN MÃI MỚI</h3>
    </div>
    <div class="section-card-body">
        <form method="POST" id="couponForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="fId">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">MÃ CODE *</label>
                    <input type="text" name="ma_code" id="fCode" class="form-control" required placeholder="VD: WELCOME10" style="text-transform:uppercase">
                </div>
                <div class="form-group">
                    <label class="form-label">TÊN CHƯƠNG TRÌNH</label>
                    <input type="text" name="ten_km" id="fTen" class="form-control" placeholder="VD: Khuyến mãi mừng khai trương">
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">LOẠI GIẢM GIÁ *</label>
                    <select name="kieu_giam" id="fKieu" class="form-control">
                        <option value="phan_tram">Phần trăm (%)</option>
                        <option value="so_tien">Cố định (VND)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">GIÁ TRỊ GIẢM *</label>
                    <input type="number" name="gia_tri_giam" id="fGia" class="form-control" required min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">GIẢM TỐI ĐA (VND)</label>
                    <input type="number" name="giam_toi_da" id="fGiaMax" class="form-control" placeholder="Không giới hạn">
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">ĐƠN TỐI THIỂU (VND)</label>
                    <input type="number" name="don_toi_thieu" id="fDon" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">NGÀY BẮT ĐẦU *</label>
                    <input type="datetime-local" name="ngay_bat_dau" id="fFrom" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">NGÀY KẾT THÚC *</label>
                    <input type="datetime-local" name="ngay_ket_thuc" id="fTo" class="form-control" required>
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr; align-items: center;">
                <div class="form-group">
                    <label class="form-label">SỐ LẦN DÙNG TỐI ĐA</label>
                    <input type="number" name="so_lan_toi_da" id="fSoLan" class="form-control" placeholder="Để trống = Không giới hạn">
                </div>
                <div class="form-group" style="display: flex; gap: 15px; margin-top: 15px;">
                    <label class="status-toggle" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="fActive" value="1" checked style="width: 20px; height: 20px;">
                        <span style="font-weight: 700; font-size: 13px; color: var(--txt);">KÍCH HOẠT MÃ</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                <button type="button" class="btn btn-outline" onclick="toggleCouponForm(null)">HỦY BỎ</button>
                <button type="submit" class="btn btn-primary" style="min-width: 180px;">LƯU MÃ KHUYẾN MÃI</button>
            </div>
        </form>
    </div>
</div>

<div class="coupon-grid">
    <?php foreach ($coupons as $c): 
        $expired = strtotime($c['ngay_ket_thuc']) < time();
        $full    = $c['so_lan_toi_da'] && $c['so_lan_da_dung'] >= $c['so_lan_toi_da'];
        $isActive = ($c['is_active'] && !$expired && !$full);
        $percent = $c['so_lan_toi_da'] ? min(100, ($c['so_lan_da_dung'] / $c['so_lan_toi_da']) * 100) : 0;
        $valStr = $c['kieu_giam'] === 'phan_tram' ? (int)$c['gia_tri_giam'] . '%' : number_format($c['gia_tri_giam']/1000, 0) . 'k';
    ?>
    <div class="coupon-card">
        <div class="coupon-card-header">
            <h3 class="code"><?= sanitize($c['ma_code']) ?></h3>
            <div class="status-tag">
                <span class="status-dot" style="background: <?= $isActive ? '#4ade80' : '#ef4444' ?>"></span>
                <?= $isActive ? 'HOẠT ĐỘNG' : ($expired ? 'HẾT HẠN' : 'ĐÃ TẮT') ?>
            </div>
            <div class="discount-badge">
                <span class="val"><?= $valStr ?></span>
                <span class="unit">DISC.</span>
            </div>
        </div>
        
        <div class="coupon-card-body">
            <div class="coupon-info-row">
                <div class="icon">🎟️</div>
                <div class="text">
                    <span class="label">Yêu cầu tối thiểu</span>
                    <span class="value">Đơn từ <?= number_format($c['don_toi_thieu'], 0, ',', '.') ?> VND</span>
                </div>
            </div>

            <div class="coupon-usage">
                <div class="usage-header">
                    <span class="label">Lượt đã sử dụng</span>
                    <span class="count"><?= $c['so_lan_da_dung'] ?> / <?= $c['so_lan_toi_da'] ?? '∞' ?></span>
                </div>
                <div class="coupon-progress">
                    <div class="coupon-progress-bar" style="width: <?= $percent ?>%"></div>
                </div>
            </div>

            <div class="coupon-expiry">
                <div class="label-group">🕒 Hạn sử dụng</div>
                <div class="date-box"><?= date('j/n/Y', strtotime($c['ngay_ket_thuc'])) ?></div>
            </div>

            <div class="coupon-card-footer">
                <button class="btn-action-text" onclick='openEditCoupon(<?= json_encode($c) ?>)'>📝 SỬA</button>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xóa mã KM này?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $c['ma_km'] ?>">
                    <button type="submit" class="btn-action-text delete">🗑️ XÓA</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function toggleCouponForm(mode) {
    const card = document.getElementById('couponFormCard');
    const title = document.getElementById('formTitle');
    const action = document.getElementById('formAction');
    const form = document.getElementById('couponForm');
    
    if (mode === null) {
        card.style.display = 'none';
        return;
    }

    if (mode === 'add') {
        title.innerHTML = '✨ TẠO MÃ KHUYẾN MÃI MỚI';
        action.value = 'add';
        form.reset();
        document.getElementById('fId').value = '';
        
        // Mặc định ngày bắt đầu là hiện tại, kết thúc sau 1 năm
        const now = new Date();
        const nextYear = new Date(); nextYear.setFullYear(now.getFullYear() + 1);
        document.getElementById('fFrom').value = now.toISOString().slice(0,16);
        document.getElementById('fTo').value = nextYear.toISOString().slice(0,16);
    }
    
    card.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openEditCoupon(c) {
    toggleCouponForm('edit');
    document.getElementById('formTitle').innerHTML = '📝 CHỈNH SỬA MÃ KHUYẾN MÃI';
    document.getElementById('formAction').value = 'edit';
    
    document.getElementById('fId').value = c.ma_km;
    document.getElementById('fCode').value = c.ma_code;
    document.getElementById('fTen').value = c.ten_km || '';
    document.getElementById('fKieu').value = c.kieu_giam;
    document.getElementById('fGia').value = c.gia_tri_giam;
    document.getElementById('fGiaMax').value = c.giam_toi_da || '';
    document.getElementById('fDon').value = c.don_toi_thieu;
    document.getElementById('fFrom').value = c.ngay_bat_dau ? c.ngay_bat_dau.slice(0,16) : '';
    document.getElementById('fTo').value = c.ngay_ket_thuc ? c.ngay_ket_thuc.slice(0,16) : '';
    document.getElementById('fSoLan').value = c.so_lan_toi_da || '';
    document.getElementById('fActive').checked = c.is_active == 1;
}
</script>

<?php if (!isset($is_included_mode)): require_once __DIR__ . '/includes/footer.php'; endif; ?>
