<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'settings.php';
require_once __DIR__ . '/connect.php';

$post_detail = null;
$_alert = '';
$logged_in_user_id = $_SESSION['id'] ?? null;
$logged_in_username = $_SESSION['username'] ?? null;

$logged_in_player_gender = 0;
$logged_in_player_head = 0;
$is_admin = 0;
if ($logged_in_username !== null && isset($conn)) {
    $stmt_user_info = $conn->prepare("
        SELECT
            p.gender,
            p.head,
            a.admin
        FROM account a
        LEFT JOIN player p ON a.id = p.account_id
        WHERE a.username = ?
    ");
    if ($stmt_user_info) {
        $stmt_user_info->bind_param("s", $logged_in_username);
        $stmt_user_info->execute();
        $result_user_info = $stmt_user_info->get_result();
        if ($result_user_info->num_rows > 0) {
            $user_info = $result_user_info->fetch_assoc();
            $logged_in_player_gender = $user_info['gender'] ?? 0;
            $logged_in_player_head = $user_info['head'] ?? 0;
            $is_admin = ($user_info['admin'] ?? 0) == 1;
        }
        $stmt_user_info->close();
    }
}

$_is_logged_in = ($logged_in_username !== null);

$post_id = null;
if (isset($_GET['id'])) {
    if (filter_var($_GET['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))) {
        $post_id = intval($_GET['id']);
    } else {
        $_alert = "<div class='alert alert-danger'>ID bài viết không hợp lệ.</div>";
    }
}
if ($post_id !== null && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.tieude,
            p.noidung,
            p.username,
            p.created_at,
            p.image,
            p.ghimbai,     -- Lấy thêm cột ghimbai
            pl.gender AS author_gender,
            pl.head AS author_head,
            a.admin AS author_is_admin
        FROM
            posts p
        LEFT JOIN
            account a ON p.username = a.username
        LEFT JOIN
            player pl ON a.id = pl.account_id
        WHERE p.id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $post_detail = $result->fetch_assoc();
            $conn->query("UPDATE posts SET views = views + 1 WHERE id = " . $post_id);

            $author_avatar_src = '/images/avatar/default_avatar.png';
            $author_gender = $post_detail['author_gender'] ?? 0;
            $author_is_admin = $post_detail['author_is_admin'] ?? 0;
            $author_head = $post_detail['author_head'] ?? 0;
            $is_ghimbai = ($post_detail['ghimbai'] ?? 0) == 1;
            if ($is_ghimbai || $author_is_admin == 1) {
                $author_avatar_src = "/images/avatar/6101.gif";
            } else {
                if ($author_head > 0) {
                    $author_avatar_src = "/images/avatar/" . htmlspecialchars($author_head) . ".png";
                } else {
                    if ($author_gender == 0) {
                        $author_avatar_src = "/images/avatar/0.png";
                    } elseif ($author_gender == 1) {
                        $author_avatar_src = "/images/avatar/1.png";
                    } elseif ($author_gender == 2) {
                        $author_avatar_src = "/images/avatar/2.png";
                    } else {
                        $author_avatar_src = "/images/avatar/default_avatar.png";
                    }
                }
            }
            $post_detail['author_avatar_path'] = $author_avatar_src;
            $post_image_raw = $post_detail['image'] ?? null;
            $post_image_path = '';

            if ($post_image_raw) {
                $decoded_images = json_decode($post_image_raw);
                $image_source = '';
                if (is_array($decoded_images) && !empty($decoded_images)) {
                    $image_source = $decoded_images[0];
                } else {
                    $image_source = $post_image_raw;
                }
                if (filter_var($image_source, FILTER_VALIDATE_URL)) {
                    $post_image_path = htmlspecialchars($image_source);
                } else {
                    $post_image_path = '/images/forum/' . htmlspecialchars($image_source);
                }
            }
            $post_detail['display_image_path'] = $post_image_path;
        }
        $stmt->close();
    } else {
        $_alert = "<div class='alert alert-danger'>Đã xảy ra lỗi khi tải bài viết. Vui lòng thử lại sau.</div>";
    }
} else if ($post_id === null && !isset($_GET['delete_comment_id'])) {
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    if ($logged_in_username === null) {
        $_alert = "<div class='alert alert-danger'>Bạn cần đăng nhập để bình luận.</div>";
    } else {
        $comment_content = trim($_POST['comment_content']);
        $comment_post_id = intval($_POST['post_id']);

        if (strlen($comment_content) < 3) {
            $_alert = "<div class='alert alert-danger'>Nội dung bình luận phải có ít nhất 3 ký tự!</div>";
        } elseif (empty($comment_post_id) || $comment_post_id != $post_id) {
            $_alert = "<div class='alert alert-danger'>ID bài viết không hợp lệ để bình luận.</div>";
        } else {
            $comment_is_admin = $is_admin ? 1 : 0;
            $comment_gender = $logged_in_player_gender;
            $comment_head_id = $logged_in_player_head;

            $stmt_insert_comment = $conn->prepare("INSERT INTO comments (post_id, nguoidung, traloi, gender, admin, image) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_insert_comment) {
                $stmt_insert_comment->bind_param("isssii", $comment_post_id, $logged_in_username, $comment_content, $comment_gender, $comment_is_admin, $comment_head_id);
                if ($stmt_insert_comment->execute()) {
                    header("Location: bai-viet.php?id=" . $comment_post_id . "&status=comment_success");
                    exit();
                } else {
                    $_alert = "<div class='alert alert-danger'>Lỗi khi gửi bình luận: " . htmlspecialchars($stmt_insert_comment->error) . "</div>";
                }
                $stmt_insert_comment->close();
            } else {
                $_alert = "<div class='alert alert-danger'>Lỗi chuẩn bị câu lệnh SQL để gửi bình luận: " . htmlspecialchars($conn->error) . "</div>";
            }
        }
    }
}
if (isset($_GET['delete_comment_id']) && is_numeric($_GET['delete_comment_id'])) {
    $comment_to_delete_id = intval($_GET['delete_comment_id']);

    if ($logged_in_username === null) {
        $_alert = "<div class='alert alert-danger'>Bạn cần đăng nhập để thực hiện hành động này.</div>";
    } else {
        $delete_condition = "id = ?";
        $bind_types = "i";
        $bind_params = [$comment_to_delete_id];
        if (!$is_admin) {
            $delete_condition .= " AND nguoidung = ?";
            $bind_types .= "s";
            $bind_params[] = $logged_in_username;
        }

        $stmt_delete_comment = $conn->prepare("DELETE FROM comments WHERE " . $delete_condition);
        if ($stmt_delete_comment) {
            $stmt_delete_comment->bind_param($bind_types, ...$bind_params);
            if ($stmt_delete_comment->execute()) {
                if ($stmt_delete_comment->affected_rows > 0) {
                    $_alert = "<div class='alert alert-success'>Bình luận đã được xóa thành công.</div>";
                } else {
                    $_alert = "<div class='alert alert-danger'>Bạn không có quyền xóa bình luận này hoặc bình luận không tồn tại.</div>";
                }
            } else {
                $_alert = "<div class='alert alert-danger'>Lỗi khi xóa bình luận: " . htmlspecialchars($stmt_delete_comment->error) . "</div>";
            }
            $stmt_delete_comment->close();
        } else {
            $_alert = "<div class='alert alert-danger'>Lỗi chuẩn bị câu lệnh SQL để xóa bình luận: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
    if ($post_id !== null) {
        header("Location: bai-viet.php?id=" . $post_id);
        exit();
    } else {
        header("Location: /Forum");
        exit();
    }
}
$comments = [];
if ($post_id !== null && isset($conn)) {
    $stmt_comments = $conn->prepare("SELECT c.id, c.nguoidung, c.traloi, c.created_at, c.admin, c.gender, c.image AS comment_head_id FROM comments c WHERE c.post_id = ? ORDER BY c.created_at ASC");
    if ($stmt_comments) {
        $stmt_comments->bind_param("i", $post_id);
        $stmt_comments->execute();
        $result_comments = $stmt_comments->get_result();
        while ($row = $result_comments->fetch_assoc()) {
            $comment_avatar_src = '/images/avatar/default_avatar.png';
            if ($row['admin'] == 1) {
                $comment_avatar_src = "/images/avatar/6101.gif";
            } else {
                if ($row['comment_head_id'] > 0) {
                    $comment_avatar_src = "/images/avatar/" . htmlspecialchars($row['comment_head_id']) . ".png";
                } else {
                    if ($row['gender'] == 0) {
                        $comment_avatar_src = "/images/avatar/0.png";
                    } elseif ($row['gender'] == 1) {
                        $comment_avatar_src = "/images/avatar/1.png";
                    } elseif ($row['gender'] == 2) {
                        $comment_avatar_src = "/images/avatar/2.png";
                    } else {
                        $comment_avatar_src = "/images/avatar/default_avatar.png";
                    }
                }
            }
            $row['calculated_avatar_path'] = $comment_avatar_src;
            $comments[] = $row;
        }
        $stmt_comments->close();
    } else {
        $_alert = "<div class='alert alert-danger'>Đã xảy ra lỗi khi tải bình luận: " . htmlspecialchars($conn->error) . "</div>";
    }
}
if (isset($_GET['status']) && $_GET['status'] === 'comment_success') {
    $_alert = "<div class='alert alert-success'>Bình luận của bạn đã được gửi thành công!</div>";
}
?>