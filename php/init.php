<?php
require_once __DIR__ . '/php/config.php';

// Gọi hàm khởi tạo database và bảng
$pdo = initializeDatabase();

echo "✅ Database và bảng giao_dich đã sẵn sàng!";
?>