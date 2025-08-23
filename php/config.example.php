<?php
// Cấu hình kết nối cơ sở dữ liệu - FILE MẪU
// Copy file này thành config.php và điền thông tin thực tế

define('DB_HOST', 'localhost');           // Địa chỉ database server
define('DB_NAME', 'savemoney_db');        // Tên database
define('DB_USER', 'your_username');       // Username database
define('DB_PASS', 'your_password');       // Password database
define('DB_CHARSET', 'utf8mb4');

// Tạo kết nối PDO
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException('Kết nối cơ sở dữ liệu thất bại: ' . $e->getMessage());
    }
}

// Khởi tạo database và bảng nếu chưa có
function initializeDatabase() {
    try {
        $pdo = getConnection();
        
        // Kiểm tra và tạo bảng giao_dich nếu chưa có
        $checkTable = $pdo->query("SHOW TABLES LIKE 'giao_dich'");
        if ($checkTable->rowCount() == 0) {
            $createTable = "CREATE TABLE giao_dich (
                id INT AUTO_INCREMENT PRIMARY KEY,
                loai ENUM('income', 'expense') NOT NULL,
                danh_muc VARCHAR(100) NOT NULL,
                so_tien DECIMAL(15,2) NOT NULL,
                ngay DATE NOT NULL,
                ghi_chu TEXT,
                ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ngay (ngay),
                INDEX idx_loai (loai),
                INDEX idx_danh_muc (danh_muc)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createTable);
            
            // Thêm dữ liệu mẫu
            insertSampleData($pdo);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException('Lỗi khởi tạo database: ' . $e->getMessage());
    }
}

// Thêm dữ liệu mẫu
function insertSampleData($pdo) {
    $sampleData = [
        ['expense', 'Ăn uống', 250000, '2024-12-18', 'Ăn trưa tại Pizza Hut'],
        ['expense', 'Di chuyển', 500000, '2024-12-17', 'Đổ xăng xe máy'],
        ['income', 'Lương', 18500000, '2024-12-01', 'Lương tháng 12/2024'],
        ['expense', 'Mua sắm', 850000, '2024-12-16', 'Mua quần áo'],
        ['expense', 'Giải trí', 300000, '2024-12-15', 'Xem phim CGV'],
        ['expense', 'Hóa đơn', 1200000, '2024-12-14', 'Tiền điện nước tháng 12'],
        ['expense', 'Ăn uống', 180000, '2024-12-13', 'Ăn tối với bạn bè'],
        ['expense', 'Di chuyển', 150000, '2024-12-12', 'Taxi về nhà'],
        ['expense', 'Mua sắm', 320000, '2024-12-11', 'Mua đồ gia dụng'],
        ['expense', 'Ăn uống', 95000, '2024-12-10', 'Cafe sáng']
    ];
    
    $sql = "INSERT INTO giao_dich (loai, danh_muc, so_tien, ngay, ghi_chu) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($sampleData as $data) {
        $stmt->execute($data);
    }
}

// Hàm tiện ích để format số tiền
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

// Hàm tiện ích để validate date
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
