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
    $tieude = htmlspecialchars($_POST["tieude"]);
    $noidung = htmlspecialchars($_POST["noidung"]);

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

    .message {
        margin-top: 10px;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
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
            <div class="a" align="center"><img src="/images/logo_sk_he.png" height="90" /></div>
            <div id="top">
                <div class="link-more">
                    <div class="h" align="center">
                        <div class="bg_tree"></div>
                        <div class="bg_noel"></div>
                        <div class="menu2" style="background: #561d00;">
                            <table width="100%" border="0" cellspacing="4">
                                <tr class="menu">
                                    <td><a href="/">Trang Chủ</a></td>
                                    <td id="selected"><a href="/forum.php">Diễn Đàn</a></td>
                                </tr>
                            </table>
                        </div>
                        <div class="body" style="text-align:center">
                            <div style="font-size:10px;">Điền thông tin bài viết của bạn.</div>
                            <center>
                                <form id="postForm" method="POST" action="">
                                    <table>
                                        <tr>
                                            <td colspan=2><label for="tieude">Tiêu đề:</label></td>
                                            <td colspan=2><input name="tieude" type="text" value="" required
                                                    style="width: 100%; padding: 5px; box-sizing: border-box;" /></td>
                                        </tr>
                                        <tr>
                                            <td colspan=2><label for="noidung">Nội dung:</label></td>
                                            <td colspan=2>
                                                <textarea name="noidung" rows="8" required
                                                    style="width: 100%; padding: 5px; box-sizing: border-box;"></textarea>
                                            </td>
                                        </tr>
                                    </table>
                                    <div id="postMessage" class="message" style="display:none;"></div>
                                    <?php if (!empty($_alert)): ?>
                                        <div class="message <?php echo strpos($_alert, 'alert-success') !== false ? 'success' : 'error'; ?>"
                                            style="display:block;">
                                            <?php echo strip_tags($_alert); ?>
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="w3-button w3-red" value="Đăng bài" id="button1"
                                        name="submit">Đăng Bài</button><br />
                                    <div style="font-size:10px;">
                                        <a href="/forum.php">Quay lại Diễn Đàn</a>
                                    </div>
                                </form><br>
                            </center>
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
        <div class="copyright"><br><b>Bản quyền thuộc về Chú Bé Rồng Online - 2013</b><div style="margin-top:8px;font-size:12px;color:#7b7870;">Developed & Optimized by <span style="background:linear-gradient(135deg,#f97316,#febb12);color:#000;font-weight:800;padding:2px 8px;border-radius:4px;font-size:10px;text-transform:uppercase;">Code by Lio</span></div></div>
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