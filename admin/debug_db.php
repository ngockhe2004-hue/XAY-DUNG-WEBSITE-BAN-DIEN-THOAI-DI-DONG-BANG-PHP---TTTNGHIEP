<?php
require_once __DIR__ . '/includes/auth_admin.php';

echo "<h1>DB Debug Info</h1>";

function showTable($name) {
    try {
        echo "<h2>Table: $name</h2>";
        $res = db()->fetchOne("SHOW CREATE TABLE $name");
        echo "<pre>" . htmlspecialchars($res['Create Table'] ?? 'Not found') . "</pre>";
        
        $count = db()->fetchColumn("SELECT COUNT(*) FROM $name");
        echo "<p>Total rows: $count</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}

showTable('danhmuc');
showTable('sanpham');
showTable('sanpham_danhmuc');

echo "<h2>Foreign Keys referencing 'danhmuc'</h2>";
try {
    $keys = db()->fetchAll("
        SELECT 
            TABLE_NAME, 
            COLUMN_NAME, 
            CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = 'bandienthoai' 
            AND REFERENCED_TABLE_NAME = 'danhmuc'
    ");
    echo "<table border='1'><tr><th>Table</th><th>Column</th><th>Constraint</th><th>Ref Table</th><th>Ref Col</th></tr>";
    foreach ($keys as $k) {
        echo "<tr><td>{$k['TABLE_NAME']}</td><td>{$k['COLUMN_NAME']}</td><td>{$k['CONSTRAINT_NAME']}</td><td>{$k['REFERENCED_TABLE_NAME']}</td><td>{$k['REFERENCED_COLUMN_NAME']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
