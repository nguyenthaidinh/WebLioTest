<?php
// connect.php
$ip_sv = "180.93.54.5";
$dbname_sv = "team2026";
$user_sv = "liodev";
$pass_sv = "liopass";

$conn = new mysqli($ip_sv, $user_sv, $pass_sv, $dbname_sv);

if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

date_default_timezone_set('Asia/Ho_Chi_Minh');
?>
