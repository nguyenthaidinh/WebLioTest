<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../connect.php';
include_once '../forum_data.php';
include_once '../cauhinh.php';
include_once '../data_nap_the.php';

$bank_name = $_bank_name ?? $_nganhang ?? '';
$bank_account_name = $_bank_account_name ?? $_taikhoanmm ?? '';
$bank_account_number = $_bank_account_number ?? $_phonemomo ?? '';
$qr_recharge_path = $_qr_nap_tien ?? $_qrmomo ?? '/images/qr-nap-tien.png';
$qr_recharge_url = '/' . ltrim($qr_recharge_path, '/');
$qr_file_path = substr($qr_recharge_path, 0, 1) === '/'
    ? dirname(__DIR__) . $qr_recharge_path
    : dirname(__DIR__) . '/' . ltrim($qr_recharge_path, '/');
$has_qr_image = is_file($qr_file_path);
$transfer_content = 'Hãy để mặc định';

function recharge_status_label($status, $is_credited = 0) {
    $status = strtolower((string)$status);
    if ($is_credited || $status === 'success') {
        return ['Da duyet', '#16a34a'];
    }
    if ($status === 'pending') {
        return ['Cho admin duyet', '#f59e0b'];
    }
    if ($status === 'rejected') {
        return ['Da tu choi', '#dc2626'];
    }
    if ($status === 'failed') {
        return ['That bai', '#dc2626'];
    }
    return ['Dang xu ly', '#64748b'];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp tiền - Chú Bé Rồng Online</title>
    <meta name="keywords" content="Chú Bé Rồng Online, ngọc rồng, nạp tiền, nạp vàng">
    <meta name="description" content="Nạp tiền thủ công, chờ admin duyệt cho Chú Bé Rồng Online.">
    <meta http-equiv="refresh" content="600">
    <link rel="apple-touch-icon" href="/images/favicon-48x48.ico">
    <link rel="icon" href="/images/favicon-48x48.ico" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
    <link rel="stylesheet" href="/view/static/css/eff.css?v=1.00">
    <link rel="stylesheet" href="/view/static/css/w3.css?v=1.01">
    <link rel="stylesheet" href="/view/static/css/styleSheet.css?v=1.1">
    <link rel="stylesheet" href="/view/static/css/forum.css?v=1.1">
    <script src="/view/static/js/disable_devtools.js"></script>
    <style>
        .manual-recharge-wrap {
            width: min(100%, 760px);
            margin: 0 auto;
            color: #2d1600;
        }
        .recharge-panel {
            background: #fff8ec;
            border: 1px solid #f6b35d;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .recharge-grid {
            display: grid;
            grid-template-columns: minmax(180px, 260px) 1fr;
            gap: 14px;
            align-items: start;
        }
        .qr-box {
            background: #fff;
            border: 1px solid #f0c27b;
            border-radius: 8px;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .qr-box img {
            width: 100%;
            max-width: 240px;
            height: auto;
            display: block;
        }
        .qr-placeholder {
            padding: 18px;
            text-align: center;
            color: #8a4b00;
            font-weight: 700;
            line-height: 1.5;
        }
        .bank-line {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 8px;
            align-items: center;
            padding: 7px 0;
            border-bottom: 1px dashed #f0c27b;
            font-size: 13px;
        }
        .bank-line:last-child {
            border-bottom: 0;
        }
        .bank-label {
            font-weight: 800;
            color: #7c2d12;
        }
        .bank-value {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #111827;
            font-weight: 700;
        }
        .copy-btn,
        .submit-recharge-btn {
            border: 0;
            border-radius: 6px;
            background: #f97316;
            color: #fff;
            font-weight: 800;
            padding: 6px 10px;
            cursor: pointer;
        }
        .copy-btn {
            font-size: 11px;
            white-space: nowrap;
        }
        .manual-form {
            display: grid;
            gap: 10px;
        }
        .manual-form label {
            display: block;
            font-weight: 800;
            color: #7c2d12;
            margin-bottom: 4px;
        }
        .manual-form input {
            width: 100%;
            border: 1px solid #f0c27b;
            border-radius: 6px;
            padding: 9px;
            box-sizing: border-box;
            color: #111827;
            background: #fff;
        }
        .submit-recharge-btn {
            width: 100%;
            padding: 10px;
            font-size: 14px;
        }
        .recharge-note {
            color: #7c2d12;
            font-size: 12px;
            line-height: 1.5;
            margin: 8px 0 0;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            font-size: 12px;
        }
        .history-table th {
            background: #b65d2f;
            color: #fff;
            padding: 9px 6px;
            text-align: center;
        }
        .history-table td {
            color: #111827;
            border-bottom: 1px solid #f0dfc2;
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .status-pill {
            display: inline-block;
            color: #fff;
            border-radius: 999px;
            padding: 3px 8px;
            font-weight: 800;
            font-size: 11px;
            white-space: nowrap;
        }
        .pagination2 {
            text-align: center;
            padding: 12px 0 0;
        }
        .pagination2 a {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            border: 1px solid #f97316;
            border-radius: 5px;
            color: #f97316;
            text-decoration: none;
            font-weight: 800;
            background: #fff;
        }
        .pagination2 a.active {
            color: #fff;
            background: #f97316;
        }
        @media (max-width: 640px) {
            .recharge-grid,
            .bank-line {
                grid-template-columns: 1fr;
            }
            .copy-btn {
                width: 100%;
            }
            .history-table {
                font-size: 11px;
            }
            .history-table th,
            .history-table td {
                padding: 7px 4px;
            }
        }
    </style>
</head>

<body>
    <div class="snowEffect">
        <canvas id="snowcanvas" height="100%" width="100%"></canvas>
    </div>

    <div style="position: relative;" class="body_body">
        <a href="#" id="backTop"><img id="backTopimg" src="/images/favicon-32x32.png" alt="top"></a>

        <div class="div-12">
            <img height="12" src="/images/12.png" style="vertical-align: middle;" alt="12+">
            <span style="vertical-align: middle;">Dành cho người chơi trên 12 tuổi. Chơi quá 180 phút mỗi ngày sẽ hại sức khỏe.</span>
        </div>
        <div class="left_top"></div>
        <div class="bg_top"><div class="right_top"></div></div>
        <div class="body-content">
            <div class="bg-content2">
                <h1 class="a">
                    <a href="/" title="game bảy viên Chú Bé Rồng Online">
                        <img height="90" src="/images/logo_sk_he.png" alt="Chú Bé Rồng Online">
                    </a>
                </h1>
                <div id="top">
                    <div class="link-more">
                        <div class="h">
                            <div class="bg_noel"></div>
                            <div class="h">
                                <div class="menu2">
                                    <table width="100%" cellspacing="4">
                                        <tr class="menu">
                                            <td><a href="/trang-chu.php">Trang Chủ</a></td>
                                            <td><a href="/gioi-thieu.php">Giới Thiệu</a></td>
                                            <td><a href="/forum.php" title="Diễn Đàn">Diễn Đàn</a></td>
                                            <td><a href="https://www.facebook.com/share/19xSSth2PY/">Fanpage</a></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="body">
                                <div class="box_inputboxx" style="width:100%">
                                    <?php if ($is_logged_in) : ?>
                                        <div id="user-info" style="color:white; text-align:center; padding: 10px; background-color: #f38500; border-radius: 8px;">
                                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="user-avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 10px;">
                                            <div class="user-details"><br>
                                                <span style="font-weight: bold;">Xin chào: <?php echo htmlspecialchars($display_player_name); ?></span><br>
                                                <span style="white-space: nowrap; color: yellow; font-weight: bold;">Số dư: <?php echo number_format($user_vnd, 0, ',', '.'); ?> VND</span><br>
                                                <a href="/app/doi-vang.php" style="color: cyan;">Đổi Thỏi Vàng</a><br>
                                                <a href="/app/doi-mat-khau.php" style="color: cyan;">Đổi mật khẩu</a><br>
                                                <a href="/app/logout.php" style="color: cyan;">Đăng xuất</a><br>
                                                <a href="/app/vong-quay.php" style="color: cyan;">Vong Quay May Man</a><br>
                                                <?php if (!empty($is_admin_for_avatar)): ?>
                                                    <a href="/admin/" style="color: cyan;">Admin</a><br>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="box_button_login" style="width:100%; position: relative; text-align:center;">
                                            <a id="tab-login-btn" href="/app/login.php"><button class="w3-button w3-red w3-small w3-hover-green">Đăng nhập</button></a>
                                            <a id="tab-register-btn" href="/app/register.php"><button class="w3-button w3-blue w3-small w3-hover-green">Đăng ký</button></a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <br>

                                <div class="body">
                                    <table width="100%" border="0" cellspacing="0">
                                        <tbody>
                                            <tr class="menu1">
                                                <td id="recharge-selected-tab" style="width:100%; background-color: #ff5601;">Nạp Tiền Thủ Công</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="manual-recharge-wrap">
                                        <?php if (!$is_logged_in) : ?>
                                            <div class="recharge-panel" style="text-align:center; font-weight:800;">
                                                Bạn cần đăng nhập để gửi yêu cầu nạp tiền.
                                            </div>
                                        <?php else : ?>
                                            <div class="recharge-panel">
                                                <div class="recharge-grid">
                                                    <div class="qr-box">
                                                        <?php if ($has_qr_image) : ?>
                                                            <img src="<?php echo htmlspecialchars($qr_recharge_url); ?>" alt="QR nạp tiền">
                                                        <?php else : ?>
                                                            <div class="qr-placeholder">
                                                                Chưa có mã QR.<br>
                                                                Upload ảnh vào:<br>
                                                                <?php echo htmlspecialchars($qr_recharge_url); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="bank-line">
                                                            <span class="bank-label">Ngân hàng</span>
                                                            <span class="bank-value" id="bankName"><?php echo htmlspecialchars($bank_name); ?></span>
                                                            <button class="copy-btn" type="button" data-copy="<?php echo htmlspecialchars($bank_name); ?>">Copy</button>
                                                        </div>
                                                        <div class="bank-line">
                                                            <span class="bank-label">Số tài khoản</span>
                                                            <span class="bank-value" id="bankNumber"><?php echo htmlspecialchars($bank_account_number); ?></span>
                                                            <button class="copy-btn" type="button" data-copy="<?php echo htmlspecialchars($bank_account_number); ?>">Copy</button>
                                                        </div>
                                                        <div class="bank-line">
                                                            <span class="bank-label">Chủ tài khoản</span>
                                                            <span class="bank-value" id="bankOwner"><?php echo htmlspecialchars($bank_account_name); ?></span>
                                                            <button class="copy-btn" type="button" data-copy="<?php echo htmlspecialchars($bank_account_name); ?>">Copy</button>
                                                        </div>
                                                        <div class="bank-line">
                                                            <span class="bank-label">Nội dung</span>
                                                            <span class="bank-value" id="transferContent"><?php echo htmlspecialchars($transfer_content); ?></span>
                                                            <button class="copy-btn" type="button" data-copy="<?php echo htmlspecialchars($transfer_content); ?>">Copy</button>
                                                        </div>
                                                        <p class="recharge-note">Moi 10.000 VND nap thanh cong se duoc cong 1 luot quay may man.</p>
                                                        <p class="recharge-note">Sau khi chuyển khoản, nhập số tiền và mã giao dịch/nội dung chuyển khoản để admin kiểm tra rồi duyệt VND.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="recharge-panel">
                                                <form id="manualRechargeForm" class="manual-form" method="post">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <div>
                                                        <label for="amount">Số tiền đã chuyển</label>
                                                        <input id="amount" name="amount" type="number" min="10000" step="1000" placeholder="Ví dụ: 50000" required>
                                                    </div>
                                                    <div>
                                                        <label for="transfer_note">Mã giao dịch hoặc ghi chú</label>
                                                        <input id="transfer_note" name="transfer_note" type="text" maxlength="255" placeholder="Nhập mã giao dịch hoặc nội dung đã chuyển khoản">
                                                    </div>
                                                    <button class="submit-recharge-btn" type="submit">Hoàn tất</button>
                                                    <div id="manualRechargeStatus" class="recharge-note"></div>
                                                </form>
                                            </div>
                                        <?php endif; ?>

                                        <div class="recharge-panel">
                                            <h3 style="margin: 0 0 10px; color:#7c2d12; text-align:center;">Lịch Sử Nạp Tiền</h3>
                                            <table class="history-table">
                                                <thead>
                                                    <tr>
                                                        <th>Thời gian</th>
                                                        <th>Số tiền</th>
                                                        <th>Trạng thái</th>
                                                        <th>Ghi chú</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($history_bank_transfers)) : ?>
                                                        <?php foreach ($history_bank_transfers as $transfer) : ?>
                                                            <?php [$status_text, $status_color] = recharge_status_label($transfer['status'] ?? '', $transfer['is_credited'] ?? 0); ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($transfer['created_at']); ?></td>
                                                                <td><?php echo number_format((int)$transfer['amount'], 0, ',', '.'); ?> VND</td>
                                                                <td><span class="status-pill" style="background: <?php echo htmlspecialchars($status_color); ?>;"><?php echo htmlspecialchars($status_text); ?></span></td>
                                                                <td><?php echo htmlspecialchars($transfer['description'] ?? ''); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="4">Chưa có yêu cầu nạp tiền.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                            <?php if ($total_pages_transfer > 1) : ?>
                                                <div class="pagination2">
                                                    <?php for ($i = 1; $i <= $total_pages_transfer; $i++) : ?>
                                                        <a class="<?php echo $i === $current_page_transfer ? 'active' : ''; ?>" href="?page_transfer=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="bg_tree"></div>
                    <div class="foot_bg"></div>
                    <div class="left_b_bottom">
                        <div class="right_b_bottom">
                            <div class="footer"><div class="left_bottom"></div><div class="right_bottom"></div></div>
                            <div class="copyright" style="line-height: 7px">
                                <p>Giấy phép thiết lập Mạng Xã Hội trên mạng số: 374/GP-BTTTT <br><br>do Bộ Thông Tin và Truyền Thông cấp ngày: 07/08/2015</p>
                                Bản Quyền thuộc về Gomobi
                            </div>
                        </div>
                    </div>
                    <script src="/view/static/js/ThreeCanvas.js" type="text/javascript"></script>
                    <script src="/view/static/js/Snow3d.js" type="text/javascript"></script>
                    <script src="/view/static/js/animation.js?v5" type="text/javascript"></script>
                    <script>
                        $(document).ready(function() {
                            $('.copy-btn').on('click', function() {
                                var text = $(this).data('copy') || '';
                                if (!text) {
                                    toastr.warning('Không có dữ liệu để copy.');
                                    return;
                                }
                                navigator.clipboard.writeText(text).then(function() {
                                    toastr.success('Đã copy: ' + text);
                                }).catch(function() {
                                    var tempInput = document.createElement('input');
                                    tempInput.value = text;
                                    document.body.appendChild(tempInput);
                                    tempInput.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(tempInput);
                                    toastr.success('Đã copy: ' + text);
                                });
                            });

                            var lastSubmitTime = 0;
                            $('#manualRechargeForm').on('submit', function(event) {
                                event.preventDefault();

                                var now = Date.now();
                                if (now - lastSubmitTime < 3000) {
                                    toastr.warning('Vui lòng chờ vài giây trước khi gửi tiếp.');
                                    return;
                                }

                                var form = $(this);
                                var button = form.find('button[type="submit"]');
                                var statusBox = $('#manualRechargeStatus');

                                statusBox.css('color', '#7c2d12').text('Đang gửi yêu cầu...');
                                button.prop('disabled', true).text('Đang gửi...');

                                $.ajax({
                                    url: '/api/manual_recharge.php',
                                    type: 'POST',
                                    data: form.serialize(),
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.success) {
                                            lastSubmitTime = Date.now();
                                            toastr.success(response.message);
                                            statusBox.css('color', '#16a34a').text(response.message);
                                            setTimeout(function() {
                                                window.location.reload();
                                            }, 1200);
                                        } else {
                                            toastr.error(response.message || 'Không thể gửi yêu cầu.');
                                            statusBox.css('color', '#dc2626').text(response.message || 'Không thể gửi yêu cầu.');
                                        }
                                    },
                                    error: function() {
                                        var message = 'Có lỗi xảy ra, vui lòng thử lại sau.';
                                        toastr.error(message);
                                        statusBox.css('color', '#dc2626').text(message);
                                    },
                                    complete: function() {
                                        button.prop('disabled', false).text('Hoàn tất');
                                    }
                                });
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
