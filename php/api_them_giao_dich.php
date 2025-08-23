<?php
// API thêm giao dịch vào cơ sở dữ liệu savemoney_db
// Cho phép CORS để frontend có thể gọi API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ hỗ trợ phương thức POST'
    ]);
    exit();
}

try {
    // Sử dụng file config để kết nối database
    require_once 'config.php';
    $pdo = initializeDatabase();
    
    // Lấy dữ liệu từ request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Kiểm tra dữ liệu đầu vào
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu đầu vào không hợp lệ'
        ]);
        exit();
    }
    
    // Validate các trường bắt buộc
    $required_fields = ['type', 'category', 'amount', 'date', 'note'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Trường '$field' là bắt buộc"
            ]);
            exit();
        }
    }
    
    // Lấy các trường
    $type = strtolower(trim($input['type']));
    $category = trim($input['category']);
    $amount = floatval($input['amount']);
    $date = trim($input['date']);
    $note = trim($input['note']);
    
    // Validate loại giao dịch
    if (!in_array($type, ['income', 'expense'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Loại giao dịch phải là "income" hoặc "expense"'
        ]);
        exit();
    }
    
    // Validate số tiền
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Số tiền phải lớn hơn 0'
        ]);
        exit();
    }
    
    // Validate ngày
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Định dạng ngày không hợp lệ (YYYY-MM-DD)'
        ]);
        exit();
    }
    
    // Tìm ID tiếp theo thay vì dùng auto-increment
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM giao_dich");
    $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
    $nextId = ($maxId ? $maxId + 1 : 1);
    
    // Chuẩn bị câu lệnh SQL để thêm giao dịch với ID cụ thể
    $sql = "INSERT INTO giao_dich (id, loai, danh_muc, so_tien, ngay, ghi_chu, ngay_tao, ngay_cap_nhat) VALUES (:id, :loai, :danh_muc, :so_tien, :ngay, :ghi_chu, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $nextId, PDO::PARAM_INT);
    $stmt->bindParam(':loai', $type, PDO::PARAM_STR);
    $stmt->bindParam(':danh_muc', $category, PDO::PARAM_STR);
    $stmt->bindParam(':so_tien', $amount, PDO::PARAM_STR);
    $stmt->bindParam(':ngay', $date, PDO::PARAM_STR);
    $stmt->bindParam(':ghi_chu', $note, PDO::PARAM_STR);
    
    // Thực thi câu lệnh
    if ($stmt->execute()) {
        // Trả về response thành công
        echo json_encode([
            'success' => true,
            'message' => 'Thêm giao dịch thành công',
            'data' => [
                'id' => $nextId,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'date' => $date,
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Không thể thêm giao dịch');
    }
    
} catch (PDOException $e) {
    // Xử lý lỗi cơ sở dữ liệu
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Xử lý các lỗi khác
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
