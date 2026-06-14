
<?php
include_once 'set.php';
include_once 'connect.php';

if ($_login == null) {
    header("location:dang-nhap");
}
include('head.php');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Trang Chủ Chính Thức - Ngọc Rồng Private</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <base href="">
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
    <link href="assets/main.css" rel="stylesheet">
</head>

<body>
    <div class="container color-forum pt-1 pb-1">
        <div class="row">
            <div class="col"> <a href="dien-dan" style="color: white">Quay lại diễn đàn</a> </div>
        </div>
    </div>
<?php
$query = "SELECT player.name FROM player LEFT JOIN account ON player.account_id = account.id";
$result = mysqli_query($conn, $query);

$count = 0; // Biến đếm số lần lặp

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $count++; // Tăng biến đếm
        
        if ($count === 1) { // Chỉ hiển thị nội dung trong lần đầu tiên
            ?>
            <div class="container pt-5 pb-5">
                <div class="row">
                    <div class="col-lg-6 offset-lg-3">
                        <h4>Cách 1: Nạp qua tin nhắn</h4>
                        <div>
                            <p>Thông tin mở thành viên:</p>
                            <p><strong>- Tên Tài Khoản:</strong> <?php echo $_taikhoanmm; ?></p>
                            <p><strong>- Ngân Hàng:</strong> <?php echo $_momo; ?></p>
                            <p><strong>- Số Tài Khoản:</strong> <?php echo $_phonemomo; ?></p>
                            <p><strong>- Nội Dung:</strong> <?php echo $_username ?></p>
                            <br>
                            <p>- Xây dựng, ủng hộ ngocrongprivate.com hoạt động.</p>
                        </div>
                        <br>
                        <a class="btn btn-main form-control" style="border-radius:10px" href="momo">Xác nhận đã chuyển khoản</a>
                        <br>
                        <p><i>Khi chuyển tiền xong nhấn xác nhận đã chuyển khoản để xác thực giao dịch nhé!</i></p>
                        <p><i>Khi xác thực xong làm mới trang sau 1 - 3 phút để cập nhật Vnd.</i></p>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    mysqli_free_result($result);
} else {
    echo "Lỗi truy vấn: " . mysqli_error($conn);
}
?>
<div class=" border-secondary border-top">
    </div>
    <div class="container pt-4 pb-4 text-white">
        <div class="row">
            <div class="col">
                <div class="text-center">
                    <div style="font-size: 13px" class="text-dark">
                        <small>IP:
                            <?php echo $_IP; ?>
                        </small><br>
                        <small>Desgin By Mr Blue</small><br>
                        <small>2023© Ngọc Rồng Private</small>
                    </div>
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