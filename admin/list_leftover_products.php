<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Remaining Products Check</h1>";
    $products = db()->fetchAll("SELECT ma_sanpham, ten_sanpham, ma_danhmuc, is_active FROM sanpham");
    
    if (empty($products)) {
        echo "<p>No products found in 'sanpham' table.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Category ID</th><th>Active</th></tr>";
        foreach ($products as $p) {
            echo "<tr><td>{$p['ma_sanpham']}</td><td>{$p['ten_sanpham']}</td><td>{$p['ma_danhmuc']}</td><td>{$p['is_active']}</td></tr>";
        }
        echo "</table>";
    }

    echo "<h2>Mapping table (sanpham_danhmuc)</h2>";
    $mapping = db()->fetchAll("SELECT * FROM sanpham_danhmuc");
    echo "<table border='1'><tr><th>Product ID</th><th>Category ID</th></tr>";
    foreach ($mapping as $m) {
        echo "<tr><td>{$m['ma_sanpham']}</td><td>{$m['ma_danhmuc']}</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
