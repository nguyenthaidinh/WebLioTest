<?php
require_once 'connect.php';
require_once 'set.php';
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Trang Chủ Chính Thức - Ngọc Rồng Online</title>
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
    <script src="assets/jquery/jquery.min.js"></>
            <script src="assets/notify/notify.js"></script>
    <link rel="icon" href="image/icon.png?v=99">
    <link href="assets/main.css" rel="stylesheet">
</head>

<body>
    <div class="container" style="border-radius: 15px; background: #ffaf4c; padding: 0px">
        <div class="container" style="background-color: #e67e22; border-radius: 15px 15px 0px 0px">
            <div class="row bg pb-3 pt-2">
                <div class="col">
                    <div class="text-center mb-2"> <a href="dien-dan"><img class="rounded" src="image/logo.png"
                                id="logo"></a> </div>
                    <div class="text-center pt-2">
                        <div style="display: inline-block;"> <a href="tai-game/android"> <img class="icon-download"
                                    src="image/android.png"></a> <br>
                            <small class="text-dark">0.0.1</small>
                        </div>
                        <div style="display: inline-block;"> <a href="tai-game/windows"><img class="icon-download"
                                    src="image/pc.png"></a> <br> <small class="text-dark">0.0.1</small> </div>
                        <div style="display: inline-block;"> <a href="tai-game/iphone"><img class="icon-download"
                                    src="image/ip.png"></a> <br> <small class="text-dark">0.0.1</small> </div>
                        <div> <img height="12" src="image/12.png" style="vertical-align: middle;"> <small
                                style="font-size: 10px" id="hour3">Dành cho
                                người chơi trên 12 tuổi. Chơi quá 180 phút mỗi ngày sẽ hại sức khỏe.</small> </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container color-main2 pb-2">
            <div class="text-center">
                <div class="row">
                    <div class="col pr-0"> <a href="dang-nhap" class="btn p-1 btn-header">Đăng
                            nhập</a> </div>
                    <div class="col"> <a href="dang-ky" class="btn p-1 btn-header-active">Đăng
                            ký</a> </div>
                </div>
            </div>
        </div>
        <div class="container color-forum pt-1 pb-1">
            <div class="row">
                <div class="col"> <a href="dien-dan" style="color: white">Quay lại diễn đàn</a> </div>
            </div>
        </div>
        <div class="container pt-5 pb-5">
            <div class="row">
                <div class="col-lg-6 offset-lg-3">
                    <h4>QUÊN MẬT KHẨU</h4>
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $username = mysqli_real_escape_string($conn, $_POST['username']);
                        $mkc2 = mysqli_real_escape_string($conn, $_POST['mkc2']);

                        // Check if the username and mkc2 combination exists in the account table
                        $query = "SELECT * FROM account WHERE username='$username' AND mkc2='$mkc2'";
                        $result = mysqli_query($conn, $query);
                        $row = mysqli_fetch_assoc($result);

                        if ($row) {
                            // Display new password update form
                            ?>
                            <form id="update-form" method="POST">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                                <div class="form-group">
                                    <label for="newpassword">Mật khẩu mới:</label>
                                    <input class="form-control" type="password" name="newpassword" id="newpassword"
                                        placeholder="Nhập mật khẩu mới" required>
                                </div>
                                <button class="btn btn-main form-control" type="submit" name="submit">CẬP NHẬT MẬT KHẨU
                                    MỚI</button>
                            </form>
                            <?php
                        } else {
                            echo "<div class='text-danger pb-2 font-weight-bold'>Tài khoản hoặc mật khẩu cấp 2 không hợp lệ.</div>";
                        }

                        if (isset($_POST['newpassword'])) {
                            $newpassword = password_hash($_POST['newpassword'], PASSWORD_DEFAULT); // Hash new password before storing
                    
                            // Update new password in the database
                            $updateQuery = "UPDATE account SET password='$newpassword' WHERE username='$username'";
                            mysqli_query($conn, $updateQuery);

                            echo "<div class='text-success pb-2 font-weight-bold'>Cập nhật mật khẩu thành công!</div>";
                        }
                    }
                    ?>
                    <form id="form" method="POST">
                        <div class="form-group">
                            <label for="username">Tài khoản:</label>
                            <input class="form-control" type="text" name="username" id="username"
                                placeholder="Nhập tài khoản" required>
                        </div>
                        <div class="form-group">
                            <label for="mkc2">Mật khẩu cấp 2:</label>
                            <input class="form-control" type="password" name="mkc2" id="mkc2"
                                placeholder="Nhập mật khẩu cấp 2" required>
                        </div>

                        <?php
                        if (!empty($_alert)) {
                            echo $_alert;
                        }
                        ?>

                        <div id="notify" class="text-danger pb-2 font-weight-bold"></div>
                        <button class="btn btn-main form-control" type="submit" name="submit">XÁC NHẬN</button>
                    </form>
                    <br>
                </div>
            </div>
        </div>
        <div class="border-secondary border-top"></div>
        <div class="container pt-4 pb-4 text-white">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        <div style="font-size: 13px" class="text-dark">
                                    <small>IP:
                                        <?php echo $_IP; ?>
                                    </small><br>
                                    <small>Desgin By Mr Blue</small><br>
                                    <small>2023© Ngọc Rồng Online</small>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="asset/main.js"></script>
</body>

</html>