<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'settings.php';
require_once 'set.php';
require_once 'connect.php';

if ($_login == null) {
    header("location:login");
    exit();
}

$_alert = '';
if (isset($_SESSION['alert_message'])) {
    $_alert = $_SESSION['alert_message'];
    unset($_SESSION['alert_message']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tieude = htmlspecialchars(trim((string)($_POST["tieude"] ?? '')), ENT_QUOTES, 'UTF-8');
    $noidung = htmlspecialchars(trim((string)($_POST["noidung"] ?? '')), ENT_QUOTES, 'UTF-8');

    if (strlen($tieude) < 5 || strlen(trim(strip_tags($noidung))) < 5) {
        $_alert = "<div class='alert alert-danger'>Tiêu đề và nội dung phải có ít nhất 5 ký tự!</div>";
    } else {
        if (!isset($_username)) {
            $_alert = "<div class='alert alert-danger'>Lỗi: Không thể xác định tên người dùng. Vui lòng đăng nhập lại.</div>";
        } else {
            $stmt_player_name = $conn->prepare("SELECT p.name FROM player p JOIN account a ON a.id = p.account_id WHERE a.username = ?");
            if (!$stmt_player_name) {
                $_alert = "<div class='alert alert-danger'>Lỗi chuẩn bị câu lệnh SQL lấy tên người chơi: " . $conn->error . "</div>";
            } else {
                $stmt_player_name->bind_param("s", $_username);
                $stmt_player_name->execute();
                $result_player_name = $stmt_player_name->get_result();
                $row_player_name = $result_player_name->fetch_assoc();
                $_name = $row_player_name['name'] ?? 'Guest';

                $stmt_player_name->close();

                // Admin posts are auto-pinned (ghimbai=1) to appear in the top section
                $ghimbai_value = ($_admin == 1) ? 1 : 0;
                $stmt_insert_post = $conn->prepare("INSERT INTO posts (tieude, noidung, username, ghimbai) VALUES (?, ?, ?, ?)");
                if (!$stmt_insert_post) {
                    $_alert = "<div class='alert alert-danger'>Lỗi chuẩn bị câu lệnh SQL đăng bài: " . $conn->error . "</div>";
                } else {
                    $stmt_insert_post->bind_param("sssi", $tieude, $noidung, $_name, $ghimbai_value);

                    if ($stmt_insert_post->execute()) {
                        $stmt_update_tichdiem = $conn->prepare("UPDATE account SET tichdiem = tichdiem + 1 WHERE username = ?");
                        if (!$stmt_update_tichdiem) {
                            $_alert = "<div class='alert alert-danger'>Lỗi chuẩn bị câu lệnh SQL cập nhật tích điểm: " . $conn->error . "</div>";
                        } else {
                            $stmt_update_tichdiem->bind_param("s", $_username);
                            $stmt_update_tichdiem->execute();
                            $stmt_update_tichdiem->close();
                        }

                        $_SESSION['alert_message'] = "<div class='alert alert-success'>Bài viết đã được đăng thành công.</div>";
                        header("Location: /forum.php");
                        exit();
                    } else {
                        $_alert = "<div class='alert alert-danger'>Lỗi khi đăng bài viết: " . $stmt_insert_post->error . "</div>";
                    }
                    $stmt_insert_post->close();
                }
            }
        }
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width,maximum-scale=1,user-scalable=no" />
    <meta http-equiv="content-language" content="vi" />
    <title>Đăng bài viết mới - Chú Bé Rồng Online</title>
    <meta name="keywords" content="Chú Bé Rồng Online, Ngoc Rong Online, Ngọc Rồng Mobile" />
    <meta name="description" content="Đăng bài viết mới" />
    <meta name="robots" content="NOINDEX,FOLLOW" />
    <link rel="apple-touch-icon" href="/images/favicon-48x48.ico" />
    <link rel="icon" href='https://forum.ngocrongonline.com/app/view/images/favicon.png' type="image/x-icon" />
    <link rel="shortcut icon" href='https://forum.ngocrongonline.com/app/view/images/favicon.png' type="image/x-icon" />
    <script src="/view/static/js/disable_devtools.js"></script>
    <link rel="stylesheet" type="text/css" href="https://forum.ngocrongonline.com/app/view/css/StyleSheet.css" />
    <link rel="stylesheet" href="https://forum.ngocrongonline.com/app/view/css/template.css" />
    <link rel="stylesheet" href="https://forum.ngocrongonline.com/app/view/css/w3.css">
    <link rel="stylesheet" type="text/css" href="https://forum.ngocrongonline.com/app/css/eff.css" />
</head>
<style>
    html,
    body {
        min-height: 100%;
    }

    body {
        margin: 0;
        background:
            radial-gradient(circle at top, rgba(255, 184, 75, 0.22), transparent 34rem),
            linear-gradient(180deg, #fff7e6 0%, #f3d29a 55%, #d58a3a 100%);
        color: #371700;
        font-family: Arial, Helvetica, sans-serif;
    }

    .snowEffect {
        position: fixed;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        z-index: 99;
        overflow: hidden;
        pointer-events: none;
    }

    #snowcanvas {
        position: fixed;
        z-index: 0;
    }

    .body_body {
        width: min(100% - 16px, 960px);
        min-height: calc(100vh - 28px);
        margin: 14px auto;
        border: 1px solid #b86522;
        border-radius: 8px;
        background: rgba(255, 248, 235, 0.96);
        box-shadow: 0 14px 34px rgba(89, 35, 0, 0.22);
        overflow: hidden;
    }

    .body-content {
        padding: 14px;
    }

    .post-logo {
        display: block;
        height: 90px;
        margin: 8px auto 10px;
        object-fit: contain;
    }

    .post-nav {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
        background: linear-gradient(90deg, #6f2500, #a54305);
        border-radius: 6px;
        padding: 8px;
        margin-bottom: 14px;
    }

    .post-nav a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 118px;
        min-height: 32px;
        padding: 0 14px;
        border-radius: 6px;
        color: #fff7cf !important;
        text-decoration: none;
        font-weight: 800;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.12);
    }

    .post-nav a.active,
    .post-nav a:hover {
        background: #f97316;
        color: #fff !important;
    }

    .post-panel {
        width: min(100%, 620px);
        margin: 0 auto 18px;
        background: #fffaf0;
        border: 1px solid #f2b15a;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 8px 18px rgba(113, 55, 0, 0.12);
        box-sizing: border-box;
    }

    .post-panel h2 {
        margin: 0 0 6px;
        text-align: center;
        color: #8b2f00;
        font-size: 22px;
        line-height: 1.25;
    }

    .post-help {
        margin: 0 0 16px;
        text-align: center;
        color: #81502a;
        font-size: 13px;
    }

    .form-field {
        margin-bottom: 12px;
        text-align: left;
    }

    .form-field label {
        display: block;
        margin-bottom: 6px;
        color: #6f2500;
        font-weight: 800;
        font-size: 13px;
    }

    .form-control {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #dda25a;
        border-radius: 7px;
        background: #fff;
        color: #231000;
        padding: 10px 11px;
        font-size: 14px;
        line-height: 1.4;
        outline: none;
    }

    textarea.form-control {
        min-height: 180px;
        resize: vertical;
    }

    .form-control:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.16);
    }

    .form-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .post-submit,
    .post-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 16px;
        border: 0;
        border-radius: 7px;
        text-decoration: none;
        font-weight: 800;
        cursor: pointer;
    }

    .post-submit {
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: #fff;
    }

    .post-back {
        background: #fff2d7;
        color: #8b2f00 !important;
        border: 1px solid #f0bd76;
    }

    .message {
        margin: 10px 0 0;
        padding: 10px;
        border-radius: 7px;
        font-weight: bold;
        text-align: center;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .copyright {
        padding: 14px 10px 18px;
        text-align: center;
        color: #6b3a13;
        font-size: 13px;
        line-height: 1.5;
    }

    .copyright b {
        color: #7c2d12;
    }

    .lio-badge {
        display: inline-block;
        margin-top: 6px;
        background: linear-gradient(135deg, #f97316, #febb12);
        color: #000;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        text-transform: uppercase;
    }

    @media (max-width: 640px) {
        .body-content {
            padding: 10px;
        }

        .post-panel {
            padding: 12px;
        }

        .post-panel h2 {
            font-size: 19px;
        }
    }
</style>

<body>
    <div class="snowEffect">
        <canvas id="snowcanvas" height="100%" width="100%"></canvas>
    </div>
    <div class="body_body">
        <div class="left_top"></div>
        <div class="bg_top">
            <div class="right_top"></div>
        </div>
        <div class="body-content">
            <div class="a" align="center"><img class="post-logo" src="/images/logo_sk_he.png" alt="Chú Bé Rồng Online" /></div>
            <div id="top">
                <div class="link-more">
                    <div class="h" align="center">
                        <div class="bg_tree"></div>
                        <div class="bg_noel"></div>
                        <div class="post-nav">
                            <a href="/">Trang Chủ</a>
                            <a class="active" href="/forum.php">Diễn Đàn</a>
                        </div>
                        <div class="body" style="text-align:center">
                            <div class="post-panel">
                                <h2>Đăng Bài Viết Mới</h2>
                                <p class="post-help">Điền tiêu đề và nội dung bài viết của bạn.</p>
                                <form id="postForm" method="POST" action="">
                                    <div class="form-field">
                                        <label for="tieude">Tiêu đề</label>
                                        <input class="form-control" id="tieude" name="tieude" type="text" value="" maxlength="120" required />
                                    </div>
                                    <div class="form-field">
                                        <label for="noidung">Nội dung</label>
                                        <textarea class="form-control" id="noidung" name="noidung" rows="8" required></textarea>
                                    </div>
                                    <div id="postMessage" class="message" style="display:none;"></div>
                                    <?php if (!empty($_alert)): ?>
                                        <div class="message <?php echo strpos($_alert, 'alert-success') !== false ? 'success' : 'error'; ?>"
                                            style="display:block;">
                                            <?php echo strip_tags($_alert); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-actions">
                                        <button type="submit" class="post-submit" value="Đăng bài" id="button1" name="submit">Đăng Bài</button>
                                        <a class="post-back" href="/forum.php">Quay Lại Diễn Đàn</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <br>
                </div><br>
            </div>
        </div>
        <div class="left_b_bottom">
            <div class="right_b_bottom">
                <div class="footer">
                    <div class="left_bottom"></div>
                    <div class="right_bottom"></div>
                </div>
            </div>
        </div>
        <div class="copyright">
            <b>Bản quyền thuộc về Lio</b>
            <div>Developed &amp; Optimized by <span class="lio-badge">Code by Lio</span></div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            var initialAlert = $('#postMessage');
            if (initialAlert.text().trim() !== '') {
                initialAlert.css('display', 'block');
            }

            $('#postForm').submit(function (e) {
            });
        });
    </script>
    <script src="https://ngocrongonline.com/view/static/js/ThreeCanvas.js"></script>
    <script src="https://ngocrongonline.com/view/static/js/Snow3d.js"></script>
    <script src="https://ngocrongonline.com/view/static/js/animation.js?v4"></script>
</body>

</html>
