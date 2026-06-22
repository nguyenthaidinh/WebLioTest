<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Chú Bé Rồng Online</title>
    <meta name="keywords" content="Chú Bé Rồng Online, ngoc rong mobile, game ngoc rong, game 7 vien ngoc rong, game bay vien ngoc rong" />
    <meta name="description" content="Website chính thức của Chú Bé Rồng Online – Game Bay Vien Ngoc Rong Mobile nhập vai trực tuyến trên máy tính và điện thoại về Game 7 Viên Ngọc Rồng hấp dẫn nhất hiện nay!" />
    <meta name="robots" content="INDEX,FOLLOW" />

    <link rel="apple-touch-icon" href="/images/favicon-48x48.ico" />
    <link rel="icon" href="/images/favicon-48x48.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="/images/favicon-48x48.ico" type="image/x-icon" />
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/images/favicon-64x64.png" sizes="64x64">
    <link rel="icon" type="image/png" href="/images/favicon-128x128.png" sizes="128x128">
    <link rel="icon" type="image/png" href="/images/favicon-48x48.png" sizes="48x48">
    
    <script src="/view/static/js/disable_devtools.js"></script>
    <link rel="stylesheet" href="/view/static/css/template.css?v=1747863425">
    <link rel="stylesheet" href="/view/static/css/eff.css?v=1747863425">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

        body {
            background: radial-gradient(circle at center, #351404 0%, #0a0300 100%) !important;
            font-family: 'Outfit', sans-serif !important;
            color: #f3f4f6 !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .snowEffect {
            position: fixed;
            width: 100%;
            height: 100%;
            left: 0;
            top: 0;
            z-index: 1;
            pointer-events: none;
        }

        #snowcanvas {
            position: fixed;
            z-index: 0;
        }

        .body_body {
            max-width: 960px !important;
            margin: 0 auto !important;
            padding: 20px;
            box-sizing: border-box;
            position: relative;
            z-index: 10;
        }

        /* Hide old decorations */
        .left_top, .bg_top, .right_top, .left_b_bottom, .right_b_bottom, .footer, .left_bottom, .right_bottom, .bg_tree, .foot_bg, .bg_noel {
            display: none !important;
        }

        .body-content {
            background: rgba(22, 10, 5, 0.85) !important;
            backdrop-filter: blur(16px);
            border: 1.5px solid rgba(249, 115, 22, 0.35) !important;
            border-radius: 20px !important;
            padding: 30px !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.75), 0 0 30px rgba(249, 115, 22, 0.18) !important;
            margin: 20px 0 !important;
            box-sizing: border-box;
        }

        .bg-content2 {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
        }

        h1.a {
            text-align: center;
            margin-top: 0 !important;
            margin-bottom: 25px !important;
        }
        h1.a img {
            filter: drop-shadow(0 4px 12px rgba(249, 115, 22, 0.5));
            transition: transform 0.3s ease;
            height: 90px;
        }
        h1.a img:hover {
            transform: scale(1.06);
        }

        /* Header Navigation */
        .menu2 {
            background: rgba(26, 12, 6, 0.7) !important;
            border-radius: 12px !important;
            padding: 8px 12px !important;
            margin-bottom: 25px !important;
            border: 1px solid rgba(249, 115, 22, 0.25) !important;
        }
        .menu2 table {
            width: 100%;
            border-collapse: collapse;
        }
        .menu2 td {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }
        .menu2 td a {
            display: block;
            padding: 12px;
            color: #b3b3b3 !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
            font-size: 15px !important;
            text-align: center;
        }
        .menu2 td#selected a, .menu2 td a:hover {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.45) !important;
        }

        .bg_top_22 {
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 25px;
            border: 1px solid rgba(249, 115, 22, 0.3);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        .bg_top_22 img {
            display: block;
            width: 100%;
            height: auto;
        }

        /* 18+ Warning banner */
        .div-12 {
            background: rgba(0, 0, 0, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
            color: #9ca3af !important;
            font-size: 13px !important;
            margin-bottom: 20px !important;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        /* Downloads Grid */
        .download-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .download-card {
            background: rgba(45, 20, 8, 0.55) !important;
            border: 1.5px solid rgba(249, 115, 22, 0.25) !important;
            border-radius: 16px !important;
            padding: 20px !important;
            text-align: center;
            transition: all 0.3s ease !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .download-card:hover {
            transform: translateY(-5px);
            border-color: #febb12 !important;
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.25) !important;
        }
        .download-card img {
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
            height: 35px;
            margin: 5px 0;
        }
        .download-card .version {
            font-size: 13px;
            color: #febb12;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .download-card .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important;
            color: #fff !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            padding: 10px 22px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 10px rgba(249, 115, 22, 0.35) !important;
            border: none !important;
            cursor: pointer;
        }
        .download-card .download-btn:hover {
            background: linear-gradient(135deg, #c2410c 0%, #f97316 100%) !important;
            box-shadow: 0 6px 15px rgba(249, 115, 22, 0.5) !important;
            transform: translateY(-1px);
        }
        .download-card .extra-links {
            font-size: 12px;
            color: #9ca3af;
        }
        .download-card .extra-links a {
            color: #febb12 !important;
            text-decoration: none !important;
            transition: color 0.3s;
            font-weight: 600;
        }
        .download-card .extra-links a:hover {
            color: #f97316 !important;
        }

        /* Disabled card styles */
        .disabled-card {
            opacity: 0.6;
            position: relative;
        }
        .disabled-card:hover {
            transform: none !important;
            border-color: rgba(249, 115, 22, 0.25) !important;
            box-shadow: none !important;
            cursor: not-allowed;
        }
        .disabled-btn {
            background: linear-gradient(135deg, #6b6b6b 0%, #4a4a4a 100%) !important;
            box-shadow: none !important;
            cursor: not-allowed !important;
            pointer-events: none;
        }
        .disabled-btn:hover {
            transform: none !important;
        }

        /* Content Card styling */
        .bg-content {
            background: rgba(45, 20, 8, 0.55) !important;
            border: 1px solid rgba(249, 115, 22, 0.15) !important;
            border-radius: 16px !important;
            padding: 25px !important;
            margin-bottom: 25px !important;
        }
        .title {
            border-bottom: 2px solid #f97316 !important;
            padding-bottom: 10px !important;
            margin-bottom: 18px !important;
        }
        .title h4 {
            font-size: 18px !important;
            font-weight: 700 !important;
            color: #febb12 !important;
            margin: 0 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            font-size: 14px !important;
            line-height: 1.7 !important;
            color: #d1d5db !important;
        }
        .content p {
            margin-bottom: 15px !important;
        }
        .content-p {
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #febb12 !important;
            margin-top: 20px !important;
            margin-bottom: 10px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Gifs styling */
        .gif-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .gif-container img {
            border-radius: 8px;
            border: 1px solid rgba(249, 115, 22, 0.25);
            background: rgba(0, 0, 0, 0.5);
            padding: 8px;
            transition: all 0.3s ease;
        }
        .gif-container img:hover {
            transform: scale(1.05);
            border-color: #febb12;
            box-shadow: 0 0 10px rgba(249, 115, 22, 0.45);
        }

        /* Override legacy blue inline color styles to golden-orange */
        strong, p[style*="color"], p[style*="color:#012699"], p[style*="color: #012699"] strong {
            color: #febb12 !important;
        }

        .content a {
            color: #febb12 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            transition: color 0.3s;
        }
        .content a:hover {
            color: #f97316 !important;
        }

        /* Footer & copyright */
        .copyright {
            margin-top: 40px !important;
            text-align: center !important;
            color: #8b8880 !important;
            font-size: 13px !important;
            line-height: 1.6 !important;
            background: rgba(0,0,0,0.3) !important;
            padding: 20px !important;
            border-radius: 12px;
            border: 1px solid rgba(249, 115, 22, 0.1);
        }
        .copyright b {
            font-weight: 400 !important;
            color: #a3a199;
        }
        .code-by-lio {
            margin-top: 12px;
            font-size: 12px;
            color: #7b7870;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .lio-badge {
            background: linear-gradient(135deg, #f97316, #febb12) !important;
            color: #000 !important;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(249, 115, 22, 0.3);
        }

        /* Back to top button styling */
        #backTop {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 99;
            background: rgba(249, 115, 22, 0.8);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            border: 1.5px solid rgba(255, 255, 255, 0.1);
        }
        #backTop.show {
            opacity: 1;
            visibility: visible;
        }
        #backTop:hover {
            background: rgba(249, 115, 22, 1);
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
        }
        #backTop img {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="snowEffect">
        <canvas id="snowcanvas" height="100%" width="100%"></canvas>
    </div>

    <div class="body_body">
        <a href="#" id="backTop"><img id='backTopimg' src='/images/favicon-32x32.png' alt='top' /> </a>

        <div class="div-12">
            <img height="12" src="/images/18-1.png" alt="18+" style="vertical-align: middle;" />
            <span>Chơi quá 180 phút một ngày sẽ ảnh hưởng xấu đến sức khỏe.</span>
        </div>

        <div class="body-content">
            <div class="bg-content2">
                <h1 class="a">
                    <a href="/" title="game bảy viên Chú Bé Rồng Online">
                        <img height="90" src="/images/logo_sk_he.png" alt="game bảy viên Chú Bé Rồng Online" />
                    </a>
                </h1>
                
                <div id="top">
                    <div class="link-more">
                        <div class="h">
                            <div class="menu2">
                                <table width="100%" cellspacing="4">
                                    <tr class="menu">
                                        <td id='selected'>
                                            <a href="/trang-chu.php">Trang Chủ</a>
                                        </td>
                                        <td>
                                            <a href="/gioi-thieu.php">Giới Thiệu</a>
                                        </td>
                                        <td>
                                            <a href="/forum.php" title="Diễn Đàn">Diễn Đàn</a>
                                        </td>
                                        <td>
                                            <a href="https://www.facebook.com/ntdinh24/" target="_blank">Fanpage</a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="bg_top_22">
                                <img src="/images/banner_Summer.png" alt="Banner" width="100%">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="download">
                    <div class="bg-content text-center">
                        <script type="text/javascript">
                            var url;
                            function openWinjar() {
                                url = window.open("http://teamobi.com/home/game/Chu-Be-Rong-6.html?javajar", "_blank", "width=786, height=786");
                            }
                            function openWinjad() {
                                url = window.open("http://teamobi.com/home/game/Chu-Be-Rong-6.html?javajad", "_blank", "width=786, height=786");
                            }
                        </script>

                        <div class="download-grid">
                            <!-- MACOS CARD -->
                            <div class="download-card">
                                <img src="/images/macos.png" alt="MacOS version">
                                <div class="version">MacOS</div>
                                <a href="https://drive.google.com/drive/u/0/folders/1rcvGxxtDYhqyFgm91XHHIBzespPmzGA8" class="download-btn" target="_blank">Tải MacOS</a>
                                <div class="extra-links">Cho MacOS</div>
                            </div>

                            <!-- ANDROID APK CARD -->
                            <div class="download-card">
                                <img src="/images/android.png" alt="Android APK">
                                <div class="version">Android (APK)</div>
                                <a href="https://drive.google.com/drive/folders/1CdvJLR2HYk6C4b3U815eN5xElB4AypD8" class="download-btn" target="_blank">Tải APK</a>
                                <div class="extra-links">Hỗ trợ Android 5.0+</div>
                            </div>

                            <!-- GOOGLE PLAY CARD -->
                            <div class="download-card disabled-card">
                                <img src="/images/play.png" alt="Google Play">
                                <div class="version">Google Play</div>
                                <span class="download-btn disabled-btn">Đang phát triển</span>
                                <div class="extra-links">🚧 Sắp ra mắt</div>
                            </div>

                            <!-- PC WINDOWS CARD -->
                            <div class="download-card">
                                <img src="/images/pc.png" alt="PC Windows">
                                <div class="version">PC Windows</div>
                                <a href="https://drive.google.com/drive/folders/1YgI0e39HlEzV9IVUSZgQcCw9_M-K8tfd" class="download-btn" target="_blank">Tải PC</a>
                                <div class="extra-links">Cho Windows XP/7/10/11</div>
                            </div>

                            <!-- IPHONE APP CARD -->
                            <div class="download-card">
                                <img src="/images/ip.png" alt="iPhone/iPad">
                                <div class="version">iPhone / iPad</div>
                                <a href="https://drive.google.com/drive/u/0/folders/1gajpRFf_RCJlgQo7J8GBPZ78R2Yfo7oj" class="download-btn" target="_blank">Tải iOS</a>
                                <div class="extra-links">Cho iPhone / iPad</div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Giới Thiệu Section -->
                <div class="bg-content">
                    <div>
                        <div class="title">
                            <h4>Giới Thiệu</h4>
                        </div>
                        <div class="content">
                            <p>Ngọc Rồng Online là Trò Chơi Trực Tuyến với cốt truyện xoay quanh bộ truyện tranh 7 viên Ngọc Rồng. Người chơi sẽ hóa thân thành một trong những anh hùng của 3 hành tinh: Trái Đất, Xayda, Namếc. Cùng luyện tập, tăng cường sức mạnh và kỹ năng. Đoàn kết cùng chiến đấu chống lại các thế lực hung ác. Cùng nhau tranh tài.</p>
                            <p>Đặc điểm nổi bật:<br>
                            - Thể loại hành động, nhập vai. Trực tiếp điều khiển nhân vật hành động. Dễ chơi, dễ điều khiển nhân vật. Đồ họa sắc nét. Có phiên bản đồ họa cao cho điện thoại mạnh và phiên bản pixel cho máy cấu hình thấp.<br>
                            - Cốt truyện bám sát nguyên tác. Người chơi sẽ gặp tất cả nhân vật từ Bunma, Quy lão kame, Jacky-chun, Tàu Pảy Pảy... cho đến Fide, Pic, Poc, Xên, Broly, đội Bojack.<br>
                            - Đặc điểm nổi bật nhất: Tham gia đánh doanh trại độc nhãn. Tham gia đại hội võ thuật. Tham gia săn lùng ngọc rồng để mang lại điều ước cho bản thân.<br>
                            - Tương thích tất cả các dòng máy trên thị trường hiện nay: Máy tính PC, Điện thoại di động Nokia Java, Android, iPhone, Windows Phone, và máy tính bảng Android, iPad.</p>
                            
                            <div class="content-p">Cơ Bản</div>
                            <div class="gif-container">
                                <img alt="Maphongba" src="/images/gif/gif_maphongba.gif">&nbsp;
                                <img alt="Saiyain" src="/images/gif/gif_gif_Saiyain.gif">&nbsp;
                                <img alt="Super Kame" src="/images/gif/gif_supber_kame.gif">&nbsp;
                            </div>

                            <div class="content-p">VIP</div>
                            <div class="gif-container">
                                <img alt="Maphongba VIP" src="/images/gif/gif_maphongba_VIP.gif">&nbsp;
                                <img alt="Saiyain VIP" src="/images/gif/gif_gif_Saiyain_VIP.gif">&nbsp;
                                <img alt="Super Kame VIP" src="/images/gif/gif_supber_kame_VIP.gif">&nbsp;
                            </div>
                            
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="/?c=skill">Xem thêm thông tin kỹ năng</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hướng Dẫn Tân Thủ Section -->
                <div class="bg-content">
                    <div>
                        <div class="title">
                            <h4>Hướng Dẫn Tân Thủ</h4>
                        </div>
                        <div class="content">
                            <p><strong>1. Đăng ký tài khoản</strong></p>
                            <p>Ngọc Rồng Online sử dụng Tài Khoản riêng, không chung với bất kỳ Trò Chơi nào khác.<br>
                            Bạn có thể đăng ký tài khoản miễn phí ngay trong game, hoặc trên trang Diễn Đàn.<br>
                            Khi đăng ký, bạn nên sử dụng đúng số điện thoại hoặc email thật của mình. Nếu sử dụng thông tin sai, người có số điện thoại hoặc email thật sẽ có thể lấy mật khẩu của bạn.<br>
                            Số điện thoại và email của bạn sẽ không hiện ra cho người khác thấy. Admin không bao giờ hỏi mật khẩu của bạn.</p>

                            <p><strong>2. Hướng dẫn điều khiển</strong></p>
                            <p>Đối với máy bàn phím: Dùng phím mũi tên, phím số, để điều khiển nhân vật. Phím chọn giữa để tương tác.<br>
                            Đối với máy cảm ứng: Dùng tay chạm vào màn hình cảm ứng để di chuyển. Chạm nhanh 2 lần vào 1 đối tượng để tương tác.<br>
                            Đối với PC: Dùng chuột, click chuột phải để di chuyển, click chuột trái để chọn, click đôi vào đối tượng để tương tác</p>

                            <p><strong>3. Một số thông tin căn bản</strong></p>
                            <p>- Đậu thần dùng để tăng KI và HP ngay lập tức.<br>
                            - Bạn chỉ mang theo người được 10 hạt đậu. Nếu muốn mang nhiều hơn, hãy xin từ bạn bè trong Bang.<br>
                            - Tất cả các sách kỹ năng đều có thể học miễn phí tại Quy Lão Kame, khi bạn có đủ điểm tiềm năng.<br>
                            - Bạn không thể bay, dùng kỹ năng, nếu hết KI.<br>
                            - Tấn công quái vật cùng bạn bè trong Bang sẽ mang lại nhiều điểm tiềm năng hơn đánh một mình.<br>
                            - Tập luyện với bạn bè tại khu vực thích hợp sẽ mang lại nhiều điểm tiềm năng hơn đánh quái vật.<br>
                            - Khi được nâng cấp, đậu thần sẽ phục hồi nhiều HP và KI hơn.<br>
                            - Vào trò chơi đều đặn mỗi ngày để nhận được Ngọc miễn phí.<br>
                            - Đùi gà sẽ phục hồi 100% HP, KI. Cà chua phục hồi 100% KI. Cà rốt phục hồi 100% HP.<br>
                            - Cây đậu thần kết một hạt sau một thời gian, cho dù bạn đang offline.<br>
                            - Sau 3 ngày không tham gia trò chơi, bạn sẽ bị giảm sức mạnh do lười luyện tập.<br>
                            - Bạn sẽ giảm thể lực khi đánh quái, nhưng sẽ tăng lại thể lực khi không đánh nữa.</p>
                        </div>
                    </div>
                </div>

                <!-- Bạn nên tải phiên bản nào Section -->
                <div class="bg-content">
                    <div>
                        <div class="title">
                            <h4>Bạn nên tải phiên bản nào?</h4>
                        </div>
                        <div class="content">
                            <p>Nếu bạn dùng điện thoại Nokia cũ, có bàn phím như Nokia 6300, Nokia E72, Nokia X2, Nokia C2, Hãy tải bản JAVA</p>
                            <p>Nếu bạn dùng máy cảm ứng sử dụng Android như: Samsung Galaxy Y, HTC, LG, Sky, HKPhone. Hãy tải bản Android APK hoặc Android Playstore đều được.</p>
                            <p>Nếu bạn dùng điện thoại cảm ứng của NOKIA Lumia, hoặc các máy HTC chạy Windows Phone, hãy tải bản cho Windows Phone.</p>
                            <p>Nếu bạn dùng máy vi tính cá nhân, laptop chạy Windows XP - Windows 7, hãy tải bản PC.</p>
                            <p>Nếu bạn dùng iPhone, iPod, iPad, hãy tải bản iPhone Appstore. Nếu bạn biết chắc rằng máy mình đã jailbreak, có cài AppSync hoặc AppstoreVN, hãy cài từ bản iPhone jailbreak để tốc độ nhanh hơn.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="copyright">
            <b>Bản quyền thuộc về Chú Bé Rồng Online - 2013</b>
            <div class="code-by-lio">Developed & Optimized by <span class="lio-badge">Code by Lio</span></div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>
    <script src="/view/static/js/ThreeCanvas.js" type="text/javascript"></script>
    <script src="/view/static/js/Snow3d.js" type="text/javascript"></script>
    <script src="/view/static/js/animation.js?v4" type="text/javascript"></script>
    <script type="text/javascript">
        (function($) {
            "use strict"
            $(function() {
                if ($('#backTop').length) {
                    var scrollTrigger = 100, // px
                        backToTop = function() {
                            var scrollTop = $(window).scrollTop();
                            if (scrollTop > scrollTrigger) {
                                $('#backTop').addClass('show');
                            } else {
                                $('#backTop').removeClass('show');
                            }
                        };
                    backToTop();
                    $(window).on('scroll', function() {
                        backToTop();
                    });
                    $('#backTop').on('click', function(e) {
                        e.preventDefault();
                        $('html,body').animate({
                            scrollTop: 0
                        }, 700);
                    });
                }
            });
        })(jQuery);
    </script>
</body>
</html>
