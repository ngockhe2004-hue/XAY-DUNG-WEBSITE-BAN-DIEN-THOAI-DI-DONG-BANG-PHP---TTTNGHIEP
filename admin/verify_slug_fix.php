<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Verification of Unique Slug</h1>";
    $products = db()->fetchAll("SELECT ma_sanpham, ten_sanpham, slug, is_active FROM sanpham WHERE ten_sanpham = 'iPhone 16' ORDER BY ma_sanpham DESC");
    
    echo "<h2>Products named 'iPhone 16':</h2>";
    foreach ($products as $p) {
        echo "<pre>" . print_r($p, true) . "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
