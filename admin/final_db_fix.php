<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Database Fix: Ultimate Category Deletion Fix</h1>";
    
    // 1. Cho phép ma_danhmuc là NULL
    echo "<p>Modifying <code>sanpham</code> table to allow <code>NULL</code> for <code>ma_danhmuc</code>...</p>";
    db()->execute("ALTER TABLE sanpham MODIFY ma_danhmuc INT UNSIGNED NULL");
    
    // 2. Xóa ràng buộc cũ (nếu có)
    echo "<p>Resetting foreign key <code>fk_sp_danhmuc</code> to <code>ON DELETE SET NULL</code>...</p>";
    try {
        db()->execute("ALTER TABLE sanpham DROP FOREIGN KEY fk_sp_danhmuc");
    } catch(Exception $e) {
        echo "<p>Notice: Old key not found or error dropping (skipping)...</p>";
    }
    
    // 3. Thêm ràng buộc mới với ON DELETE SET NULL
    db()->execute("ALTER TABLE sanpham ADD CONSTRAINT fk_sp_danhmuc FOREIGN KEY (ma_danhmuc) REFERENCES danhmuc(ma_danhmuc) ON DELETE SET NULL");
    
    echo "<h2 style='color:green'>✅ Database updated successfully!</h2>";
    echo "<p>Now you can delete categories even if they have soft-deleted products.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
