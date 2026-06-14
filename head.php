<?php
// head.php
// File này CHỈ chứa nội dung bên trong thẻ <head> và logic PHP liên quan
// Đảm bảo session đã được bắt đầu ở đầu file chính (vd: index.php, bai-viet.php)
// và $conn đã được thiết lập (từ connect.php) trước khi file này được include.

// Khởi tạo các biến mặc định
$_is_logged_in = false;
$_username = '';
$_user_avatar = '/images/avatar/default_avatar.png'; // Đường dẫn avatar mặc định
$_coin = 0; // Số dư mặc định
$_danh_hieu_logged_in = "";
$_color_logged_in = "";
$_admin_logged_in = 0;
$_name_str_logged_in = ''; // Biến để chứa chuỗi HTML tên và danh hiệu

$logged_in_player_name = null; // Tên nhân vật (dùng cho bình luận, admin logic)
$logged_in_player_gender = 0; // Giới tính của nhân vật
$is_admin = false; // Trạng thái admin của tài khoản

if (isset($_SESSION['username']) && isset($conn)) { // Đảm bảo $conn đã có
    $_username = $_SESSION['username']; // Đây là username tài khoản (vd: "admin", "user123")

    // Truy vấn để lấy tên nhân vật (player.name), giới tính (player.gender) và trạng thái admin (account.admin)
    // Giả định mỗi tài khoản có một nhân vật chính hoặc bạn muốn lấy bất kỳ nhân vật nào liên kết.
    // Nếu posts.username là tên nhân vật, bạn cần điều chỉnh truy vấn JOIN trong post_detail_logic.php
    // Current query assumes posts.username is account.username
    $sql_user_data = "
        SELECT
            p.name AS player_name,
            p.gender,
            p.head, -- Lấy cột head từ bảng player
            a.admin,
            a.tichdiem,
            a.coin
        FROM
            account a
        LEFT JOIN
            player p ON a.id = p.account_id
        WHERE
            a.username = ?
        LIMIT 1 -- Lấy 1 nhân vật nếu có nhiều
    ";
    $stmt_user_data = $conn->prepare($sql_user_data);

    if ($stmt_user_data) {
        $stmt_user_data->bind_param("s", $_username);
        $stmt_user_data->execute();
        $result_user_data = $stmt_user_data->get_result();
        $user_row = $result_user_data->fetch_assoc();

        if ($user_row) {
            $_is_logged_in = true;
            $logged_in_player_gender = $user_row['gender'] ?? 0; // Giới tính của nhân vật
            $_admin_logged_in = $user_row['admin'] ?? 0;
            $tichdiem_logged_in = $user_row['tichdiem'] ?? 0;
            $_coin = $user_row['coin'] ?? 0;
            $logged_in_player_name = $user_row['player_name'] ?? $_username; // Sử dụng tên nhân vật nếu có, không thì dùng username

            // Xác định avatar dựa trên head và admin
            $player_head = $user_row['head'] ?? 0; // Lấy giá trị head
            if ($_admin_logged_in == 1) {
                // Admin avatar (có thể tùy chỉnh theo head, hoặc dùng avatar cố định cho admin)
                // Nếu bạn muốn admin có avatar riêng không phụ thuộc head, giữ nguyên các dòng dưới.
                // Nếu admin avatar phụ thuộc head, bạn cần logic phức tạp hơn ở đây.
                if ($logged_in_player_gender == 1) {
                    $_user_avatar = "/images/avatar/avatar10.png"; // Admin nam
                } elseif ($logged_in_player_gender == 2) {
                    $_user_avatar = "/images/avatar/avatar11.png"; // Admin nữ
                } else {
                    $_user_avatar = "/images/avatar/avatar12.png"; // Admin khác
                }
            } else {
                // Avatar người dùng bình thường dựa trên head
                if ($player_head > 0) { // Đảm bảo head có giá trị hợp lệ
                    $_user_avatar = "/images/avatar/" . htmlspecialchars($player_head) . ".png"; // Hoặc .gif, tùy định dạng file avatar của bạn
                } else {
                    // Avatar mặc định nếu head không hợp lệ hoặc không có
                    if ($logged_in_player_gender == 1) {
                        $_user_avatar = "/images/avatar/avatar1.png"; // User nam mặc định
                    } elseif ($logged_in_player_gender == 2) {
                        $_user_avatar = "/images/avatar/avatar2.png"; // User nữ mặc định
                    } else {
                        $_user_avatar = "/images/avatar/avatar0.png"; // User khác mặc định
                    }
                }
            }

            // Lưu các biến quan trọng vào SESSION để sử dụng ở các file khác
            $_SESSION['player_name'] = $logged_in_player_name;
            $_SESSION['player_gender'] = $logged_in_player_gender;
            $_SESSION['is_admin'] = $_admin_logged_in;
            $_SESSION['user_avatar'] = $_user_avatar; // Lưu đường dẫn avatar đầy đủ vào session
            $_SESSION['coin'] = $_coin;
            // ... (các biến session khác bạn muốn lưu)

            // Xác định danh hiệu và màu sắc
            if ($tichdiem_logged_in >= 500) { $_danh_hieu_logged_in = "(Chuyên Gia)"; $_color_logged_in = "#800000"; }
            // ... (Thêm các cấp danh hiệu khác của bạn ở đây) ...

            // Tạo chuỗi HTML cho tên người dùng và danh hiệu
            if ($_admin_logged_in == 1) {
                $_name_str_logged_in = '<span class="text-danger font-weight-bold">' . htmlspecialchars($logged_in_player_name) . '</span><br>';
                $_name_str_logged_in .= '<span class="text-danger pt-1 mb-0">(Admin)</span>';
            } else {
                $_name_str_logged_in = '<p class="text-main font-weight-bold pt-1 mb-0">' . htmlspecialchars($logged_in_player_name) . '</p>';
                if ($_danh_hieu_logged_in !== "") {
                    $_name_str_logged_in .= '<div style="font-size: 9px; padding-top: 5px"><span style="color:' . $_color_logged_in . ' !important">' . $_danh_hieu_logged_in . '</span></div>';
                }
            }

            $is_admin = ($_admin_logged_in == 1); // Biến boolean cho logic
        }
        $stmt_user_data->close();
    } else {
        error_log("Lỗi chuẩn bị truy vấn SQL trong head.php: " . $conn->error);
    }
} else {
    // Nếu không đăng nhập hoặc $conn không có, các biến sẽ giữ giá trị mặc định.
    // Đảm bảo logged_in_player_name và is_admin được set để tránh lỗi undefined
    $logged_in_player_name = null;
    $is_admin = false;
}
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="Chú Bé Rồng Online,ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong" />
    <meta name="description" content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngọc Rồng Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!" />
    <meta http-equiv="refresh" content="600" />
    <meta name="robots" content="INDEX,FOLLOW" />

    <link rel="apple-touch-icon" href="/images/favicon-48x48.ico" />
    <link rel="icon" href='/images/favicon-48x48.ico' type="image/x-icon" />
    <link rel="shortcut icon" href='/images/favicon-48x48.ico' type="image/x-icon" />
    <link rel="icon" href="/images/favicon-48x48.ico">
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/images/favicon-64x64.png" sizes="64x64">
    <link rel="icon" type="image/png" href="/images/favicon-128x128.png" sizes="128x128">
    <link rel="icon" type="image/png" href="/images/favicon-48x48.png" sizes="48x48">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
    <link rel="stylesheet" href="/view/static/css/eff.css?v=1.00">
    <link rel="stylesheet" href="/view/static/css/w3.css?v=1.01">
    <link rel="stylesheet" href="/view/static/css/styleSheet.css?v=1.1">
    <script src="https://www.google.com/recaptcha/api.js?render="></script>