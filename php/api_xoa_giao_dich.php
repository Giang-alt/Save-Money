<?php
// API xóa giao dịch
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Chỉ cho phép DELETE request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ hỗ trợ phương thức DELETE'
    ]);
    exit();
}

require_once 'connect.php';

try {
    // Lấy ID từ query parameter
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id || $id <= 0) {
        throw new Exception('ID giao dịch không hợp lệ');
    }
    
    // Get database connection
    $pdo = getConnection();
    
    // Set charset to UTF-8
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Kiểm tra giao dịch có tồn tại không
    $checkStmt = $pdo->prepare("SELECT id, danh_muc, so_tien, ngay FROM giao_dich WHERE id = ?");
    $checkStmt->execute([$id]);
    $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Giao dịch không tồn tại');
    }
    
    // Xóa giao dịch
    $deleteStmt = $pdo->prepare("DELETE FROM giao_dich WHERE id = ?");
    $result = $deleteStmt->execute([$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa giao dịch thành công',
            'data' => [
                'deleted_transaction' => $transaction
            ]
        ]);
    } else {
        throw new Exception('Không thể xóa giao dịch');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
