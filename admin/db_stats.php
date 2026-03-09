<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Database Statistics</h1>";
    $tables = db()->fetchAll("SHOW TABLES");
    
    echo "<table border='1'><tr><th>Table Name</th><th>Record Count</th></tr>";
    foreach ($tables as $t) {
        $tableName = array_values($t)[0];
        $count = db()->fetchColumn("SELECT COUNT(*) FROM $tableName");
        echo "<tr><td>$tableName</td><td>$count</td></tr>";
    }
    echo "</table>";

    echo "<h2>Admin Users (Need to keep)</h2>";
    // Kiểm tra cấu trúc bảng users để lấy đúng tên cột
    $cols = array_column(db()->fetchAll("DESCRIBE users"), "Field");
    $nameCol = in_array('ho_ten', $cols) ? 'ho_ten' : (in_array('full_name', $cols) ? 'full_name' : 'ten_dang_nhap');
    
    $admins = db()->fetchAll("SELECT ma_user, $nameCol as name, email, vai_tro FROM users WHERE vai_tro = 'admin'");
    foreach ($admins as $admin) {
        echo "<pre>" . print_r($admin, true) . "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
