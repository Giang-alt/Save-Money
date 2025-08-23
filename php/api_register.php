<?php
header("Content-Type: application/json");
require "connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$fullname = $data['fullname'] ?? '';
$email    = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$fullname || !$email || !$password) {
    echo json_encode(["error" => "Thiếu dữ liệu"]);
    exit;
}
file_put_contents("debug.txt", print_r($data, true));
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Kiểm tra email tồn tại
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["error" => "Email đã tồn tại"]);
    exit;
}

// Thêm user mới
$stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $fullname, $email, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode(["message" => "Đăng ký thành công"]);
} else {
    echo json_encode(["error" => "Lỗi khi đăng ký"]);
}
?>
