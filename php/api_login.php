<?php
// API đăng nhập cho Save Money App
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

require_once 'connect.php';

try {
    $pdo = getConnection();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu đầu vào không hợp lệ'
        ]);
        exit();
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email và mật khẩu không được để trống'
        ]);
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Định dạng email không hợp lệ'
        ]);
        exit();
    }
    
    // Tìm user trong database
    $stmt = $pdo->prepare("SELECT id, fullname, email, password, status FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Đăng nhập thành công
        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'fullname' => $user['fullname'],
                    'email' => $user['email']
                ]
            ]
        ]);
    } else {
        // Thông tin đăng nhập không đúng
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Email hoặc mật khẩu không đúng'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
