<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Chào mừng bạn đến với Chú Bé Rồng Online - Đăng nhập, Đăng ký</title>
	<link rel="stylesheet" href="https://forum.ngocrongonline.com/app/view/css/StyleSheet.css" type="text/css" />
	<link rel="stylesheet" href="https://forum.ngocrongonline.com/app/view/css/template.css" type="text/css" />
	<script src="/view/static/js/disable_devtools.js"></script>
	<link rel="shortcut icon" href='https://forum.ngocrongonline.com/app/view/images/favicon.png' type="image/x-icon" />
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-22738816-4']);
		_gaq.push(['_setDomainName', '.teamobi.com']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script');
			ga.type = 'text/javascript';
			ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0];
			s.parentNode.insertBefore(ga, s);
		})();
	</script>
	<link rel="stylesheet" href="https://forum.ngocrongonline.com/app/view/css/w3.css">
</head>
<style>
		@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

		body {
			background: radial-gradient(circle at center, #351404 0%, #0a0300 100%) !important;
			font-family: 'Outfit', sans-serif !important;
			color: #f3f4f6 !important;
			margin: 0;
			padding: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.snowEffect {
			position: fixed;
			width: 100%;
			height: 100%;
			left: 0;
			top: 0;
			z-index: 1;
			overflow: hidden;
			pointer-events: none;
		}

		#snowcanvas {
			position: fixed;
			z-index: 0;
		}

		.body_body {
			width: 100%;
			max-width: 440px !important;
			padding: 20px;
			box-sizing: border-box;
			position: relative;
			z-index: 10;
		}

		/* Hide old TeaMobi sprites */
		.left_top, .bg_top, .right_top, .left_b_bottom, .right_b_bottom, .footer, .left_bottom, .right_bottom, .bg_tree, .bg_noel {
			display: none !important;
		}

		.body-content {
			background: rgba(22, 10, 5, 0.85) !important;
			backdrop-filter: blur(16px);
			border: 1.5px solid rgba(249, 115, 22, 0.35) !important;
			border-radius: 20px !important;
			padding: 30px 25px !important;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.75), 0 0 30px rgba(249, 115, 22, 0.18) !important;
			margin: 0 !important;
			width: 100% !important;
			box-sizing: border-box;
		}

		.a {
			margin-top: 0 !important;
			margin-bottom: 25px !important;
			height: auto !important;
			text-align: center;
		}
		.a img {
			margin-top: 0 !important;
			filter: drop-shadow(0 4px 12px rgba(249, 115, 22, 0.5));
			transition: transform 0.3s ease;
			height: 80px;
		}
		.a img:hover {
			transform: scale(1.06);
		}

		.menu2 {
			background: rgba(26, 12, 6, 0.7) !important;
			border-radius: 12px !important;
			padding: 5px !important;
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
			width: 50% !important;
			padding: 0 !important;
		}

		.menu2 td a {
			display: block;
			padding: 10px;
			color: #b3b3b3 !important;
			font-weight: 600 !important;
			text-decoration: none !important;
			border-radius: 8px !important;
			transition: all 0.3s ease !important;
			font-size: 14px !important;
			text-align: center;
		}

		.menu2 td#selected a, .menu2 td a:hover {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
			color: #fff !important;
			box-shadow: 0 4px 15px rgba(249, 115, 22, 0.45) !important;
		}

		.body {
			background: transparent !important;
		}

		.body-subtitle {
			font-size: 13px !important;
			color: #9ca3af !important;
			margin-bottom: 20px !important;
			text-align: center;
		}

		/* Form Styling */
		.form-group {
			text-align: left;
			margin-bottom: 18px;
		}

		.form-group label {
			display: block;
			font-size: 12px;
			font-weight: 600;
			color: #febb12;
			margin-bottom: 6px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.form-group input[type="text"], 
		.form-group input[type="password"] {
			width: 100% !important;
			box-sizing: border-box;
			background: rgba(0, 0, 0, 0.4) !important;
			border: 1px solid rgba(249, 115, 22, 0.35) !important;
			color: #fff !important;
			padding: 12px 16px !important;
			font-size: 14px !important;
			border-radius: 8px !important;
			outline: none !important;
			transition: all 0.3s ease !important;
		}

		.form-group input[type="text"]:focus, 
		.form-group input[type="password"]:focus {
			border-color: #f97316 !important;
			box-shadow: 0 0 10px rgba(249, 115, 22, 0.4) !important;
			background: rgba(0, 0, 0, 0.6) !important;
		}

		/* Server select styling */
		.server-container {
			background: rgba(0, 0, 0, 0.35);
			border: 1px solid rgba(249, 115, 22, 0.2);
			border-radius: 10px;
			padding: 12px;
			margin: 20px 0;
			display: flex;
			justify-content: center;
			align-items: center;
		}

		.server-label {
			display: flex;
			align-items: center;
			gap: 8px;
			font-weight: 600;
			color: #febb12;
			cursor: pointer;
			font-size: 14px;
		}

		.server-label input[type="radio"] {
			accent-color: #f97316;
			width: 18px;
			height: 18px;
			margin: 0;
			cursor: pointer;
		}

		/* Submit Button */
		button[type="submit"] {
			width: 100% !important;
			background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important;
			color: #fff !important;
			border: none !important;
			padding: 13px !important;
			font-size: 15px !important;
			font-weight: 700 !important;
			border-radius: 8px !important;
			cursor: pointer !important;
			transition: all 0.3s ease !important;
			box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35) !important;
			text-transform: uppercase !important;
			letter-spacing: 0.5px !important;
		}

		button[type="submit"]:hover {
			transform: translateY(-1.5px) !important;
			box-shadow: 0 6px 20px rgba(249, 115, 22, 0.5) !important;
		}

		button[type="submit"]:active {
			transform: translateY(1px) !important;
		}

		/* Message Styling */
		.message {
			padding: 12px !important;
			border-radius: 8px !important;
			font-size: 13px !important;
			margin: 15px 0 !important;
			text-align: center !important;
			font-weight: 600 !important;
		}
		.message.success {
			background-color: rgba(46, 125, 50, 0.2) !important;
			color: #81c784 !important;
			border: 1px solid rgba(76, 175, 80, 0.3) !important;
		}
		.message.error {
			background-color: rgba(198, 40, 40, 0.2) !important;
			color: #e57373 !important;
			border: 1px solid rgba(244, 67, 54, 0.3) !important;
		}

		.register-link-container {
			margin-top: 20px !important;
			font-size: 13px !important;
			color: #9ca3af !important;
			text-align: center;
		}

		.register-link-container a {
			color: #febb12 !important;
			font-weight: 600 !important;
			text-decoration: none !important;
			border-bottom: 1px dashed rgba(254, 187, 18, 0.5);
			transition: all 0.3s ease;
			padding-bottom: 2px;
		}

		.register-link-container a:hover {
			color: #f97316 !important;
			border-bottom-color: #f97316;
		}

		/* Footer & Copyright */
		.copyright {
			margin-top: 30px !important;
			text-align: center !important;
			color: #8b8880 !important;
			font-size: 12px !important;
			line-height: 1.5;
		}

		.copyright b {
			font-weight: 400 !important;
		}

		.code-by-lio {
			margin-top: 15px;
			font-size: 11px;
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
	</style>
<body>
<div class="snowEffect">
	<canvas id="snowcanvas" height="100%" width="100%"></canvas>
</div>
<div class="body_body">
	<div class="body-content">
		<div class="a"><img src="/images/logo_sk_he.png" alt="Chú Bé Rồng Online" /></div>
		<div id="top">
			<div class="link-more">
				<div class="h">
					<div class="menu2">
						<table width="100%" border="0" cellspacing="4">
							<tr class="menu">
								<td><a href="/">Trang Chủ</a></td>
								<td id="selected"><a href="/forum.php">Diễn Đàn</a></td>
							</tr>
						</table>
					</div>
					<div class="body">
						<div class="body-subtitle">Sử dụng tài khoản Chú Bé Rồng Online để đăng nhập.</div>
						<form id="loginForm" method="POST" name="login">
							<input type="hidden" name="action" value="login" />
							<input type="hidden" name="keySig" value="a511129a7ce15460414e6fe318eebc2b" />
							<input type="hidden" name="nav" value="" readonly="readonly" />
							
							<div class="form-group">
								<label for="user">Tài Khoản</label>
								<input name="user" type="text" placeholder="Nhập tài khoản..." required />
							</div>
							
							<div class="form-group">
								<label for="pass">Mật khẩu</label>
								<input name="pass" type="password" placeholder="Nhập mật khẩu..." required />
								<input type="hidden" name="checkru" value="d3540b1767470e0a87215174bd0ed85d" />
							</div>

							<div class="server-container">
								<label class="server-label">
									<input type="radio" name="server" value="1" checked required />
									<span>Server 1 sao</span>
								</label>
							</div>

							<div id="loginMessage" class="message" style="display:none;"></div>
							
							<button type="submit" id="button1" name="submit">Đăng nhập</button>
							
							<div class="register-link-container">
								Chưa có tài khoản? <a href="register">Đăng Ký ngay</a>
							</div>
						</form>
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
<script type="text/javascript">
$(document).ready(function() {
    $('#loginForm').submit(function(e) {
        e.preventDefault();

        var form = $(this);
        var url = 'auth_process.php';

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            dataType: "json",
            success: function(response) {
                var messageDiv = $('#loginMessage');
                messageDiv.css('display', 'block');
                messageDiv.removeClass('success error');

                if (response.status === 'success') {
                    messageDiv.addClass('success');
                    messageDiv.text(response.message);
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                } else {
                    messageDiv.addClass('error');
                    messageDiv.text(response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
                var messageDiv = $('#loginMessage');
                messageDiv.css('display', 'block');
                messageDiv.addClass('error');
                messageDiv.text('Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.');
            }
        });
    });
});
</script>

<script src="https://ngocrongonline.com/view/static/js/ThreeCanvas.js" type="text/javascript"></script>
<script src="https://ngocrongonline.com/view/static/js/Snow3d.js" type="text/javascript"></script>
<script src="https://ngocrongonline.com/view/static/js/animation.js?v4" type="text/javascript"></script>
<script src="/cdn-cgi/scripts/7d0fa10a/cloudflare-static/rocket-loader.min.js" data-cf-settings="57d9dd0f2fd997a3f6dac1b9-|49" defer></script></body>
</html>