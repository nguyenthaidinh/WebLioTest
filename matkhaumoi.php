<?php
include('set.php');
include('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $matkhau = $_POST['matkhau'];
    $xacnhan_matkhau = $_POST['xacnhan_matkhau'];

    if (empty($matkhau)) {
        echo '<div class="text-danger pb-2 font-weight-bold">Vui lòng nhập mật khẩu mới.</div>';
        exit;
    }

    if ($matkhau !== $xacnhan_matkhau) {
        echo '<div class="text-danger pb-2 font-weight-bold">Xác nhận mật khẩu mới không đúng.</div>';
        exit;
    }

    // Escape các giá trị để tránh SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $matkhau = mysqli_real_escape_string($conn, $matkhau);

    // Cập nhật mật khẩu mới vào cơ sở dữ liệu
    $sql = "UPDATE account SET password = '$matkhau' WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['alert'] = '<div class="text-danger pb-2 font-weight-bold">Đổi mật khẩu thành công.</div>';
    } else {
        $_SESSION['alert'] = '<div class="text-danger pb-2 font-weight-bold">Đổi mật khẩu thất bại.</div>';
    }

    mysqli_close($conn);
}
?>