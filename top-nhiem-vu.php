<?php
require_once 'connect.php';
require_once 'task_data_config.php';

$players_task_data = [];
$error_message = '';

if (!isset($conn) || $conn->connect_error) {
    $error_message = 'Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau!';
} else {
    $query = "SELECT name, data_task FROM player ORDER BY id ASC LIMIT 200";
    $result = $conn->query($query);

    if ($result === false) {
        $error_message = 'Lỗi truy vấn SQL: ' . htmlspecialchars($conn->error);
    } else if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $task_data = json_decode($row['data_task'], true);
            $current_task_id_for_sort = 0;
            $task_display_text = "Không có dữ liệu nhiệm vụ";

            if (is_array($task_data) && json_last_error() === JSON_ERROR_NONE) {
                if (isset($task_data[0]) && is_numeric($task_data[0])) {
                    $current_task_id_for_sort = (int)$task_data[0];
                    if (isset($game_tasks[$current_task_id_for_sort])) {
                        $task_display_text = htmlspecialchars($game_tasks[$current_task_id_for_sort]['name']);
                    } else {
                        $task_display_text = "Nhiệm vụ ID: " . $current_task_id_for_sort . " (Chưa có tên trong cấu hình)";
                    }

                    if (isset($task_data[1]) && is_numeric($task_data[1]) && $task_data[1] > 0) {
                       $task_display_text .= " (Tiến độ: " . $task_data[1] . "%)";
                    }

                } else {
                    $task_id_found = false;
                    if (isset($task_data['current_task_id'])) {
                        $current_task_id_for_sort = (int)$task_data['current_task_id'];
                        $task_id_found = true;
                    } else if (isset($task_data['task_id'])) {
                        $current_task_id_for_sort = (int)$task_data['task_id'];
                        $task_id_found = true;
                    } else if (isset($task_data['currentTask']) && is_array($task_data['currentTask']) && isset($task_data['currentTask']['id'])) {
                        $current_task_id_for_sort = (int)$task_data['currentTask']['id'];
                        $task_id_found = true;
                    }
                    
                    if ($task_id_found && isset($game_tasks[$current_task_id_for_sort])) {
                        $task_info = $game_tasks[$current_task_id_for_sort];
                        $task_display_text = htmlspecialchars($task_info['name']);
                    } else if ($task_id_found) {
                        $task_display_text = "Nhiệm vụ ID: " . $current_task_id_for_sort . " (Chưa có tên trong cấu hình)";
                    }

                    if (isset($task_data['name']) && !empty($task_data['name'])) {
                        $task_display_text = htmlspecialchars($task_data['name']);
                    } else if (isset($task_data['description']) && !empty($task_data['description'])) {
                        $task_display_text = htmlspecialchars($task_data['description']);
                    } else if (isset($task_data['message']) && !empty($task_data['message'])) {
                        $task_display_text = htmlspecialchars($task_data['message']);
                    }

                    if (isset($task_data['progress']) && is_numeric($task_data['progress'])) {
                        $task_display_text .= " (Tiến độ: " . $task_data['progress'] . "%)";
                    }
                }
            } 
            
            $players_task_data[] = [
                'name' => htmlspecialchars($row['name']),
                'task_id_for_sort' => $current_task_id_for_sort,
                'task_display' => $task_display_text
            ];
        }

        usort($players_task_data, function($a, $b) {
            return $b['task_id_for_sort'] <=> $a['task_id_for_sort'];
        });

        $players_task_data = array_slice($players_task_data, 0, 50);

    } else {
        $error_message = 'Hiện tại chưa có người chơi nào để hiển thị bảng xếp hạng nhiệm vụ.';
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bảng Xếp Hạng Nhiệm Vụ - Ngọc Rồng Online</title>
    <meta name="description" content="Xem bảng xếp hạng nhiệm vụ của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile hấp dẫn nhất hiện nay.">
    <meta name="keywords" content="top nhiệm vụ, chú bé rồng online, ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong">
    <meta name="author" content="Mr Blue">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="Bảng Xếp Hạng Nhiệm Vụ - Chú Bé Rồng Online">
    <meta property="og:description" content="Xem bảng xếp hạng nhiệm vụ của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile hấp dẫn nhất hiện nay.">
    <meta property="og:image" content="/image/logo.png">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:title" content="Bảng Xếp Hạng Nhiệm Vụ - Chú Bé Rồng Online">
    <meta name="twitter:description" content="Xem bảng xếp hạng nhiệm vụ của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile hấp dẫn nhất hiện nay.">
    <meta name="twitter:image" content="/image/logo.png">

    <link rel="apple-touch-icon" href="/image/icon.png">
    <link rel="icon" href="/image/icon.png?v=99" type="image/png">
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
        .table-responsive {
            max-width: 700px;
            width: 100%;
            margin: 20px auto 0 auto;
        }
        .table {
            width: 100%;
            margin-top: 0;
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
                <h6 class="text-center">BẢNG XẾP HẠNG NHIỆM VỤ</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-center">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Nhân Vật</th>
                                <th>Nhiệm Vụ Hiện Tại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($error_message)): ?>
                                <tr><td colspan="3" class="alert alert-danger"><?php echo $error_message; ?></td></tr>
                            <?php elseif (empty($players_task_data)): ?>
                                <tr><td colspan="3" class="alert alert-info">Hiện tại chưa có dữ liệu nhiệm vụ hợp lệ để xếp hạng.</td></tr>
                            <?php else: ?>
                                <?php $stt = 1; ?>
                                <?php foreach ($players_task_data as $player): ?>
                                    <tr>
                                        <td><?php echo $stt++; ?></td>
                                        <td><?php echo $player['name']; ?></td>
                                        <td><?php echo $player['task_display']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-right w-100 mt-3">
                    <small>Cập nhật lúc:
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