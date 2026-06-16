<?php
require_once 'settings.php';
require_once 'forum_data.php';
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Chú Bé Rồng Onlines - Ngọc Rồng Online</title>
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
    <link rel="stylesheet" href="/view/static/css/forum.css?v=1.2">
    <script src="https://www.google.com/recaptcha/api.js?render="></script>
	<script src="/view/static/js/disable_devtools.js"></script>
    <style>
        .code-by-lio { margin-top: 12px; font-size: 12px; color: #7b7870; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .lio-badge { background: linear-gradient(135deg, #f97316, #febb12) !important; color: #000 !important; font-weight: 800; padding: 2px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 5px rgba(249,115,22,0.3); }
    </style>
</head>
<body>
    <div class="snowEffect">
        <canvas id="snowcanvas" height="100%" width="100%"></canvas>
    </div>

    <div style="position: relative;" class="body_body">
        <a href="#" id="backTop"><img id='backTopimg' src='/images/favicon-32x32.png' alt='top' /> </a>

        <div class="div-12">
            <img height=12 src="/images/18-1.png" style="vertical-align: middle;" />
            <span style="vertical-align: middle;">Dành cho người chơi trên 18 tuổi. Chơi quá 180 phút mỗi ngày sẽ hại sức khỏe.
            </span>
        </div>
        <div class="left_top"></div>
        <div class="bg_top">
            <div class="right_top"></div>
        </div>
        <div class="body-content">
            <div class="bg-content2">
                <h1 class="a">
                    <a href="/" title="game bảy viên Chú Bé Rồng Online">
                        <img height=90 src="/images/logo_sk_he.png" alt="game bảy viên Chú Bé Rồng Online" /></a>
                </h1>
                <div id="top">
                    <div class="link-more">
                        <div class="h">
                            <div class="bg_noel"></div>
                            <div class="h">
                                <div class="menu2">
                                    <table width="100%" cellspacing="4">
                                        <tr class="menu">
                                            <td>
                                                <a href="/trang-chu.php">Trang Chủ</a>
                                            </td>
                                            <td>
                                                <a href="/gioi-thieu.php">Giới Thiệu</a>
                                            </td>
                                            <td>
                                                <a href="/forum.php" title="Diễn Đàn">Diễn Đàn</a>
                                            </td>
                                            <td>
                                                <a href="https://www.facebook.com/ntdinh24/">Fanpage</a>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    var currentUrl = window.location.pathname;
                                    document.querySelectorAll(".menu a").forEach(function(link) {
                                        if (link.getAttribute("href") === currentUrl) {
                                            document.querySelector("#selected")?.removeAttribute("id");
                                            link.parentElement.id = "selected";
                                        }
                                    });
                                });
                            </script>
                            <div class="body">
                                <div class="box_inputboxx" style="width:100%">
    <?php if ($is_logged_in): ?>
        <div id="user-info" style="color:white; text-align:center;">
            <div class="user-details"> <br>
                <span>Xin chào: <?php echo htmlspecialchars($display_player_name); ?></span>
                <a href="/app/nap-ngoc.php">Nạp Tiền</a> <br>
                <a href="/app/doi-vang.php">Đổi Thỏi Vàng</a> <br>
                <a href="/app/doi-mat-khau.php">Đổi mật khẩu</a> <br>
                <a href="/app/logout.php">Đăng xuất</a> <br>
                <?php if (!empty($is_admin_for_avatar)): ?>
                    <a href="/admin/">Admin</a> <br>
                <?php endif; ?>
        </div>
<?php else: ?>
    <div id="auth-buttons" class="box_button_login" style="width:100%; text-align:center; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
        <a id="tab-register" href="/app/login.php" style="flex-shrink: 0;">
            <button class="w3-button w3-red w3-small w3-hover-green">Đăng Nhập</button>
        </a>
        <a id="tab-leaderboard" href="bang-xep-hang" style="flex-shrink: 0;">
            <button class="w3-button w3-red w3-small w3-hover-green">Bảng Xếp Hạng</button>
        </a>

        <a id="tab-top-nap" href="top-nhiem-vu" style="flex-shrink: 0;">
            <button class="w3-button w3-red w3-small w3-hover-green">Top Nhiệm Vụ</button>
        </a>
    </div>
    <?php endif; ?>
