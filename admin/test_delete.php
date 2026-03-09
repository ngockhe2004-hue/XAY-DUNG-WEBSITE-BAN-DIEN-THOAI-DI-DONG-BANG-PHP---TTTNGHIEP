<?php
require_once __DIR__ . '/../config/database.php';

$id_to_delete = $_GET['id'] ?? null;

if (!$id_to_delete) {
    echo "Usage: test_delete.php?id=NUMBER";
    exit;
}

try {
    echo "<h1>Testing Deletion for Category ID: $id_to_delete</h1>";
    
    // 1. Check counts as per my current logic
    $usedInProducts = db()->fetchColumn("SELECT COUNT(*) FROM sanpham WHERE ma_danhmuc=? AND is_active >= 0", [$id_to_delete]);
    $usedInMapping  = db()->fetchColumn("SELECT COUNT(*) FROM sanpham_danhmuc spdm 
                                         JOIN sanpham sp ON spdm.ma_sanpham = sp.ma_sanpham 
                                         WHERE spdm.ma_danhmuc=? AND sp.is_active >= 0", [$id_to_delete]);
    
    echo "<p>Active products in main table: $usedInProducts</p>";
    echo "<p>Active products in mapping table: $usedInMapping</p>";
    
    if ($usedInProducts + $usedInMapping > 0) {
        echo "<p style='color:orange'>Warning: Still has active products. Deletion might be blocked in UI.</p>";
    }

    // 2. Try actual delete
    echo "<p>Attempting DELETE from sanpham_danhmuc...</p>";
    $r1 = db()->execute("DELETE FROM sanpham_danhmuc WHERE ma_danhmuc=?", [$id_to_delete]);
    echo "<p>Affected rows: $r1</p>";

    echo "<p>Attempting DELETE from danhmuc...</p>";
    $r2 = db()->execute("DELETE FROM danhmuc WHERE ma_danhmuc=?", [$id_to_delete]);
    echo "<p>Affected rows: $r2</p>";

    echo "<h2 style='color:green'>✅ Test completed successfully. Rows deleted: $r2</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
