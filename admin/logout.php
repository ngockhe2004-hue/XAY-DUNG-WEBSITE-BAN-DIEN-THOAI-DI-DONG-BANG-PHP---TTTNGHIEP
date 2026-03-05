<?php
// Admin logout - chỉ xóa admin namespace, không đụng chạm session user
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
logout(); // Hàm logout() trong auth.php tự detect admin page và chỉ xóa admin_* keys
