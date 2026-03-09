<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>DB Integrity Check</h1>";
    
    // Tìm tất cả các bảng có cột ma_danhmuc
    $tables = db()->fetchAll("SELECT TABLE_NAME, COLUMN_NAME 
                              FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE COLUMN_NAME = 'ma_danhmuc' 
                              AND TABLE_SCHEMA = 'bandienthoai'");
    
    echo "<h2>Tables using 'ma_danhmuc':</h2><ul>";
    foreach ($tables as $t) {
        $count = db()->fetchColumn("SELECT COUNT(*) FROM {$t['TABLE_NAME']} WHERE ma_danhmuc IS NOT NULL");
        echo "<li>Table: <b>{$t['TABLE_NAME']}</b> (Column: {$t['COLUMN_NAME']}) - Total rows with data: $count</li>";
    }
    echo "</ul>";

    // Kiểm tra Foreign Keys
    echo "<h2>Foreign Keys referencing 'danhmuc':</h2>";
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
    echo "<table border='1'><tr><th>Table</th><th>Column</th><th>Constraint</th></tr>";
    foreach ($keys as $k) {
        echo "<tr><td>{$k['TABLE_NAME']}</td><td>{$k['COLUMN_NAME']}</td><td>{$k['CONSTRAINT_NAME']}</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
