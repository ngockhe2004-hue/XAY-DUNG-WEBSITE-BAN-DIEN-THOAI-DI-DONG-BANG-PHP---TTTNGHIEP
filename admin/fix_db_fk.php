<?php
require_once __DIR__ . '/includes/auth_admin.php';

try {
    echo "<h1>Database Fix: Updating Foreign Keys</h1>";
    
    // 1. Xóa ràng buộc cũ trên bảng sanpham
    echo "<p>Removing old constraint <code>fk_sp_danhmuc</code>...</p>";
    db()->execute("ALTER TABLE sanpham DROP FOREIGN KEY fk_sp_danhmuc");
    
    // 2. Thêm lại ràng buộc với ON DELETE CASCADE
    echo "<p>Adding new constraint with <code>ON DELETE CASCADE</code>...</p>";
    db()->execute("ALTER TABLE sanpham ADD CONSTRAINT fk_sp_danhmuc FOREIGN KEY (ma_danhmuc) REFERENCES danhmuc(ma_danhmuc) ON DELETE CASCADE");
    
    echo "<h2 style='color:green'>✅ Fix Applied Successfully!</h2>";
    echo "<p><a href='categories.php'>Quay lại trang danh mục để thử xóa.</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error applying fix:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