</div>


                                <br>

                                <div id="box_login_ads">
    <div id="columns" style="text-align:center">
        <figure>
            <a href="https://xnxx.com" target="_blank"> <img height="35" src="/images/jar.png" alt="CHÚ BÉ RỒNG ONLINE"></a>
            <br>
            </a>
            <figcaption>
                <span style="color:rgb(209, 9, 50);">237</span> <a href="#" onclick="if (!window.__cfRLUnblockHandlers) return false; openWinjad()" title="CHÚ BÉ RỒNG ONLINE" target="_blank" data-cf-modified-6e49640f1ec245fe06a7b8fa-="">
                </a>
                <br> <br>
            </figcaption>
        </figure>

        <figure>
            <a href="https://drive.google.com/file/d/1ofJkH001UW_d8szxhPD2B9aSJV8VbYTI/view?usp=sharing" title="CHÚ BÉ RỒNG ONLINE">
                <img height="35" src="/images/android.png" alt="CHÚ BÉ RỒNG ONLINE">
            </a>
            <figcaption><span style="color:rgb(209, 9, 50);">244</span>
                <br>
                <a href="/?c=huong-dan"></a>
            </figcaption>
        </figure>

        <figure>
            <a href="https://drive.google.com/file/d/1ofJkH001UW_d8szxhPD2B9aSJV8VbYTI/view?usp=sharing" title="CHÚ BÉ RỒNG ONLINE">
                <img height="35" src="/images/play.png" alt="CHÚ BÉ RỒNG ONLINE">
            </a>
            <figcaption><span style="color:rgb(209, 9, 50);">244</span>
                <br> <br>
            </figcaption>
        </figure>

        <figure>
            <a href="https://drive.google.com/file/d/1oi_cuLSgN1QcOTREACKSn3effveoiNNU/view?usp=sharing" title="CHÚ BÉ RỒNG ONLINE">
                <img height="35" src="/images/pc.png" alt="CHÚ BÉ RỒNG ONLINE">
            </a>
            <figcaption><span style="color:rgb(209, 9, 50);">244</span>
                <br> <br>
            </figcaption>
        </figure>

        <figure>
            <a href="https://drive.google.com/file/d/1okys2XJ9bDTsrG7msPk54Xdi23DECmCp/view?usp=sharing" title="CHÚ BÉ RỒNG ONLINE">
                <img style="margin-bottom:0px" height="35" src="/images/ip.png" alt="CHÚ BÉ RỒNG ONLINE">
            </a>
            <figcaption>
                <a href="<?php /* TestFlight */ ?>">Testflight 1</a> <br> <br>
            </figcaption>
        </figure>


                                </div>
                                            <div class="body">
                                                <div id="box_forums" class="beta_test">
                                                    <div class="box_list_chuyenmuc">
                                                        <?php if (!empty($pinned_posts)) : ?>
                                                            <div id="stick">
                                                                <?php foreach ($pinned_posts as $post) : ?>
                                                                    <div class="forum-post-item">
                                                                        <div class="post-avatar-wrapper">
                                                                            <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" alt="Post Avatar">
                                                                        </div>
                                                                        <div class="post-content">
                                                                            <a class="post-title-link" href="bai-viet.php?id=<?php echo htmlspecialchars($post['id']); ?>" title="<?php echo htmlspecialchars($post['tieude']); ?>">
                                                                                <?php echo htmlspecialchars($post['tieude']); ?> <img src="/images/gif/hot.gif" class="hot-icon" alt="Hot">
                                                                            </a>
                                                                            <div class="post-meta-info">
                                                                                bởi <a href="javascript:void(0)"><?php echo htmlspecialchars($post['username']); ?></a>
                                                                                <span style="color:red">☆</span>
                                                                                <i>
                                                                                    <?php echo date_format(date_create($post['created_at']), 'd/m/Y H:i'); ?>
                                                                                </i>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <br>
                                                        <?php endif; ?>

                                                        <?php if (!empty($unpinned_posts)) : ?>
                                                            <?php foreach ($unpinned_posts as $post) : ?>
                                                                <div class="forum-post-item">
                                                                    <div class="post-avatar-wrapper">
                                                                        <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" alt="User Avatar">
                                                                    </div>
                                                                    <div class="post-content">
                                                                        <a class="post-title-link" href="bai-viet.php?id=<?php echo htmlspecialchars($post['id']); ?>" title="<?php echo htmlspecialchars($post['tieude']); ?>">
                                                                            <?php echo htmlspecialchars($post['tieude']); ?>
                                                                        </a>
                                                                        <div class="post-meta-info">
                                                                            bởi <a href="javascript:void(0)"><?php echo htmlspecialchars($post['username']); ?></a>
                                                                            <span style="color:red">☆</span>
                                                                            <i>
                                                                                <?php echo date_format(date_create($post['created_at']), 'd/m/Y H:i'); ?>
                                                                            </i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>

                                                        <?php if ($total_pages > 1) : ?>
                                                        <div class="pagination-container">
                                                            <div class="pagination">
                                                                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                                                    <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="post-button-section">
                                                        <hr>
                                                        <a href="dang-bai.php">
                                                            <span>Đăng Bài</span>
                                                        </a>
                                                        <hr>
                                                    </div>
                                                    <br>
                                                    <div class="bg_tree"></div>
                                                    <div class="foot_bg"></div>
                                                    <div class="left_b_bottom">
                                                        <div class="right_b_bottom">
                                                            <div class="footer">
                                                                <div class="left_bottom"></div>
                                                                <div class="right_bottom"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="copyright" style="line-height: 13px">
                                                        <b>Bản quyền thuộc về Chú Bé Rồng Online - 2013</b>
                                                        <div class="code-by-lio">Developed & Optimized by <span class="lio-badge">Code by Lio</span></div>
                                        </div>
                                            <script src="/view/static/js/ThreeCanvas.js" type="text/javascript"></script>
                                            <script src="/view/static/js/Snow3d.js" type="text/javascript"></script>
                                            <script src="/view/static/js/animation.js?v5" type="text/javascript"></script>
                                            <script src="/view/static/js/rocket-loader.min.js" data-cf-settings="3248e74b3f0d3f240922716b-|49" defer></script>
                                            <script>
                                                $(document).ready(function() {
                                                    var lastPostTime = 0;
                                                    $("form[name='loginform'], form[name='registerform']").submit(function(event) {
                                                        event.preventDefault();
                                                        var now = Date.now();
                                                        if (now - lastPostTime < 10000) {
                                                            var secondsLeft = Math.ceil((10000 - (now - lastPostTime)) / 1000);
                                                            $("#comment_error").css("color", "red").text("Bạn chỉ có thể post mỗi 10 giây. Vui lòng chờ " + secondsLeft + " giây.");
                                                            return;
                                                        }

                                                        var form = $(this);
                                                        var formData = form.serialize();
                                                        var action = form.attr("id");
                                                        var csrfToken = $("#csrf_token").val();

                                                        formData += '&csrf_token=' + csrfToken + '&action=' + action;

                                                        $.post("/Api/Auth", formData)
                                                            .done(function(response) {
                                                                if (response.status === "success") {
                                                                    $("#comment_error").css("color", "green").text(response.message);
                                                                    lastPostTime = Date.now();
                                                                    if ($("#authModal").length) {
                                                                        $("#authModal").modal("hide");
                                                                    }
                                                                    setTimeout(function() {
                                                                        window.location.reload();
                                                                    }, 2000);
                                                                } else {
                                                                    $("#comment_error").css("color", "red").text(response.message);
                                                                }
                                                            })
                                                            .fail(function(jqXHR) {
                                                                var errorMessage = (jqXHR.responseJSON && jqXHR.responseJSON.message) || "Vui lòng thử lại trong ít phút nữa.";
                                                                $("#comment_error").css("color", "red").text(errorMessage);
                                                            });
                                                    });
                                                });
                                                function showTab(tab) {
                                                }
                                            </script>
</body>
</html>
