<?php
header("Content-Type: application/json");
require "connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$email    = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["error" => "Thiếu dữ liệu"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            "message" => "Đăng nhập thành công",
            "user" => [
                "id" => $row['id'],
                "fullname" => $row['fullname'],
                "email" => $email
            ]
        ]);
    } else {
        echo json_encode(["error" => "Sai mật khẩu"]);
    }
} else {
    echo json_encode(["error" => "Email không tồn tại"]);
}
?>
