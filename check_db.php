<?php
require_once __DIR__ . '/config/database.php';
$columns = db()->fetchAll("DESCRIBE users");
header('Content-Type: application/json');
echo json_encode($columns, JSON_PRETTY_PRINT);
?>
