<?php
$host = "localhost";
$user = "root"; // Mặc định XAMPP
$pass = "";
$db   = "save_money";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
