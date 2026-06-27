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
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

            /* ===== RESET & BASE ===== */
            *, *::before, *::after { box-sizing: border-box; }
            body {
                background: #0c0a09 !important;
                background-image: radial-gradient(ellipse at 50% 0%, rgba(120,53,15,0.25) 0%, transparent 60%), radial-gradient(ellipse at 80% 100%, rgba(154,52,18,0.12) 0%, transparent 50%) !important;
                font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
                color: #e7e5e4 !important;
                margin: 0; padding: 0; min-height: 100vh;
                -webkit-font-smoothing: antialiased;
            }

            /* ===== SNOW & EFFECTS ===== */
            .snowEffect { position: fixed; width: 100%; height: 100%; left: 0; top: 0; z-index: 1; pointer-events: none; }
            #snowcanvas { position: fixed; z-index: 0; }

            /* ===== LAYOUT ===== */
            .body_body { max-width: 820px !important; margin: 0 auto !important; padding: 16px; position: relative; z-index: 10; }
            .left_top, .bg_top, .right_top, .left_b_bottom, .right_b_bottom, .footer, .left_bottom, .right_bottom, .bg_tree, .foot_bg, .bg_noel { display: none !important; }

            /* ===== MAIN CARD ===== */
            .body-content {
                background: rgba(28,25,23,0.92) !important;
                backdrop-filter: blur(20px) saturate(1.4);
                -webkit-backdrop-filter: blur(20px) saturate(1.4);
                border: 1px solid rgba(245,158,11,0.15) !important;
                border-radius: 24px !important;
                padding: 28px 24px !important;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8), 0 0 0 1px rgba(245,158,11,0.08), inset 0 1px 0 rgba(255,255,255,0.03) !important;
                margin: 16px 0 !important;
            }
            .bg-content2 { background: transparent !important; border: none !important; padding: 0 !important; box-shadow: none !important; }

            /* ===== LOGO ===== */
            h1.a { text-align: center; margin-top: 0 !important; margin-bottom: 24px !important; }
            h1.a img { filter: drop-shadow(0 8px 24px rgba(245,158,11,0.35)); transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1); height: 80px; }
            h1.a img:hover { transform: scale(1.08) translateY(-2px); }

            /* ===== NAVIGATION ===== */
            .menu2 {
                background: rgba(28,25,23,0.6) !important;
                border-radius: 16px !important;
                padding: 6px !important;
                margin-bottom: 24px !important;
                border: 1px solid rgba(245,158,11,0.12) !important;
            }
            .menu2 table { width: 100%; border-collapse: separate; border-spacing: 4px; }
            .menu2 td { background: transparent !important; border: none !important; padding: 0 !important; }
            .menu2 td a {
                display: block; padding: 10px 8px;
                color: #a8a29e !important; font-weight: 600 !important;
                text-decoration: none !important; border-radius: 12px !important;
                transition: all 0.25s cubic-bezier(0.4,0,0.2,1) !important;
                font-size: 13px !important; text-align: center; letter-spacing: 0.01em;
            }
            .menu2 td a:hover { background: rgba(245,158,11,0.1) !important; color: #fbbf24 !important; }
            .menu2 td#selected a {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                color: #fff !important;
                box-shadow: 0 4px 16px rgba(245,158,11,0.35), inset 0 1px 0 rgba(255,255,255,0.15) !important;
            }

            /* ===== AGE WARNING ===== */
            .div-12 {
                background: rgba(28,25,23,0.7) !important;
                border: 1px solid rgba(255,255,255,0.06) !important;
                border-radius: 12px !important;
                padding: 10px 16px !important;
                color: #78716c !important;
                font-size: 12px !important;
                margin-bottom: 16px !important;
                display: flex; align-items: center; gap: 8px; justify-content: center;
            }

            /* ===== AUTH BUTTONS ===== */
            .box_button_login { margin-bottom: 20px; }
            .box_button_login .w3-button {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                color: #fff !important; border: none !important;
                border-radius: 10px !important; padding: 10px 24px !important;
                font-weight: 600 !important; font-size: 13px !important;
                margin: 0 6px !important; cursor: pointer;
                transition: all 0.25s cubic-bezier(0.4,0,0.2,1) !important;
                box-shadow: 0 2px 8px rgba(245,158,11,0.25) !important;
            }
            .box_button_login .w3-button:hover {
                box-shadow: 0 8px 24px rgba(245,158,11,0.4) !important;
                transform: translateY(-2px);
            }

            /* ===== POST CONTAINERS ===== */
            .box_list_parent {
                background: transparent !important;
                border: none !important;
                border-radius: 0 !important;
                margin-bottom: 0 !important;
                padding: 0 !important;
            }
            .box_list_parent_next, .box_parent_list_next {
                background: rgba(28,25,23,0.5) !important;
                border: 1px solid rgba(245,158,11,0.08) !important;
                border-radius: 16px !important;
                margin-bottom: 12px !important;
                overflow: hidden;
            }

            /* ===== POST TABLE LAYOUT ===== */
            .box_list_parent > form > .box_list_parent_next > table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
            }
            .box_list_parent > form > .box_list_parent_next > table > tbody > tr > td:first-child {
                width: 70px !important;
                vertical-align: top !important;
                padding: 20px 8px 20px 16px !important;
            }
            .box_list_parent > form > .box_list_parent_next > table > tbody > tr > td:last-child {
                padding: 20px 20px 20px 8px !important;
                vertical-align: top !important;
            }
            .box_list_c_s { color: #d6d3d1 !important; }
            .box_list_b_s {
                background: transparent !important;
                border-radius: 12px !important;
                padding: 4px !important;
            }

            /* ===== POST TITLE ===== */
            .box_title_bviet {
                color: #fbbf24 !important;
                font-weight: 700 !important;
                font-size: 18px !important;
                line-height: 1.4 !important;
                margin-bottom: 12px !important;
                letter-spacing: -0.01em;
            }

            /* ===== POST CONTENT ===== */
            .box_ndung_bviet {
                color: #d6d3d1 !important;
                line-height: 1.8 !important;
                font-size: 14px !important;
                letter-spacing: 0.01em;
            }
            .box_ndung_bviet img {
                max-width: 100% !important;
                border-radius: 12px;
                margin: 12px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            }

            /* ===== POST META (time, likes) ===== */
            .box_timee_bviet {
                border-top: 1px solid rgba(245,158,11,0.1) !important;
                margin-top: 16px !important;
                padding-top: 12px !important;
            }
            .box_timee_bviet span { color: #78716c !important; font-size: 13px !important; }
            .box_oxx_admin {
                border-color: rgba(245,158,11,0.08) !important;
                padding: 2px 0 !important;
                border: none !important;
            }
            .box_oxx_admin a { color: #fbbf24 !important; }
            .box_oxx_admin span { color: #78716c !important; }

            /* ===== BACK LINK ===== */
            .backlink a {
                color: #fbbf24 !important;
                font-weight: 600;
                font-size: 13px !important;
                display: inline-flex; align-items: center; gap: 4px;
                transition: color 0.2s ease;
            }
            .backlink a:hover { color: #f59e0b !important; }
            .box_phantrang { background: transparent !important; border: none !important; padding: 8px 16px !important; }

            /* ===== AVATAR ===== */
            .avatar {
                border-radius: 50% !important;
                border: 2px solid rgba(245,158,11,0.2) !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                transition: border-color 0.3s ease;
            }
            .avatar:hover { border-color: rgba(245,158,11,0.5) !important; }

            /* ===== COMMENT FORM ===== */
            textarea {
                background: rgba(12,10,9,0.8) !important;
                color: #e7e5e4 !important;
                border: 1px solid rgba(245,158,11,0.15) !important;
                border-radius: 12px !important;
                font-family: 'Inter', sans-serif !important;
                font-size: 14px !important;
                padding: 12px !important;
                transition: border-color 0.25s ease, box-shadow 0.25s ease;
                resize: vertical;
            }
            textarea:focus {
                outline: none !important;
                border-color: rgba(245,158,11,0.4) !important;
                box-shadow: 0 0 0 3px rgba(245,158,11,0.1) !important;
            }
            .w3-blue {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                border: none !important; border-radius: 10px !important;
                color: #fff !important; font-weight: 600 !important;
                font-size: 13px !important; padding: 10px 20px !important;
                cursor: pointer;
                transition: all 0.25s ease !important;
                box-shadow: 0 2px 8px rgba(245,158,11,0.25) !important;
            }
            .w3-blue:hover {
                box-shadow: 0 6px 20px rgba(245,158,11,0.4) !important;
                transform: translateY(-1px);
            }

            /* ===== FOOTER ===== */
            .copyright {
                margin-top: 32px !important;
                text-align: center !important;
                color: #57534e !important;
                font-size: 12px !important;
                line-height: 1.6 !important;
                background: rgba(28,25,23,0.5) !important;
                padding: 20px !important;
                border-radius: 16px;
                border: 1px solid rgba(245,158,11,0.06);
            }
            .copyright b { color: #78716c !important; font-weight: 600; }
            .copyright p { color: #78716c !important; }
            .code-by-lio { margin-top: 10px; font-size: 11px; color: #57534e; display: flex; align-items: center; justify-content: center; gap: 6px; }
            .lio-badge {
                background: linear-gradient(135deg, #f59e0b, #fbbf24) !important;
                color: #1c1917 !important; font-weight: 800;
                padding: 3px 10px; border-radius: 6px;
                font-size: 9px; text-transform: uppercase;
                letter-spacing: 0.8px;
                box-shadow: 0 2px 8px rgba(245,158,11,0.3);
            }

            /* ===== BACK TO TOP ===== */
            #backTop {
                position: fixed; bottom: 24px; right: 24px; z-index: 99;
                background: rgba(245,158,11,0.9);
                width: 44px; height: 44px; border-radius: 14px;
                display: flex; align-items: center; justify-content: center;
                cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
                opacity: 0; visibility: hidden;
                border: 1px solid rgba(255,255,255,0.1);
                box-shadow: 0 4px 12px rgba(245,158,11,0.3);
            }
            #backTop.show { opacity: 1; visibility: visible; }
            #backTop:hover { background: #f59e0b; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(245,158,11,0.4); }
            #backTop img { width: 18px; height: 18px; }

            /* ===== "Code by Lio" link in post ===== */
            .box_list_parent_next > p { text-align: center; }
            .box_list_parent_next > p a { color: #78716c !important; font-size: 12px !important; transition: color 0.2s; }
            .box_list_parent_next > p a:hover { color: #fbbf24 !important; }
            .box_list_parent_next > p img { height: 10px; vertical-align: middle; opacity: 0.6; }

            /* ===== SCROLL BAR ===== */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: #1c1917; }
            ::-webkit-scrollbar-thumb { background: rgba(245,158,11,0.3); border-radius: 3px; }
            ::-webkit-scrollbar-thumb:hover { background: rgba(245,158,11,0.5); }

            /* ===== RESPONSIVE ===== */
            @media (max-width: 640px) {
                .body_body { padding: 8px !important; }
                .body-content { padding: 16px 12px !important; border-radius: 16px !important; }
                .box_title_bviet { font-size: 15px !important; }
                .menu2 td a { font-size: 11px !important; padding: 8px 4px; }
            }
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
                                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                                        color: #fff !important;
                                        font-size: 8px;
                                        font-weight: 700;
                                        padding: 3px 10px;
                                        border-radius: 20px;
                                        text-transform: uppercase;
                                        letter-spacing: 0.8px;
                                        box-shadow: 0 2px 8px rgba(239,68,68,0.35);
                                        animation: adminGlow 3s ease-in-out infinite alternate;
                                        margin-top: 4px;
                                    }
                                    @keyframes adminGlow {
                                        from { box-shadow: 0 2px 8px rgba(239,68,68,0.3); }
                                        to { box-shadow: 0 2px 16px rgba(239,68,68,0.55), 0 0 24px rgba(239,68,68,0.15); }
                                    }

                                    /* Member Badge */
                                    .member-badge {
                                        display: inline-block;
                                        background: rgba(120,113,108,0.25);
                                        border: 1px solid rgba(120,113,108,0.2);
                                        color: #a8a29e !important;
                                        font-size: 7px;
                                        font-weight: 600;
                                        padding: 2px 8px;
                                        border-radius: 20px;
                                        text-transform: uppercase;
                                        letter-spacing: 0.5px;
                                        margin-top: 4px;
                                    }

                                    /* Username styling */
                                    .username-display {
                                        color: #fbbf24 !important;
                                        font-size: 11px !important;
                                        font-weight: 700 !important;
                                        text-decoration: none !important;
                                        display: block;
                                        text-align: center;
                                        line-height: 1.4;
                                        margin-top: 6px;
                                    }
                                    .admin-username {
                                        color: #f87171 !important;
                                        text-shadow: 0 0 12px rgba(239,68,68,0.25);
                                    }

                                    /* Post card - avatar sidebar */
                                    .box_list_b_s {
                                        background: transparent !important;
                                        border-radius: 12px !important;
                                        padding: 4px !important;
                                    }
                                    .box_oxx_admin {
                                        border: none !important;
                                        padding: 4px 0 !important;
                                    }
                                    .box_timee_bviet {
                                        border-top: 1px solid rgba(245,158,11,0.08) !important;
                                        margin-top: 14px !important;
                                        padding-top: 10px !important;
                                    }

                                    /* Comment table styling */
                                    table[style*="margin-bottom: 5px"] {
                                        border: 1px solid rgba(245,158,11,0.08) !important;
                                        border-radius: 14px !important;
                                        overflow: hidden;
                                        background: rgba(28,25,23,0.4) !important;
                                        margin-bottom: 12px !important;
                                        transition: border-color 0.25s ease;
                                    }
                                    table[style*="margin-bottom: 5px"]:hover {
                                        border-color: rgba(245,158,11,0.18) !important;
                                    }

                                    /* Delete button */
                                    a[style*="color: red"][style*="font-size: 9px"] {
                                        background: rgba(239,68,68,0.1) !important;
                                        color: #f87171 !important;
                                        padding: 3px 10px !important;
                                        border-radius: 6px !important;
                                        font-weight: 600 !important;
                                        font-size: 10px !important;
                                        transition: all 0.2s ease !important;
                                        text-decoration: none !important;
                                    }
                                    a[style*="color: red"][style*="font-size: 9px"]:hover {
                                        background: rgba(239,68,68,0.25) !important;
                                        color: #fca5a5 !important;
                                    }

                                    /* Comment section headers */
                                    .box_list_parent_next .box_list_c_s {
                                        padding: 16px !important;
                                    }
                                    .box_list_parent_next .box_title_bviet {
                                        font-size: 15px !important;
                                        margin-bottom: 14px !important;
                                        padding-bottom: 10px;
                                        border-bottom: 1px solid rgba(245,158,11,0.08);
                                    }

                                    /* Login prompt */
                                    .box_list_parent_next p a {
                                        color: #fbbf24 !important;
                                        font-weight: 600;
                                        text-decoration: none;
                                        transition: color 0.2s ease;
                                    }
                                    .box_list_parent_next p a:hover {
                                        color: #f59e0b !important;
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
