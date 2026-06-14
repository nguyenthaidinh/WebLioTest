<?php
// bang-xep-hang.php
include_once 'set.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Trang Chủ Chính Thức - Ngọc Rồng Online</title>
    <meta name="description" content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!">
    <meta name="keywords" content="Chú Bé Rồng Online,ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong">
    <meta name="author" content="">
    <base href="/">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!">
    <meta name="twitter:description" content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!">
    <meta name="twitter:image" content="/image/logo.png">
    <meta name="twitter:image:width" content="200">
    <meta name="twitter:image:height" content="200">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="assets/jquery/jquery.min.js"></script>
    <script src="assets/notify/notify.js"></script>
    <link rel="icon" href="/image/icon.png?v=99">
    <link href="assets/main.css" rel="stylesheet">
	<script src="/view/static/js/disable_devtools.js"></script>
    <style>
        th,
        td {
            white-space: nowrap;
            padding: 2px 4px !important;
            font-size: 11px;
            border: 1px solid #dee2e6;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, .05);
        }
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6 !important;
        }
    </style>
</head>

<body>
    <div class="container color-forum pt-1 pb-1">
        <div class="row">
            <div class="col"> <a href="forum" style="color: white">Quay lại diễn đàn</a> </div>
        </div>
    </div>
    <div class="container color-forum pt-2">
        <div class="row">
            <div class="col">
                <h6 class="text-center">BẢNG XẾP HẠNG ĐUA TOP</h6>
                <table class="table table-bordered table-striped text-center" id="leaderboard-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Nhân vật</th>
                            <th>Sức Mạnh</th>
                            <th>Đệ Tử</th>
                            <th>Hành Tinh</th>
                            <th>Tổng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $countTop = 1;
                        $query = "
                            SELECT
                                name,
                                gender,
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(data_point, '$[1]')) AS SIGNED) AS player_sm,
                                COALESCE(
                                    CAST(
                                        SUBSTRING_INDEX(
                                            SUBSTRING_INDEX(
                                                JSON_UNQUOTE(JSON_EXTRACT(pet, '$[1]')),
                                                ',', 2
                                            ),
                                            ',', -1
                                        ) AS SIGNED
                                    ), 0
                                ) AS detu_sm,
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(data_point, '$[1]')) AS SIGNED) +
                                COALESCE(
                                    CAST(
                                        SUBSTRING_INDEX(
                                            SUBSTRING_INDEX(
                                                JSON_UNQUOTE(JSON_EXTRACT(pet, '$[1]')),
                                                ',', 2
                                            ),
                                            ',', -1
                                        ) AS SIGNED
                                    ), 0
                                ) AS tongdiem
                            FROM player
                            ORDER BY tongdiem DESC
                            LIMIT 100;
                        ";

                        $data = mysqli_query($conn, $query);

                        if ($data) {
                            if (mysqli_num_rows($data) > 0) {
                                while ($row = mysqli_fetch_array($data)) {
                        ?>
                                        <tr class="top_<?php echo $countTop; ?>">
                                            <td>
                                                <?php echo $countTop++; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $value = $row['player_sm'];
                                                if ($value != '') {
                                                    if ($value > 1000000000) {
                                                        echo number_format($value / 1000000000, 1, '.', '') . ' tỷ';
                                                    } elseif ($value > 1000000) {
                                                        echo number_format($value / 1000000, 1, '.', '') . ' Triệu';
                                                    } elseif ($value >= 1000) {
                                                        echo number_format($value / 1000, 1, '.', '') . ' k';
                                                    } else {
                                                        echo number_format($value, 0, ',', '');
                                                    }
                                                } else {
                                                    echo 'Không có chỉ số sức mạnh';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $value = $row['detu_sm'];
                                                if ($value != '' && $value > 0) {
                                                    if ($value > 1000000000) {
                                                        echo number_format($value / 1000000000, 1, '.', '') . ' tỷ';
                                                    } elseif ($value > 1000000) {
                                                        echo number_format($value / 1000000, 1, '.', '') . ' Triệu';
                                                    } elseif ($value >= 1000) {
                                                        echo number_format($value / 1000, 1, '.', '') . ' k';
                                                    } else {
                                                        echo number_format($value, 0, ',', '');
                                                    }
                                                } else {
                                                    echo 'Không đệ tử';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($row['gender'] == 0) {
                                                    echo "Trái đất";
                                                } elseif ($row['gender'] == 1) {
                                                    echo "Namec";
                                                } elseif ($row['gender'] == 2) {
                                                    echo "Xayda";
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $total = $row['tongdiem'];
                                                if ($total > 1000000000) {
                                                    echo number_format($total / 1000000000, 1, '.', '') . ' tỷ';
                                                } elseif ($total > 1000000) {
                                                    echo number_format($total / 1000000, 1, '.', '') . ' Triệu';
                                                } elseif ($total >= 1000) {
                                                    echo number_format($total / 1000, 1, '.', '') . ' k';
                                                } else {
                                                    echo number_format($total, 0, ',', '');
                                                }
                                                ?>
                                            </td>
                                        </tr>
                        <?php
                                }
                            } else {
                                echo '<tr><td colspan="6">Máy Chủ 1 chưa có thông kê bảng xếp hạng!</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6">Lỗi khi lấy dữ liệu từ cơ sở dữ liệu: ' . mysqli_error($conn) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <script>
                    setInterval(function() {
                        $.ajax({
                            url: location.href,
                            success: function(result) {
                                var leaderboardTableBody = $(result).find('#leaderboard-table tbody');
                                $('#leaderboard-table tbody').html(leaderboardTableBody.html());
                            }
                        });
                    }, 3000);
                </script>
                <div class="text-right">
                    <small>Cập nhật lúc:
                        <?php echo date('H:i d/m/Y'); ?>
                    </small>
                </div>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/main.js"></script>
</body>

</html>