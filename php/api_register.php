<?php
// API đăng ký cho Save Money App
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
    
    $fullname = trim($input['fullname'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    // Validate dữ liệu đầu vào
    if (!$fullname || !$email || !$password) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng điền đầy đủ thông tin'
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
    
    // Validate mật khẩu
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu phải có ít nhất 6 ký tự'
        ]);
        exit();
    }
    
    // Validate password strength
    if (!preg_match('/[a-zA-Z]/', $password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu phải có ít nhất 1 chữ cái'
        ]);
        exit();
    }
    
    if (!preg_match('/\d/', $password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu phải có ít nhất 1 số'
        ]);
        exit();
    }
    
    // Validate xác nhận mật khẩu
    if ($confirmPassword && $password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu xác nhận không khớp'
        ]);
        exit();
    }
    
    // Validate độ dài họ tên
    if (strlen($fullname) < 2 || strlen($fullname) > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Họ tên phải có từ 2-100 ký tự'
        ]);
        exit();
    }
    
    // Kiểm tra email đã tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email này đã được sử dụng'
        ]);
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm user mới
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $result = $stmt->execute([$fullname, $email, $hashedPassword]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.',
            'data' => [
                'user' => [
                    'id' => $userId,
                    'fullname' => $fullname,
                    'email' => $email
                ]
            ]
        ]);
    } else {
        throw new Exception('Không thể tạo tài khoản');
    }
    
} catch (PDOException $e) {
    // Log error để debug
    error_log("Register error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
