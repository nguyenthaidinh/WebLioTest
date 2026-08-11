<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../forum_data.php';
include_once __DIR__ . '/account_info.php';

const GOLD_ITEM_ID = 457;
const GOLD_EXCHANGE_RATE = 3;
const RECHARGE_THANKS_GIFTCODE = 'camonquykhach';

$message = $_SESSION['gold_exchange_message'] ?? '';
$message_type = $_SESSION['gold_exchange_message_type'] ?? '';
unset($_SESSION['gold_exchange_message'], $_SESSION['gold_exchange_message_type']);

if (empty($_SESSION['gold_exchange_csrf_token'])) {
    $_SESSION['gold_exchange_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['gold_exchange_csrf_token'];

function set_gold_exchange_message($message, $type = 'error') {
    $_SESSION['gold_exchange_message'] = $message;
    $_SESSION['gold_exchange_message_type'] = $type;
}

function decode_items_bag_for_exchange($raw_items_bag) {
    $outer_slots = json_decode($raw_items_bag ?: '[]', true);
    if (!is_array($outer_slots)) {
        return [];
    }

    $items = [];
    foreach ($outer_slots as $slot) {
        if (is_string($slot)) {
            $item = json_decode($slot, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($item) && count($item) >= 4) {
                $items[] = $item;
            } else {
                $items[] = [-1, 0, '[]', 0];
            }
            continue;
        }

        if (is_array($slot) && count($slot) >= 4) {
            $items[] = $slot;
        } else {
            $items[] = [-1, 0, '[]', 0];
        }
    }

    return $items;
}

function encode_items_bag_for_exchange($items) {
    $encoded_slots = [];
    foreach ($items as $item) {
        $encoded_slots[] = json_encode($item, JSON_UNESCAPED_UNICODE);
    }

    return json_encode($encoded_slots, JSON_UNESCAPED_UNICODE);
}

function add_gold_item_to_bag($items, $amount) {
    $empty_slot_index = -1;

    foreach ($items as $index => &$item) {
        if (is_array($item) && isset($item[0]) && (int)$item[0] === GOLD_ITEM_ID) {
            $item[1] = (int)($item[1] ?? 0) + $amount;
            unset($item);
            return $items;
        }

        if (
            $empty_slot_index === -1
            && is_array($item)
            && isset($item[0], $item[1], $item[2])
            && (int)$item[0] === -1
            && (int)$item[1] === 0
            && $item[2] === '[]'
        ) {
            $empty_slot_index = $index;
        }
    }
    unset($item);

    $new_gold_item = [
        GOLD_ITEM_ID,
        $amount,
        json_encode([[73, 0]], JSON_UNESCAPED_UNICODE),
        round(microtime(true) * 1000)
    ];

    if ($empty_slot_index !== -1) {
        $items[$empty_slot_index] = $new_gold_item;
    } else {
        $items[] = $new_gold_item;
    }

    return $items;
}

function handle_gold_exchange_request($conn, $account_id, $player_id) {
    $amount = (int)($_POST['gold_amount'] ?? 0);

    if ($amount <= 0) {
        throw new Exception('Số thỏi vàng cần đổi phải lớn hơn 0.');
    }

    if ($amount > 1000000) {
        throw new Exception('Số thỏi vàng mỗi lần đổi quá lớn.');
    }

    if ($account_id === null || $account_id <= 0) {
        throw new Exception('Không tìm thấy tài khoản.');
    }

    if ($player_id === null || $player_id <= 0) {
        throw new Exception('Bạn chưa có nhân vật trong game.');
    }

    $cost = $amount * GOLD_EXCHANGE_RATE;
    $conn->begin_transaction();

    try {
        $stmt_account = $conn->prepare("SELECT vnd FROM account WHERE id = ? FOR UPDATE");
        if (!$stmt_account) {
            throw new Exception('Lỗi kiểm tra tài khoản: ' . $conn->error);
        }
        $stmt_account->bind_param("i", $account_id);
        $stmt_account->execute();
        $account_result = $stmt_account->get_result();
        $account = $account_result->fetch_assoc();
        $stmt_account->close();

        if (!$account) {
            throw new Exception('Tài khoản không tồn tại.');
        }

        $current_balance = (int)$account['vnd'];
        if ($current_balance < $cost) {
            throw new Exception('Số dư VND không đủ. Bạn cần ' . number_format($cost, 0, ',', '.') . ' VND.');
        }

        $stmt_player = $conn->prepare("SELECT items_bag FROM player WHERE id = ? AND account_id = ? FOR UPDATE");
        if (!$stmt_player) {
            throw new Exception('Lỗi kiểm tra nhân vật: ' . $conn->error);
        }
        $stmt_player->bind_param("ii", $player_id, $account_id);
        $stmt_player->execute();
        $player_result = $stmt_player->get_result();
        $player = $player_result->fetch_assoc();
        $stmt_player->close();

        if (!$player) {
            throw new Exception('Nhân vật không tồn tại hoặc không thuộc tài khoản này.');
        }

        $items = decode_items_bag_for_exchange($player['items_bag'] ?? '[]');
        $items = add_gold_item_to_bag($items, $amount);
        $new_items_bag = encode_items_bag_for_exchange($items);

        if ($new_items_bag === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Không thể mã hóa túi đồ.');
        }

        $stmt_update_account = $conn->prepare("UPDATE account SET vnd = vnd - ? WHERE id = ?");
        if (!$stmt_update_account) {
            throw new Exception('Lỗi trừ VND: ' . $conn->error);
        }
        $stmt_update_account->bind_param("ii", $cost, $account_id);
        if (!$stmt_update_account->execute() || $stmt_update_account->affected_rows === 0) {
            $stmt_update_account->close();
            throw new Exception('Không thể trừ VND khỏi tài khoản.');
        }
        $stmt_update_account->close();

        $stmt_update_bag = $conn->prepare("UPDATE player SET items_bag = ? WHERE id = ? AND account_id = ?");
        if (!$stmt_update_bag) {
            throw new Exception('Lỗi cập nhật túi đồ: ' . $conn->error);
        }
        $stmt_update_bag->bind_param("sii", $new_items_bag, $player_id, $account_id);
        if (!$stmt_update_bag->execute()) {
            $stmt_update_bag->close();
            throw new Exception('Không thể cập nhật túi đồ.');
        }
        $stmt_update_bag->close();

        $conn->commit();
        $_SESSION['vnd'] = $current_balance - $cost;
        return 'Đổi thành công ' . number_format($amount, 0, ',', '.') . ' thỏi vàng.';
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'exchange_gold') {
    try {
        $posted_token = $_POST['csrf_token'] ?? '';
        if (!is_string($posted_token) || !hash_equals($csrf_token, $posted_token)) {
            throw new Exception('Phiên giao dịch không hợp lệ, vui lòng tải lại trang.');
        }

        $success_message = handle_gold_exchange_request($conn, $account_id, $current_player_id);
        set_gold_exchange_message($success_message, 'success');
    } catch (Exception $e) {
        set_gold_exchange_message($e->getMessage(), 'error');
    }

    header("Location: /app/doi-vang.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi Thỏi Vàng - Chú Bé Rồng Online</title>
    <link rel="icon" href="/images/favicon-48x48.ico" type="image/x-icon">
    <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
    <link rel="stylesheet" href="/view/static/css/w3.css?v=1.01">
    <link rel="stylesheet" href="/view/static/css/styleSheet.css?v=1.1">
    <script src="/view/static/js/disable_devtools.js"></script>
    <style>
        .exchange-wrap {
            max-width: 560px;
            margin: 0 auto;
            color: #2d1600;
        }

        .exchange-panel {
            background: #fffaf0;
            border: 1px solid #efc067;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(124, 45, 18, 0.12);
            margin: 10px;
            padding: 16px;
            text-align: left;
        }

        .exchange-heading {
            border-bottom: 1px solid #f2d4a0;
            margin-bottom: 14px;
            padding-bottom: 12px;
        }

        .exchange-panel h2 {
            color: #7c2d12;
            font-size: 20px;
            margin: 0 0 4px;
        }

        .exchange-intro {
            color: #6b3a00;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.45;
            margin: 0;
        }

        .balance-line {
            align-items: center;
            background: #fff3d6;
            border: 1px solid #f2c166;
            border-radius: 8px;
            color: #7c2d12;
            display: flex;
            font-weight: 800;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px 12px;
        }

        .balance-line span {
            color: #b91c1c;
            font-size: 16px;
        }

        .rate-box {
            background: #fff;
            border: 1px solid #f3d08a;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 13px 14px;
        }

        .rate-label {
            color: #8a4b00;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .rate-value {
            align-items: baseline;
            color: #4a2a00;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            line-height: 1.2;
        }

        .rate-value strong {
            color: #b45309;
            font-size: 27px;
            font-weight: 900;
        }

        .rate-example {
            color: #6b3a00;
            font-size: 12px;
            font-weight: 700;
            margin-top: 7px;
        }

        .sync-note {
            background: #eef8f1;
            border: 1px solid #a7d9b8;
            border-radius: 8px;
            color: #166534;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.45;
            margin: 0 0 12px;
            padding: 10px 12px;
        }

        .giftcode-notice {
            background: #fff6df;
            border: 1px solid #f0c36b;
            border-radius: 8px;
            color: #713f12;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.5;
            margin: 0 0 12px;
            padding: 10px 12px;
        }

        .giftcode-notice .giftcode-value {
            background: #7c2d12;
            border-radius: 6px;
            color: #fff7ed;
            display: inline-block;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0;
            margin: 2px 0;
            padding: 2px 8px;
        }

        .exchange-form {
            display: grid;
            gap: 10px;
        }

        .exchange-form label {
            color: #7c2d12;
            font-weight: 900;
        }

        .exchange-form input[type="number"] {
            background: #fff;
            border: 1px solid #e7b869;
            border-radius: 8px;
            box-sizing: border-box;
            color: #111827;
            font-size: 15px;
            padding: 10px 11px;
            width: 100%;
        }

        .exchange-form input[type="number"]:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.18);
            outline: none;
        }

        .exchange-submit {
            background: #d97706;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-weight: 900;
            padding: 11px 12px;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .exchange-submit:hover {
            background: #b45309;
            transform: translateY(-1px);
        }

        .message {
            border-radius: 8px;
            font-weight: 800;
            line-height: 1.45;
            margin-bottom: 12px;
            padding: 10px 12px;
        }

        .message.success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            font-size: 12px;
            gap: 12px;
            justify-content: center;
            margin-top: 12px;
        }

        .quick-links a {
            color: #9a3412;
            font-weight: 900;
            text-decoration: none;
        }

        .quick-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .exchange-panel {
                margin: 8px;
                padding: 13px;
            }

            .balance-line {
                align-items: flex-start;
                flex-direction: column;
                gap: 2px;
            }

            .rate-value strong {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="body_body">
        <div class="left_top"></div>
        <div class="bg_top"><div class="right_top"></div></div>
        <div class="body-content">
            <div class="a" align="center"><img src="/images/logo_sk_he.png" height="90" alt="Logo"></div>
            <div id="top">
                <div class="link-more">
                    <div class="h" align="center">
                        <div class="menu2" style="background: #561d00;">
                            <table width="100%" border="0" cellspacing="4">
                                <tr class="menu">
                                    <td><a href="/">Trang Chủ</a></td>
                                    <td><a href="/forum.php">Diễn Đàn</a></td>
                                </tr>
                            </table>
                        </div>

                        <div class="body">
                            <div class="exchange-wrap">
                                <div class="exchange-panel">
                                    <div class="exchange-heading">
                                        <h2>Đổi thỏi vàng</h2>
                                        <p class="exchange-intro">Tỉ lệ mới đã được cập nhật. Nhập số lượng thỏi vàng muốn nhận, hệ thống sẽ tự trừ VND theo tỉ lệ bên dưới.</p>
                                    </div>

                                    <?php if (!$is_logged_in): ?>
                                        <div class="message error">Bạn cần đăng nhập để đổi thỏi vàng.</div>
                                        <div class="quick-links"><a href="/app/login.php">Đăng nhập</a></div>
                                    <?php else: ?>
                                        <?php if ($message !== ''): ?>
                                            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                                                <?php echo htmlspecialchars($message); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="balance-line">
                                            <strong>Số dư tài khoản</strong>
                                            <span><?php echo number_format((int)$user_balance, 0, ',', '.'); ?> VND</span>
                                        </div>
                                        <div class="rate-box">
                                            <div class="rate-label">Tỉ lệ đổi hiện tại</div>
                                            <div class="rate-value">
                                                <strong><?php echo number_format(GOLD_EXCHANGE_RATE, 0, ',', '.'); ?> VND</strong>
                                                <span>= 1 thỏi vàng</span>
                                            </div>
                                            <div class="rate-example">
                                                Ví dụ: đổi 10 thỏi vàng cần <?php echo number_format(10 * GOLD_EXCHANGE_RATE, 0, ',', '.'); ?> VND.
                                            </div>
                                        </div>
                                        <div class="sync-note">
                                            Vui lòng thoát game trước khi đổi để túi đồ đồng bộ chính xác.
                                        </div>
                                        <?php if ((int)$user_total_recharge > 0): ?>
                                            <div class="giftcode-notice">
                                                Giftcode tri ân: <span class="giftcode-value"><?php echo htmlspecialchars(RECHARGE_THANKS_GIFTCODE); ?></span><br>
                                                Lượt nhập toàn server chỉ 5 lần. Vui lòng chỉ nhập 1 lần cho tài khoản đã nạp tích lũy. Mọi trường hợp gian lận sẽ bị xử lý.
                                            </div>
                                        <?php endif; ?>

                                        <form class="exchange-form" method="post" action="/app/doi-vang.php">
                                            <input type="hidden" name="action" value="exchange_gold">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                            <label for="gold_amount">Số thỏi vàng muốn đổi</label>
                                            <input id="gold_amount" name="gold_amount" type="number" min="1" step="1" placeholder="Ví dụ: 10" required>

                                            <button class="exchange-submit" type="submit">Đổi thỏi vàng</button>
                                        </form>

                                        <div class="quick-links">
                                            <a href="/app/nap-ngoc.php">Nạp tiền</a>
                                            <a href="/app/vong-quay.php">Vòng quay</a>
                                            <a href="/forum.php">Về diễn đàn</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><br>
            </div>
        </div>
        <div class="left_b_bottom">
            <div class="right_b_bottom">
                <div class="footer"><div class="left_bottom"></div><div class="right_bottom"></div></div>
            </div>
        </div>
    </div>
</body>
</html>
