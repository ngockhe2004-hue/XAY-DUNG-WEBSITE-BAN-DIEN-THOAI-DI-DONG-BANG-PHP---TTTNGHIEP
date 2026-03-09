<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Slug Search: 'iphone-16'</h1>";
    
    $products = db()->fetchAll("SELECT ma_sanpham, ten_sanpham, slug, is_active FROM sanpham WHERE slug = 'iphone-16'");
    echo "<h2>Products with slug 'iphone-16':</h2>";
    if (empty($products)) {
        echo "<p>None found in sanpham.</p>";
    } else {
        foreach ($products as $p) {
            echo "<pre>" . print_r($p, true) . "</pre>";
        }
    }

    $categories = db()->fetchAll("SELECT ma_danhmuc, ten_danhmuc, slug FROM danhmuc WHERE slug = 'iphone-16'");
    echo "<h2>Categories with slug 'iphone-16':</h2>";
    if (empty($categories)) {
        echo "<p>None found in danhmuc.</p>";
    } else {
        foreach ($categories as $c) {
            echo "<pre>" . print_r($c, true) . "</pre>";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
