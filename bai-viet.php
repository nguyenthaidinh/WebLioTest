<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'post_detail_logic.php';

?>
<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="content-language" content="vi" />
        <title><?php echo $post_detail ? htmlspecialchars($post_detail['tieude']) : 'Bài viết không tồn tại'; ?> - Diễn Đàn</title>
        <meta name="keywords" content="Chú Bé Rồng Online, Ngọc Rồng Online, game ngoc rong, game 7 viên ngọc rồng" />
        <meta name="description" content="Ngoc Rong Online, Ngọc Rồng Mobile, Dragon Ball Online" />
        <meta name="robots" content="INDEX,FOLLOW" />
        <link rel="apple-touch-icon" href="/images/favicon-48x48.ico" />
        <link rel="icon" href="/images/favicon-48x48.ico" type="image/x-icon" />
        <link rel="shortcut icon" href="/images/favicon-48x48.ico" type="image/x-icon" />
        <link rel="icon" type="image/png" href="/images/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/images/favicon-64x64.png" sizes="64x64">
        <link rel="icon" type="image/png" href="/images/favicon-128x128.png" sizes="128x128">
        <link rel="icon" type="image/png" href="/images/favicon-48x48.png" sizes="48x48">
        <script src="/view/static/js/disable_devtools.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="app/wiew/css/StyleSheet.css" />
        <link rel="stylesheet" href="app/view/css/w3.css">
        <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
        <link rel="stylesheet" href="/view/static/css/eff.css?v=1.00">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
            body {
                background: radial-gradient(circle at center, #351404 0%, #0a0300 100%) !important;
                font-family: 'Outfit', sans-serif !important;
                color: #f3f4f6 !important;
                margin: 0; padding: 0; min-height: 100vh;
            }
            .snowEffect { position: fixed; width: 100%; height: 100%; left: 0; top: 0; z-index: 1; pointer-events: none; }
            #snowcanvas { position: fixed; z-index: 0; }
            .body_body { max-width: 960px !important; margin: 0 auto !important; padding: 20px; box-sizing: border-box; position: relative; z-index: 10; }
            .left_top, .bg_top, .right_top, .left_b_bottom, .right_b_bottom, .footer, .left_bottom, .right_bottom, .bg_tree, .foot_bg, .bg_noel { display: none !important; }
            .body-content { background: rgba(22, 10, 5, 0.85) !important; backdrop-filter: blur(16px); border: 1.5px solid rgba(249, 115, 22, 0.35) !important; border-radius: 20px !important; padding: 30px !important; box-shadow: 0 20px 40px rgba(0,0,0,0.75), 0 0 30px rgba(249,115,22,0.18) !important; margin: 20px 0 !important; box-sizing: border-box; }
            .bg-content2 { background: transparent !important; border: none !important; padding: 0 !important; box-shadow: none !important; }
            h1.a { text-align: center; margin-top: 0 !important; margin-bottom: 25px !important; }
            h1.a img { filter: drop-shadow(0 4px 12px rgba(249,115,22,0.5)); transition: transform 0.3s ease; height: 90px; }
            h1.a img:hover { transform: scale(1.06); }
            .menu2 { background: rgba(26,12,6,0.7) !important; border-radius: 12px !important; padding: 8px 12px !important; margin-bottom: 25px !important; border: 1px solid rgba(249,115,22,0.25) !important; }
            .menu2 table { width: 100%; border-collapse: collapse; }
            .menu2 td { background: transparent !important; border: none !important; padding: 0 !important; }
            .menu2 td a { display: block; padding: 12px; color: #b3b3b3 !important; font-weight: 600 !important; text-decoration: none !important; border-radius: 8px !important; transition: all 0.3s ease !important; font-size: 15px !important; text-align: center; }
            .menu2 td#selected a, .menu2 td a:hover { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; color: #fff !important; box-shadow: 0 4px 15px rgba(249,115,22,0.45) !important; }
            .div-12 { background: rgba(0,0,0,0.6) !important; border: 1px solid rgba(255,255,255,0.05) !important; border-radius: 8px !important; padding: 10px 15px !important; color: #9ca3af !important; font-size: 13px !important; margin-bottom: 20px !important; display: flex; align-items: center; gap: 10px; justify-content: center; }
            .box_button_login { margin-bottom: 15px; }
            .box_button_login .w3-button { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; color: #fff !important; border: none !important; border-radius: 8px !important; padding: 10px 20px !important; font-weight: 600 !important; margin: 0 5px !important; transition: all 0.3s ease !important; }
            .box_button_login .w3-button:hover { box-shadow: 0 4px 15px rgba(249,115,22,0.45) !important; transform: translateY(-2px); }
            .box_list_parent, .box_list_parent_next, .box_parent_list_next { background: rgba(45,20,8,0.55) !important; border: 1px solid rgba(249,115,22,0.15) !important; border-radius: 12px !important; margin-bottom: 15px !important; }
            .box_list_c_s { color: #d1d5db !important; }
            .box_list_b_s { background: rgba(30,15,5,0.7) !important; }
            .box_title_bviet { color: #febb12 !important; font-weight: 700 !important; }
            .box_ndung_bviet { color: #d1d5db !important; line-height: 1.7 !important; }
            .box_ndung_bviet img { max-width: 100% !important; border-radius: 8px; }
            .box_timee_bviet span { color: #9ca3af !important; }
            .box_oxx_admin { border-color: rgba(249,115,22,0.2) !important; }
            .box_oxx_admin a { color: #febb12 !important; }
            .backlink a { color: #febb12 !important; font-weight: 600; }
            .box_phantrang { background: transparent !important; border: none !important; }
            .avatar { border-radius: 50% !important; border: 2px solid rgba(249,115,22,0.3) !important; }
            textarea { background: rgba(30,15,5,0.8) !important; color: #f3f4f6 !important; border: 1px solid rgba(249,115,22,0.25) !important; border-radius: 8px !important; }
            .w3-blue { background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important; border: none !important; border-radius: 8px !important; color: #fff !important; font-weight: 600 !important; }
            .copyright { margin-top: 40px !important; text-align: center !important; color: #8b8880 !important; font-size: 13px !important; line-height: 1.6 !important; background: rgba(0,0,0,0.3) !important; padding: 20px !important; border-radius: 12px; border: 1px solid rgba(249,115,22,0.1); }
            .copyright p { color: #a3a199 !important; }
            .code-by-lio { margin-top: 12px; font-size: 12px; color: #7b7870; display: flex; align-items: center; justify-content: center; gap: 6px; }
            .lio-badge { background: linear-gradient(135deg, #f97316, #febb12) !important; color: #000 !important; font-weight: 800; padding: 2px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 5px rgba(249,115,22,0.3); }
            #backTop { position: fixed; bottom: 25px; right: 25px; z-index: 99; background: rgba(249,115,22,0.8); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; opacity: 0; visibility: hidden; border: 1.5px solid rgba(255,255,255,0.1); }
            #backTop.show { opacity: 1; visibility: visible; }
            #backTop:hover { background: rgba(249,115,22,1); transform: scale(1.1); box-shadow: 0 4px 15px rgba(249,115,22,0.4); }
            #backTop img { width: 20px; height: 20px; }
        </style>
    </head>
    <body>
    <div class="snowEffect">
        <canvas id="snowcanvas" height="100%" width="100%"></canvas>
    </div>

    <div style="position: relative;" class="body_body">
        <a href="#" id="backTop"><img id="backTopimg" src="/images/favicon-32x32.png" alt="top" /> </a>

        <div class="div-12">
            <img height="12" src="/images/18-1.png" style="vertical-align: middle;" />
            <span style="vertical-align: middle;">Dành cho người chơi trên 18 tuổi. Chơi quá 180 phút mỗi ngày sẽ hại sức khỏe.</span>
        </div>
        <div class="left_top"></div>
        <div class="bg_top"><div class="right_top"></div></div>
        <div class="body-content">
            <div class="bg-content2">
                <h1 class="a">
                    <a href="/" title="game bảy viên Chú Bé Rồng Online">
                        <img height="90" src="/images/logo_sk_he.png" alt="game bảy viên Chú Bé Rồng Online" /></a>
                </h1>
                <div id="top">
                    <div class="link-more">
                        <div class="h">
                            <div class="bg_tree"></div>
                            <div class="bg_noel"></div>
                            <div class="menu2">
                                <table width="100%" border="0" cellspacing="4">
                                    <tr class="menu">
                                        <td style="border: 3px solid #924C31;padding: 2px;"><a href="trang-chu">Trang Chủ</a></td>
                                        <td style="border: 3px solid #924C31;padding: 2px;"><a href="gioi-thieu">Giới Thiệu</a></td>
                                        <td id="selected" style="border: 3px solid #FFAF4D;padding: 2px;"><a href="forum">Diễn Đàn</a></td>
                                        <td style="border: 3px solid #924C31;padding: 2px;"><a href="https://www.facebook.com/ntdinh24/">Fanpage</a></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="body">
                                <div id="box_login_ads">
                                    <div class="box_inputboxx" style="width:100%">
                                        <div class="box_button_login" style="width:100%;position: relative;text-align:center;">
                                            <a href="app/login.php"><button class="w3-button w3-red w3-small w3-hover-green">Đăng nhập</button></a>
                                            <a href="app/doi-mat-khau.php"><button class="w3-button w3-red w3-small w3-hover-green">Đổi mật khẩu</button></a>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <style>
                                    .w-40px { width: 40px !important; }
                                    .a-hv { cursor: pointer; }

                                    /* Admin Badge */
                                    .admin-badge {
                                        display: inline-block;
                                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
                                        color: #fff !important;
                                        font-size: 8px;
                                        font-weight: 800;
                                        padding: 2px 8px;
                                        border-radius: 10px;
                                        text-transform: uppercase;
                                        letter-spacing: 1px;
                                        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5), inset 0 1px 0 rgba(255,255,255,0.2);
                                        animation: adminGlow 2s ease-in-out infinite alternate;
                                        margin-top: 3px;
                                    }
                                    @keyframes adminGlow {
                                        from { box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4); }
                                        to { box-shadow: 0 2px 14px rgba(239, 68, 68, 0.7), 0 0 20px rgba(239, 68, 68, 0.2); }
                                    }

                                    /* Member Badge */
                                    .member-badge {
                                        display: inline-block;
                                        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
                                        color: #d1d5db !important;
                                        font-size: 7px;
                                        font-weight: 600;
                                        padding: 1px 6px;
                                        border-radius: 8px;
                                        text-transform: uppercase;
                                        letter-spacing: 0.5px;
                                        margin-top: 3px;
                                    }

                                    /* Username styling */
                                    .username-display {
                                        color: #febb12 !important;
                                        font-size: 10px !important;
                                        font-weight: 700 !important;
                                        text-decoration: none !important;
                                        display: block;
                                        text-align: center;
                                        line-height: 1.4;
                                    }
                                    .admin-username {
                                        color: #ef4444 !important;
                                        text-shadow: 0 0 8px rgba(239, 68, 68, 0.3);
                                    }

                                    /* Post card improvements */
                                    .box_list_b_s {
                                        background: rgba(30, 15, 5, 0.6) !important;
                                        border-radius: 8px !important;
                                        padding: 6px !important;
                                    }
                                    .box_oxx_admin {
                                        border-color: rgba(249,115,22,0.15) !important;
                                        padding: 4px !important;
                                    }
                                    .box_timee_bviet {
                                        border-top: 1px solid rgba(249,115,22,0.1) !important;
                                        margin-top: 10px !important;
                                        padding-top: 8px !important;
                                    }

                                    /* Comment table styling */
                                    table[style*="margin-bottom: 5px"] {
                                        border: 1px solid rgba(249,115,22,0.15) !important;
                                        border-radius: 10px !important;
                                        overflow: hidden;
                                        background: rgba(30,15,5,0.4) !important;
                                        margin-bottom: 10px !important;
                                    }

                                    /* Delete button */
                                    a[style*="color: red"][style*="font-size: 9px"] {
                                        background: rgba(239,68,68,0.15) !important;
                                        color: #f87171 !important;
                                        padding: 2px 8px !important;
                                        border-radius: 4px !important;
                                        font-weight: 600 !important;
                                        transition: all 0.2s ease !important;
                                    }
                                    a[style*="color: red"][style*="font-size: 9px"]:hover {
                                        background: rgba(239,68,68,0.3) !important;
                                    }
                                </style>
                                <div id="box_forums">
                                    <?php echo $_alert;?>
                                    <div class="box_list_parent">
                                        <div class="box_parent_list_next">
                                            <div class="box_phantrang">
                                                <div class="backlink">
                                                    <a style="color:#fff;" href="forum">Quay lại</a>
                                                </div>
                                                <div class="pagination"></div>
                                            </div>
                                        </div>
                                        <form method="POST" name="UpdateHide">
                                            <div class="box_list_parent_next">
                                                <?php if ($post_detail): ?>
                                                <table cellpadding="0" cellspacing="0" width="99%" border="0" style="table-layout:fixed;word-wrap: break-word;">
                                                    <tr>
                                                        <td width="50px;" align="center" class="box_list_c_s">
                                                            <img class="avatar" src="<?php echo htmlspecialchars($post_detail['author_avatar_path']); ?>" alt="<?php echo htmlspecialchars($post_detail['username']); ?>" />
                                                            <div class="box_list_b_s" style="background-color: #FFAF4D;">
                                                                <div class="box_list_ads">
                                                                    <div class="box_oxx_admin" style="border:none">
                                                                        <a class="username-display <?php echo ($post_detail['author_is_admin'] == 1) ? 'admin-username' : ''; ?>" href="javascript:void(0)"><?php echo htmlspecialchars($post_detail['username']); ?></a>
                                                                        <?php if ($post_detail['author_is_admin'] == 1): ?>
                                                                            <span class="admin-badge">👑 Admin</span>
                                                                        <?php else: ?>
                                                                            <span class="member-badge">Member</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="box_list_b_s">
                                                            <div class="box_list_ads">
                                                                <div class="box_oxx_admin">
                                                                    <span style="font-weight:normal;color:black;font-size:9px;"><i>
                                                                        <img style="vertical-align:middle;" title="<?php echo htmlspecialchars($post_detail['username']); ?> is offline" src="images/img/offline.png" border="0" />
                                                                        <?php echo htmlspecialchars($post_detail['created_at']); ?></i></span>
                                                                </div>
                                                                <div class="box_title_bviet"><?php echo htmlspecialchars($post_detail['tieude']); ?></div>
                                                                <div class="box_ndung_bviet">
                                                                    <?php echo nl2br(htmlspecialchars($post_detail['noidung'])); ?>
                                                                    <?php if (!empty($post_detail['display_image_path'])): ?>
                                                                        <br /><center><img src="<?php echo htmlspecialchars($post_detail['display_image_path']); ?>" /></center>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="box_timee_bviet" style="padding:3px;">
                                                                    <span style="color:#333;"><span style="color:red">☆</span> 1.000.000.000 người thích bài này.</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <p><center><a href="/forum.php" target="_blank"><img src="https://my.teamobi.com/images/new.gif"> Code by Lio <img src="https://my.teamobi.com/images/new.gif"></a></center></p>
                                                <?php else: ?>
                                                <div class="box_list_parent_next" style="margin-top: 10px;">
                                                    <div class="box_list_c_s" style="padding: 10px; text-align: center;">
                                                        Bài viết không tồn tại hoặc đã bị xóa.
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="box_parent_list_next" style="margin:0px;text-align:right;">
                                                <div class="box_phantrang">
                                                    <div class="pagination"></div>
                                                </div>
                                            </div>
                                        </form>

                                        <?php if ($post_detail):?>
                                        <div class="box_list_parent_next" style="margin-top: 10px;">
                                            <div class="box_list_c_s" style="padding: 10px;">
                                                <div class="box_title_bviet">Bình luận</div>
                                                <?php if ($_is_logged_in): ?>
                                                <form method="POST" action="bai-viet.php?id=<?php echo htmlspecialchars($post_id); ?>">
                                                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_id); ?>">
                                                    <textarea name="comment_content" rows="4" placeholder="Nhập bình luận của bạn..." style="width: 98%; padding: 5px; margin-bottom: 5px; border: 1px solid #ccc; border-radius: 3px;"></textarea>
                                                    <button type="submit" name="submit_comment" class="w3-button w3-blue w3-small w3-hover-green">Gửi bình luận</button>
                                                </form>
                                                <?php else: ?>
                                                <p style="text-align: center;">Bạn cần <a href="app/login.php">đăng nhập</a> để bình luận.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($comments)): ?>
                                        <div class="box_list_parent_next" style="margin-top: 10px;">
                                            <div class="box_list_c_s" style="padding: 10px;">
                                                <div class="box_title_bviet">Các bình luận</div>
                                                <?php foreach ($comments as $comment):
                                                    $comment_avatar_src = '/images/avatar/default_avatar.png';
                                                    if ($comment['admin'] == 0) {
                                                        if ($comment['gender'] == 0) { $comment_avatar_src = "/images/avatar/10.png"; }
                                                        elseif ($comment['gender'] == 1) { $comment_avatar_src = "/images/avatar/11.png"; }
                                                        elseif ($comment['gender'] == 2) { $comment_avatar_src = "/images/avatar/12.png"; }
                                                        else { $comment_avatar_src = "/images/avatar/6101.gif"; }
                                                    } else {
                                                        if ($comment['comment_head_id'] > 0) {
                                                            $comment_avatar_src = "/images/avatar/" . htmlspecialchars($comment['comment_head_id']) . ".png";
                                                        } else {
                                                            if ($comment['gender'] == 0) { $comment_avatar_src = "/images/avatar/0.png"; }
                                                            elseif ($comment['gender'] == 1) { $comment_avatar_src = "/images/avatar/1.png"; }
                                                            elseif ($comment['gender'] == 2) { $comment_avatar_src = "/images/avatar/2.png"; }
                                                            else { $comment_avatar_src = "/images/avatar/default_avatar.png"; }
                                                        }
                                                    }
                                                ?>
                                                <table cellpadding="0" cellspacing="0" width="99%" border="0" style="table-layout:fixed;word-wrap: break-word; margin-bottom: 5px; border: 1px solid #ddd; padding: 5px;">
                                                    <tr>
                                                        <td width="50px;" align="center" class="box_list_c_s">
                                                            <img class="avatar" src="<?php echo htmlspecialchars($comment_avatar_src); ?>" alt="<?php echo htmlspecialchars($comment['nguoidung']); ?>" />
                                                            <div class="box_list_b_s" style="background-color: #FFAF4D;">
                                                                <div class="box_list_ads">
                                                                    <div class="box_oxx_admin" style="border:none">
                                                                        <a class="username-display <?php echo ($comment['admin'] == 1) ? 'admin-username' : ''; ?>" href="javascript:void(0)"><?php echo htmlspecialchars($comment['nguoidung']); ?></a>
                                                                        <?php if ($comment['admin'] == 1): ?>
                                                                            <span class="admin-badge">👑 Admin</span>
                                                                        <?php else: ?>
                                                                            <span class="member-badge">Member</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="box_list_b_s">
                                                            <div class="box_list_ads">
                                                                <div class="box_oxx_admin">
                                                                    <span style="font-weight:normal;color:black;font-size:9px;"><i><?php echo htmlspecialchars($comment['created_at']); ?></i></span>
                                                                    <?php if ($logged_in_username === $comment['nguoidung'] || $is_admin): ?>
                                                                    <span style="float:right;"><a href="bai-viet.php?id=<?php echo htmlspecialchars($post_id); ?>&delete_comment_id=<?php echo htmlspecialchars($comment['id']); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa bình luận này không?');" style="color: red; font-size: 9px;">Xóa</a></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="box_ndung_bviet"><?php echo nl2br(htmlspecialchars($comment['traloi'])); ?></div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php elseif ($post_detail):?>
                                        <div class="box_list_parent_next" style="margin-top: 10px;">
                                            <div class="box_list_c_s" style="padding: 10px; text-align: center;">
                                                Chưa có bình luận nào cho bài viết này. Hãy là người đầu tiên!
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="box_list_chuyenmuc"></div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="copyright">
        <b>Bản quyền thuộc về Chú Bé Rồng Online - 2013</b>
        <div class="code-by-lio">Developed & Optimized by <span class="lio-badge">Code by Lio</span></div>
    </div>

    <script src="/view/static/js/ThreeCanvas.js" type="text/javascript"></script>
    <script src="/view/static/js/Snow3d.js" type="text/javascript"></script>
    <script src="/view/static/js/animation.js?v4" type="text/javascript"></script>
    <script type="text/javascript">
        (function($) {
            "use strict"
            $(function() {
                if ($('#backTop').length) {
                    var scrollTrigger = 100,
                        backToTop = function() {
                            var scrollTop = $(window).scrollTop();
                            if (scrollTop > scrollTrigger) {
                                $('#backTop').addClass('show');
                            } else {
                                $('#backTop').removeClass('show');
                            }
                        };
                    backToTop();
                    $(window).on('scroll', function() { backToTop(); });
                    $('#backTop').on('click', function(e) {
                        e.preventDefault();
                        $('html,body').animate({ scrollTop: 0 }, 700);
                    });
                }
            });
        })(jQuery);
    </script>
    </body>
</html>
