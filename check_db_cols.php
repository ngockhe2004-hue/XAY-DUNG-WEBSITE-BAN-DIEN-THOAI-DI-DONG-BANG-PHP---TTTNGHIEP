<?php
require_once __DIR__ . '/admin/includes/auth_admin.php';

echo "<h2>Columns in 'donhang'</h2>";
$cols1 = db()->fetchAll("DESCRIBE donhang");
echo "<pre>"; print_r($cols1); echo "</pre>";

echo "<h2>Columns in 'chitiet_donhang'</h2>";
$cols2 = db()->fetchAll("DESCRIBE chitiet_donhang");
echo "<pre>"; print_r($cols2); echo "</pre>";

$test_order = db()->fetchOne("SELECT * FROM donhang LIMIT 1");
echo "<h2>Sample Order</h2>";
echo "<pre>"; print_r($test_order); echo "</pre>";
?>
