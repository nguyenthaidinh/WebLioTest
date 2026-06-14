<?php
// forum_data.php - Xử lý dữ liệu diễn đàn và thông tin người dùng

// Bật hiển thị lỗi PHP để dễ gỡ lỗi (CHỈ TRONG MÔI TRƯỜNG PHÁT TRIỂN!)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/connect.php'; // Đảm bảo connect.php được include để có $conn

// Khởi tạo các biến mặc định
$is_logged_in = false;
$account_username = '';
$display_player_name = '';
$user_vnd = 0;
$user_avatar = '/images/avatar/default_avatar.png'; // Avatar mặc định
$player_gender_for_avatar = 0;
$is_admin_for_avatar = 0;
$player_head_for_avatar = 0;
$player_name = $display_player_name;
$user_balance = $user_vnd;

// Ghi log trạng thái session khi forum_data.php được tải
error_log("DEBUG: forum_data.php - Session status: " . session_status());
error_log("DEBUG: forum_data.php - SESSION username: " . ($_SESSION['username'] ?? 'Not set'));
error_log("DEBUG: forum_data.php - SESSION user_id: " . ($_SESSION['user_id'] ?? 'Not set'));


if (isset($_SESSION['username'])) {
    $is_logged_in = true;
    $account_username = $_SESSION['username'];

    // Lấy user_id từ session (rất quan trọng cho các truy vấn sau này)
    // Đảm bảo rằng user_id được đặt trong session khi đăng nhập
    if (!isset($_SESSION['user_id'])) {
        // Nếu user_id chưa có trong session, cố gắng lấy từ DB dựa trên username
        $stmt_get_user_id = $conn->prepare("SELECT id FROM account WHERE username = ? LIMIT 1");
        if ($stmt_get_user_id) {
            $stmt_get_user_id->bind_param("s", $account_username);
            $stmt_get_user_id->execute();
            $result_get_user_id = $stmt_get_user_id->get_result();
            if ($row_user_id = $result_get_user_id->fetch_assoc()) {
                $_SESSION['user_id'] = $row_user_id['id'];
                error_log("DEBUG: forum_data.php - user_id được lấy từ DB và đặt vào session: " . $_SESSION['user_id']);
            } else {
                error_log("DEBUG: forum_data.php - Không tìm thấy user_id trong DB cho username: " . $account_username);
                $is_logged_in = false; // Nếu không tìm thấy ID, coi như chưa đăng nhập
            }
            $stmt_get_user_id->close();
        } else {
            error_log("Lỗi prepare lấy user_id trong forum_data.php: " . $conn->error);
            $is_logged_in = false;
        }
    }


    $stmt_user_data = $conn->prepare("
        SELECT
            a.id, a.vnd, a.admin,
            p.name AS player_name, p.gender, p.head
        FROM
            account a
        LEFT JOIN
            player p ON a.id = p.account_id
        WHERE
            a.username = ?
        LIMIT 1
    ");

    if ($stmt_user_data) {
        $stmt_user_data->bind_param("s", $account_username);
        $stmt_user_data->execute();
        $result_user_data = $stmt_user_data->get_result();
        if ($user_row = $result_user_data->fetch_assoc()) {
            $user_vnd = $user_row['vnd'] ?? 0;
            $display_player_name = $user_row['player_name'] ?? $account_username;
            $player_gender_for_avatar = $user_row['gender'] ?? 0;
            $is_admin_for_avatar = $user_row['admin'] ?? 0;
            $player_head_for_avatar = $user_row['head'] ?? 0;

            // Đảm bảo user_id được đặt trong session từ đây nếu nó chưa có
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['user_id'] = $user_row['id'];
                error_log("DEBUG: forum_data.php - user_id được đặt vào session từ truy vấn chính: " . $_SESSION['user_id']);
            }

            error_log("DEBUG: forum_data.php - Dữ liệu người dùng: Admin=" . $is_admin_for_avatar . ", Gender=" . $player_gender_for_avatar . ", Head=" . $player_head_for_avatar);

            if ($is_admin_for_avatar == 1) {
                if ($player_gender_for_avatar == 0) {
                    $user_avatar = "/images/avatar/10.png";
                } elseif ($player_gender_for_avatar == 1) {
                    $user_avatar = "/images/avatar/11.png";
                } elseif ($player_gender_for_avatar == 2) {
                    $user_avatar = "/images/avatar/12.png";
                } else {
                    $user_avatar = "/images/avatar/12.png"; // Default for admin if gender is unexpected
                }
            } else {
                if ($player_head_for_avatar > 0) {
                    $user_avatar = "/images/avatar/" . htmlspecialchars($player_head_for_avatar) . ".png";
                } else {
                    if ($player_gender_for_avatar == 0) {
                        $user_avatar = "/images/avatar/0.png";
                    } elseif ($player_gender_for_avatar == 1) {
                        $user_avatar = "/images/avatar/1.png";
                    } elseif ($player_gender_for_avatar == 2) {
                        $user_avatar = "/images/avatar/2.png";
                    } else {
                        $user_avatar = "/images/avatar/default_avatar.png"; // Default for non-admin if head/gender is unexpected
                    }
                }
            }
            error_log("DEBUG: forum_data.php - Đường dẫn avatar cuối cùng: " . $user_avatar);
        } else {
            error_log("DEBUG: forum_data.php - Không tìm thấy dữ liệu người dùng cho username: " . $account_username);
            $is_logged_in = false; // Nếu không tìm thấy dữ liệu, coi như chưa đăng nhập
        }
        $stmt_user_data->close();
    } else {
        error_log("Lỗi prepare lấy thông tin người dùng trong forum_data.php: " . $conn->error);
        $is_logged_in = false;
    }
} else {
    error_log("DEBUG: forum_data.php - SESSION username không được đặt. Người dùng chưa đăng nhập.");
}

