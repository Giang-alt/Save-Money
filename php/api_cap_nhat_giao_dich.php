<?php
// API cập nhật giao dịch
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Chỉ cho phép PUT request
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ hỗ trợ phương thức PUT'
    ]);
    exit();
}

require_once 'connect.php';

try {
    // Lấy dữ liệu từ request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dữ liệu không hợp lệ: ' . json_last_error_msg());
    }
    
    // Validate required fields
    $required_fields = ['id', 'loai', 'danh_muc', 'so_tien', 'ngay'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Trường {$field} là bắt buộc");
        }
        // Special handling for so_tien - allow 0 but not empty string
        if ($field === 'so_tien') {
            if ($input[$field] === '' || $input[$field] === null) {
                throw new Exception("Trường {$field} là bắt buộc");
            }
        } else {
            if (empty($input[$field])) {
                throw new Exception("Trường {$field} là bắt buộc");
            }
        }
    }
    
    $id = intval($input['id']);
    $loai = $input['loai'];
    $danh_muc = $input['danh_muc'];
    $so_tien = floatval($input['so_tien']);
    $ngay = $input['ngay'];
    $ghi_chu = $input['ghi_chu'] ?? '';
    $user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
    
    // Validate loại giao dịch
    if (!in_array($loai, ['income', 'expense'])) {
        throw new Exception('Loại giao dịch không hợp lệ');
    }
    
    // Validate số tiền
    if ($so_tien <= 0) {
        throw new Exception('Số tiền phải lớn hơn 0');
    }
    
    // Validate ngày
    if (!isValidDate($ngay)) {
        throw new Exception('Định dạng ngày không hợp lệ');
    }
    
    // Get database connection
    $pdo = getConnection();
    
    // Set charset to UTF-8
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Kiểm tra giao dịch có tồn tại không
    $checkStmt = $pdo->prepare("SELECT id FROM giao_dich WHERE id = ?");
    $checkStmt->execute([$id]);
    
    if ($checkStmt->rowCount() == 0) {
        throw new Exception('Giao dịch không tồn tại');
    }
    
    // Cập nhật giao dịch
    $sql = "UPDATE giao_dich SET loai = ?, danh_muc = ?, so_tien = ?, ngay = ?, ghi_chu = ?, user_id = ?, ngay_cap_nhat = CURRENT_TIMESTAMP WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        $loai,
        $danh_muc,
        $so_tien,
        $ngay,
        $ghi_chu,
        $user_id,
        $id
    ]);
    
    if ($result) {
        // Lấy thông tin giao dịch đã cập nhật
        $getUpdatedStmt = $pdo->prepare("SELECT * FROM giao_dich WHERE id = ?");
        $getUpdatedStmt->execute([$id]);
        $updatedTransaction = $getUpdatedStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật giao dịch thành công',
            'data' => $updatedTransaction
        ]);
    } else {
        throw new Exception('Không thể cập nhật giao dịch');
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
