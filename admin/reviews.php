<?php
require_once __DIR__ . '/includes/auth_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id']; $action = $_POST['action'] ?? '';
    if ($action === 'approve') db()->execute("UPDATE danhgia SET trang_thai='da_duyet' WHERE ma_danhgia=?", [$id]);
    if ($action === 'reject')  db()->execute("UPDATE danhgia SET trang_thai='da_tu_choi' WHERE ma_danhgia=?", [$id]);
    if ($action === 'delete')  db()->execute("DELETE FROM danhgia WHERE ma_danhgia=?", [$id]);
    setFlash('success','Đã xử lý đánh giá');
    redirect(BASE_URL . '/admin/customer_management.php?tab=reviews');
}

$pageTitle = 'Duyệt Đánh Giá';
require_once __DIR__ . '/includes/auth_admin.php';
if (!isset($is_included_mode)) {
    require_once __DIR__ . '/includes/header.php';
}


$tt   = sanitize($_GET['tt'] ?? 'cho_duyet');
$page = max(1,(int)($_GET['page']??1));
$where = $tt ? "WHERE dg.trang_thai = ?" : "WHERE 1=1";
$params = $tt ? [$tt] : [];
$total = (int)db()->fetchColumn("SELECT COUNT(*) FROM danhgia dg $where", $params);
$paging = paginate($total, $page, ADMIN_PER_PAGE);

$reviews = db()->fetchAll("
    SELECT dg.*, u.ten_user, u.hovaten, sp.ten_sanpham
    FROM danhgia dg JOIN users u ON dg.ma_user = u.ma_user JOIN sanpham sp ON dg.ma_sanpham = sp.ma_sanpham
    $where ORDER BY dg.ngay_lap DESC LIMIT {$paging['per_page']} OFFSET {$paging['offset']}
", $params);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⭐ DUYỆT ĐÁNH GIÁ</h1>
        <p class="page-desc">Hệ thống đang lưu trữ <strong><?= $total ?></strong> phản hồi từ khách hàng</p>
    </div>
</div>

<!-- Review Status Tabs -->
<div class="section-card" style="margin-bottom: 30px; padding: 15px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php foreach (['cho_duyet'=>'⏳ CHỜ DUYỆT','da_duyet'=>'✅ ĐÃ DUYỆT','da_tu_choi'=>'❌ TỪ CHỐI'] as $k=>$v): 
            $isActive = ($tt === $k);
        ?>
        <a href="customer_management.php?tab=reviews&tt=<?= $k ?>" class="btn <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 12px; font-size: 11px;">
            <?= $v ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="section-card">
    <div class="section-card-body" style="padding: 0;">
        <?php if (empty($reviews)): ?>
        <div style="text-align:center; padding:80px; color:var(--txt3); font-weight: 700;">
            📭 CHƯA CÓ ĐÁNH GIÁ NÀO TRONG MỤC NÀY
        </div>
        <?php else: ?>
        <?php foreach ($reviews as $r): ?>
        <div class="review-item" style="padding:25px 30px; border-bottom:1px solid var(--border); transition: 0.3s; cursor: default;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div style="width:40px; height:40px; border-radius:12px; background: var(--purple-grad); color: #fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:16px;">
                        <?= mb_strtoupper(mb_substr($r['hovaten'] ?: $r['ten_user'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 800; color: var(--txt);"><?= sanitize($r['hovaten'] ?: $r['ten_user']) ?></div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--accent);">Đánh giá: <strong style="color: var(--txt);"><?= sanitize($r['ten_sanpham']) ?></strong></div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="color:#f59e0b; font-size:16px; letter-spacing: 2px;">
                        <?= str_repeat('★',$r['diem']) ?><span style="color: #e2e8f0;"><?= str_repeat('★',5-$r['diem']) ?></span>
                    </div>
                    <div style="font-size:11px; font-weight: 600; color:var(--txt3); margin-top: 5px;"><?= date('d/m/Y H:i',strtotime($r['ngay_lap'])) ?></div>
                </div>
            </div>

            <div style="padding-left: 55px;">
                <?php if ($r['tieu_de']): ?>
                    <div style="font-weight:800; margin-bottom:8px; color: var(--txt); font-size: 15px;">"<?= sanitize($r['tieu_de']) ?>"</div>
                <?php endif; ?>
                <div style="font-size:14px; color:var(--txt2); line-height: 1.6; font-style: italic;">
                    <?= sanitize($r['noi_dung']) ?>
                </div>

                <div style="display:flex; gap:12px; margin-top:20px;">
                    <?php if ($tt === 'cho_duyet'): ?>
                        <form method="POST"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $r['ma_danhgia'] ?>"><button class="btn btn-sm btn-primary" style="font-size: 10px; padding: 6px 15px;">✅ CHẤP NHẬN</button></form>
                        <form method="POST"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= $r['ma_danhgia'] ?>"><button class="btn btn-sm btn-outline" style="font-size: 10px; padding: 6px 15px;">❌ TỪ CHỐI</button></form>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('⚠️ Chắc chắn muốn XÓA vĩnh viễn đánh giá này?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['ma_danhgia'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline" style="font-size: 10px; padding: 6px 15px; color: var(--danger); border-color: #fee2e2;">🗑️ XÓA BỎ</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!isset($is_included_mode)): ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