// Hàm lấy URL avatar cho bài viết (không liên quan trực tiếp đến avatar người dùng hiện tại)
function get_post_avatar_url($is_admin, $gender, $head, $is_pinned) {
    if ($is_pinned == 1) {
        return "/images/avatar/6101.gif";
    }

    if ($is_admin == 1) {
        if ($gender == 0) {
            return "/images/avatar/10.png";
        } elseif ($gender == 1) {
            return "/images/avatar/11.png";
        } elseif ($gender == 2) {
            return "/images/avatar/12.png";
        } else {
            return "/images/avatar/12.png";
        }
    } else {
        if ($head > 0) {
            return "/images/avatar/" . htmlspecialchars($head) . ".png";
        } else {
            if ($gender == 0) {
                return "/images/avatar/0.png";
            } elseif ($gender == 1) {
                return "/images/avatar/1.png";
            } elseif ($gender == 2) {
                return "/images/avatar/2.png";
            } else {
                return "/images/avatar/default_avatar.png";
            }
        }
    }
}

// Phần code dưới đây không liên quan trực tiếp đến avatar người dùng mà là các bài viết
// Nhưng tôi sẽ giữ nguyên để đảm bảo tính toàn vẹn của file forum_data.php của bạn.
$sql_pinned = "
    SELECT
        p.id,
        p.tieude,
        p.username,
        p.created_at,
        p.ghimbai,
        a.admin,
        pl.gender,
        pl.head
    FROM
        posts p
    LEFT JOIN
        account a ON p.username = a.username
    LEFT JOIN
        player pl ON a.id = pl.account_id
    WHERE
        p.ghimbai = 1
    ORDER BY
        p.created_at DESC";
$result_pinned = $conn->query($sql_pinned);

$pinned_posts = [];
if ($result_pinned) {
    while ($row = $result_pinned->fetch_assoc()) {
        $row['avatar_url'] = get_post_avatar_url($row['admin'] ?? 0, $row['gender'] ?? 0, $row['head'] ?? 0, $row['ghimbai'] ?? 0);
        $pinned_posts[] = $row;
    }
    $result_pinned->free();
} else {
    error_log("Lỗi truy vấn bài viết đã ghim: " . $conn->error);
}

$posts_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$sql_count_unpinned = "SELECT COUNT(*) AS total_posts FROM posts WHERE ghimbai = 0";
$result_count = $conn->query($sql_count_unpinned);
$total_unpinned_posts = 0;
if ($result_count) {
    $row_count = $result_count->fetch_assoc();
    $total_unpinned_posts = $row_count['total_posts'];
    $result_count->free();
} else {
    error_log("Lỗi truy vấn tổng số bài viết chưa ghim: " . $conn->error);
}

$total_pages = ceil($total_unpinned_posts / $posts_per_page);

if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
} elseif ($total_pages == 0) {
    $current_page = 1;
}

$offset = ($current_page - 1) * $posts_per_page;
if ($offset < 0) {
    $offset = 0;
}

$sql_unpinned = "
    SELECT
        p.id,
        p.tieude,
        p.username,
        p.created_at,
        p.ghimbai,
        a.admin,
        pl.gender,
        pl.head
    FROM
        posts p
    LEFT JOIN
        account a ON p.username = a.username
    LEFT JOIN
        player pl ON a.id = pl.account_id
    WHERE
        p.ghimbai = 0
    ORDER BY
        p.created_at DESC
    LIMIT ?, ?";

$stmt_unpinned = $conn->prepare($sql_unpinned);
$unpinned_posts = [];
if ($stmt_unpinned) {
    $stmt_unpinned->bind_param("ii", $offset, $posts_per_page);
    $stmt_unpinned->execute();
    $result_unpinned = $stmt_unpinned->get_result();
    while ($row = $result_unpinned->fetch_assoc()) {
        $row['avatar_url'] = get_post_avatar_url($row['admin'] ?? 0, $row['gender'] ?? 0, $row['head'] ?? 0, $row['ghimbai'] ?? 0);
        $unpinned_posts[] = $row;
    }
    $result_unpinned->free();
    $stmt_unpinned->close();
} else {
    error_log("Lỗi prepare truy vấn bài viết chưa ghim: " . $conn->error);
}
?>