<?php
// top-nap.php
require_once 'connect.php';
?>
<!DOCTYPE html>
<html lang="vi"> <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bảng Xếp Hạng Top Nạp - Ngọc Rồng Online</title> <meta name="description"
        content="Xem bảng xếp hạng Top Nạp của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile hấp dẫn nhất hiện nay.">
    <meta name="keywords"
        content="top nạp, chú bé rồng online, ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong">
    <meta name="author" content="Mr Blue"> <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="Bảng Xếp Hạng Top Nạp - Chú Bé Rồng Online">
    <meta property="og:description" content="Xem bảng xếp hạng Top Nạp của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile hấp dẫn nhất hiện nay.">
    <meta property="og:image" content="/image/logo.png">
    <meta name="twitter:card" content="summary_large_image"> <meta name="twitter:url" content="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:title" content="Bảng Xếp Hạng Top Nạp - Chú Bé Rồng Online">
    <meta name="twitter:description" content="Xem bảng xếp hạng Top Nạp của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile hấp dẫn nhất hiện nay.">
    <meta name="twitter:image" content="/image/logo.png">
    <link rel="apple-touch-icon" href="/image/icon.png"> <link rel="icon" href="/image/icon.png?v=99" type="image/png">
    <link rel="shortcut icon" href="/image/icon.png?v=99" type="image/x-icon">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/main.css" rel="stylesheet">
	<script src="/view/static/js/disable_devtools.js"></script>
    <script src="assets/jquery/jquery.min.js"></script>
    <script src="assets/notify/notify.js"></script>

    <style>
        th,
        td {
            white-space: nowrap;
            padding: 8px 12px !important;
            font-size: 14px;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #343a40;
            font-weight: 600;
        }
        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, .03);
        }
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, .07);
        }
        .container.color-forum.pt-2 .col {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .table {
            max-width: 600px;
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container color-forum pt-1 pb-1">
        <div class="row">
            <div class="col">
                <a href="forum" style="color: white">Quay lại diễn đàn</a>
            </div>
        </div>
    </div>

    <div class="container color-forum pt-2">
        <div class="row">
            <div class="col">
                <h6 class="text-center">BẢNG XẾP HẠNG ĐUA TOP NẠP</h6>
                <table class="table table-striped table-bordered text-center"> <thead> <tr>
                            <th>STT</th>
                            <th>Nhân Vật</th>
                            <th>Tổng Nạp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        include 'connect.php'; 
                        if (!$conn) {
                            echo '<tr><td colspan="3" class="alert alert-danger">Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau!</td></tr>';
                        } else {
                            $query = "SELECT p.name, SUM(a.tongnap) AS tongnap 
                                      FROM account a 
                                      JOIN player p ON a.id = p.account_id 
                                      GROUP BY p.name 
                                      ORDER BY tongnap DESC 
                                      LIMIT 50";
                            
                            $result = $conn->query($query);
                            
                            if ($result === false) {
                                echo '<tr><td colspan="3" class="alert alert-danger">Lỗi truy vấn SQL: ' . htmlspecialchars($conn->error) . '</td></tr>';
                            } else if ($result->num_rows > 0) {
                                $stt = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo '
                                    <tr>
                                        <td>' . $stt . '</td>
                                        <td>' . htmlspecialchars($row['name']) . '</td>
                                        <td>' . number_format($row['tongnap'], 0, ',', '.') . 'đ</td>
                                    </tr>
                                    ';
                                    $stt++;
                                }
                            } else {
                                echo '<tr><td colspan="3" class="alert alert-info">Máy Chủ 1 chưa có thông kê bảng xếp hạng!</td></tr>';
                            }
                            $conn->close();
                        }
                        ?>
                    </tbody>
                </table>
                <div class="text-right w-100 mt-3"> <small>Cập nhật lúc:
                        <?php echo date('H:i d/m/Y'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/main.js"></script>
</body>
</html>