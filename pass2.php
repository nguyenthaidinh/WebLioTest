<?php
include_once 'set.php';
include_once 'connect.php';
include_once 'head.php';
if ($_login == null) {
    header("location:dang-nhap");
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Trang Chủ Chính Thức - Ngọc Rồng Light</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="description"
        content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!">
    <meta name="keywords"
        content="Chú Bé Rồng Online,ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title"
        content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!">
    <meta name="twitter:description"
        content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!">
    <meta name="twitter:image" content="image/logo.png">
    <meta name="twitter:image:width" content="200">
    <meta name="twitter:image:height" content="200">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="assets/jquery/jquery.min.js"></script>
    <script src="assets/notify/notify.js"></script>
    <link rel="icon" href="image/icon.png?v=99">
    <link href="assets//main.css" rel="stylesheet">
</head>

<body>
        <div class="container color-forum pt-1 pb-1">
            <div class="row">
                <div class="col"> <a href="dien-dan" style="color: white">Quay lại diễn đàn</a> </div>
            </div>
        </div>
        <div class="container pt-5 pb-5">
            <div class="row">
                <div class="col-lg-6 offset-lg-3">
                    <h4>BẢO MẬT CẤP 2 - BẢO VỆ TÀI KHOẢN</h4>
                    <?php
                    $stmt = $conn->prepare("SELECT password, mkc2 FROM account WHERE username=?");
                    $stmt->bind_param("s", $_username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $primaryPassword = $row['password'];
                    $mkc2 = $row['mkc2'];

                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $password = $_POST['password'] ?? '';
                        $new_passwordcap2 = $_POST['new_passwordcap2'] ?? '';
                        $new_passwordcap2xacnhan = $_POST['new_passwordcap2xacnhan'] ?? '';

                        if (!empty($mkc2)) {
                            $old_passwordcap2 = isset($_POST['old_passwordcap2']) ? $_POST['old_passwordcap2'] : '';

                            if (!empty($password) && !empty($new_passwordcap2) && !empty($new_passwordcap2xacnhan) && !empty($old_passwordcap2)) {
                                // Kiểm tra xem mật khẩu hiện tại nhập vào có giống với mật khẩu trong database không.
                                // Nếu sai, in ra thông báo lỗi.
                                if ($password !== $primaryPassword) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Sai mật khẩu hiện tại</div>";
                                } elseif ($old_passwordcap2 !== $mkc2) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Sai mật khẩu cấp 2 hiện tại</div>";
                                } elseif ($new_passwordcap2 === $password) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Mật khẩu cấp 2 không được giống mật khẩu hiện tại</div>";
                                } elseif ($new_passwordcap2 !== $new_passwordcap2xacnhan) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Mật khẩu cấp 2 không giống nhau</div>";
                                } elseif ($password === $new_passwordcap2) { // Kiểm tra mật khẩu hiện tại có trùng với mật khẩu mới hay không.
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Mật khẩu cấp 2 phải khác với mật khẩu hiện tại</div>";
                                } else {
                                    // Cập nhật mật khẩu cấp 2 lên database
                                    $stmt = $conn->prepare("UPDATE account SET mkc2=? WHERE username=?");
                                    $stmt->bind_param("ss", $new_passwordcap2, $_username);

                                    if ($stmt->execute()) {
                                        echo "<div class='text-danger pb-2 font-weight-bold'>Cập nhật mật khẩu cấp 2 thành công</div>";
                                    } else {
                                        echo "<div class='text-danger pb-2 font-weight-bold'>Lỗi khi cập nhật mật khẩu cấp 2</div>";
                                    }
                                }
                            } else {
                                echo "<div class='text-danger pb-2 font-weight-bold'>Vui lòng điền đầy đủ thông tin trong form</div>";
                            }
                        } else {
                            if (!empty($password) && !empty($new_passwordcap2) && !empty($new_passwordcap2xacnhan)) {
                                if ($password !== $primaryPassword) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Sai mật khẩu hiện tại</div>";
                                } elseif ($new_passwordcap2 === $password) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Mật khẩu cấp 2 không được giống mật khẩu hiện tại</div>";
                                } elseif ($new_passwordcap2 !== $new_passwordcap2xacnhan) {
                                    echo "<div class='text-danger pb-2 font-weight-bold'>Mật khẩu cấp 2 không giống nhau</div>";
                                } else {
                                    $stmt = $conn->prepare("UPDATE account SET mkc2=? WHERE username=?");
                                    $stmt->bind_param("ss", $new_passwordcap2, $_username);

                                    if ($stmt->execute()) {
                                        echo "<div class='text-danger pb-2 font-weight-bold'>Cập nhật mật khẩu cấp 2 thành công</div>";
                                    } else {
                                        echo "<div class='text-danger pb-2 font-weight-bold'>Lỗi khi cập nhật mật khẩu cấp 2</div>";
                                    }
                                }
                            } else {
                                echo "<div class='text-danger pb-2 font-weight-bold'>Vui lòng điền đầy đủ thông tin trong form</div>";
                            }
                        }
                    }

                    if (!empty($mkc2)) {
                        ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="font-weight-bold">Mật Khẩu hiện tại:</label>
                                <input type="password" class="form-control" name="password" id="password"
                                    placeholder="Mật khẩu hiện tại" required autocomplete="password">
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">Mật Khẩu Cấp 2 Hiện Tại:</label>
                                <input type="password" class="form-control" name="old_passwordcap2" id="old_passwordcap2"
                                    placeholder="Mật khẩu cấp 2 hiện tại" required autocomplete="old-passwordcap2">
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">Mật Khẩu Cấp 2 Mới:</label>
                                <input type="password" class="form-control" name="new_passwordcap2" id="new_passwordcap2"
                                    placeholder="Mật khẩu cấp 2 mới" required autocomplete="new-passwordcap2">
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">Xác Nhận Mật Khẩu Cấp 2:</label>
                                <input type="password" class="form-control" name="new_passwordcap2xacnhan"
                                    id="new_passwordcap2xacnhan" placeholder="Xác nhận mật khẩu cấp 2 mới" required
                                    autocomplete="new-passwordcap2xacnhan">
                            </div>
                            <button class="btn btn-main form-control" type="submit">Thực hiện</button>
                        </form>
                    <?php } else { ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="font-weight-bold">Mật Khẩu Hiện Tại:</label>
                                <input type="password" class="form-control" name="password" id="password"
                                    placeholder="Mật khẩu hiện tại" required autocomplete="password">
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">Mật Khẩu Cấp 2:</label>
                                <input type="password" class="form-control" name="new_passwordcap2" id="new_passwordcap2"
                                    placeholder="Mật khẩu cấp 2" required autocomplete="new-passwordcap2">
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">Nhập Lại Mật Khẩu Cấp 2:</label>
                                <input type="password" class="form-control" name="new_passwordcap2xacnhan"
                                    id="new_passwordcap2xacnhan" placeholder="Xác nhận mật khẩu cấp 2" required
                                    autocomplete="new-passwordcap2xacnhan">
                            </div>
                            <button class="btn btn-main form-control" type="submit">Thực hiện</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="border-secondary border-top"></div>
        <div class="container pt-4 pb-4 text-white">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        <div style="font-size: 13px" class="text-dark"> <small>Desgin By Nguyễn Đức Kiên</small><br>
                            <small>2023©NGỌC RỒNG LIGHT</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/main.js"></script>
</body><!-- Bootstrap core JavaScript -->

</html>