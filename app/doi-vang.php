<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../forum_data.php';
include_once __DIR__ . '/account_info.php';

const GOLD_ITEM_ID = 457;
const GOLD_EXCHANGE_RATE = 100;

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
        throw new Exception('So thoi vang can doi phai lon hon 0.');
    }

    if ($amount > 1000000) {
        throw new Exception('So thoi vang moi lan doi qua lon.');
    }

    if ($account_id === null || $account_id <= 0) {
        throw new Exception('Khong tim thay tai khoan.');
    }

    if ($player_id === null || $player_id <= 0) {
        throw new Exception('Ban chua co nhan vat trong game.');
    }

    $cost = $amount * GOLD_EXCHANGE_RATE;
    $conn->begin_transaction();

    try {
        $stmt_account = $conn->prepare("SELECT vnd FROM account WHERE id = ? FOR UPDATE");
        if (!$stmt_account) {
            throw new Exception('Loi prepare tai khoan: ' . $conn->error);
        }
        $stmt_account->bind_param("i", $account_id);
        $stmt_account->execute();
        $account_result = $stmt_account->get_result();
        $account = $account_result->fetch_assoc();
        $stmt_account->close();

        if (!$account) {
            throw new Exception('Tai khoan khong ton tai.');
        }

        $current_balance = (int)$account['vnd'];
        if ($current_balance < $cost) {
            throw new Exception('So du VND khong du. Ban can ' . number_format($cost, 0, ',', '.') . ' VND.');
        }

        $stmt_player = $conn->prepare("SELECT items_bag FROM player WHERE id = ? AND account_id = ? FOR UPDATE");
        if (!$stmt_player) {
            throw new Exception('Loi prepare nhan vat: ' . $conn->error);
        }
        $stmt_player->bind_param("ii", $player_id, $account_id);
        $stmt_player->execute();
        $player_result = $stmt_player->get_result();
        $player = $player_result->fetch_assoc();
        $stmt_player->close();

        if (!$player) {
            throw new Exception('Nhan vat khong ton tai hoac khong thuoc tai khoan nay.');
        }

        $items = decode_items_bag_for_exchange($player['items_bag'] ?? '[]');
        $items = add_gold_item_to_bag($items, $amount);
        $new_items_bag = encode_items_bag_for_exchange($items);

        if ($new_items_bag === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Khong the ma hoa tui do.');
        }

        $stmt_update_account = $conn->prepare("UPDATE account SET vnd = vnd - ? WHERE id = ?");
        if (!$stmt_update_account) {
            throw new Exception('Loi prepare tru VND: ' . $conn->error);
        }
        $stmt_update_account->bind_param("ii", $cost, $account_id);
        if (!$stmt_update_account->execute() || $stmt_update_account->affected_rows === 0) {
            $stmt_update_account->close();
            throw new Exception('Khong the tru VND khoi tai khoan.');
        }
        $stmt_update_account->close();

        $stmt_update_bag = $conn->prepare("UPDATE player SET items_bag = ? WHERE id = ? AND account_id = ?");
        if (!$stmt_update_bag) {
            throw new Exception('Loi prepare cap nhat tui do: ' . $conn->error);
        }
        $stmt_update_bag->bind_param("sii", $new_items_bag, $player_id, $account_id);
        if (!$stmt_update_bag->execute()) {
            $stmt_update_bag->close();
            throw new Exception('Khong the cap nhat tui do.');
        }
        $stmt_update_bag->close();

        $conn->commit();
        $_SESSION['vnd'] = $current_balance - $cost;
        return 'Doi thanh cong ' . number_format($amount, 0, ',', '.') . ' thoi vang.';
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'exchange_gold') {
    try {
        $posted_token = $_POST['csrf_token'] ?? '';
        if (!is_string($posted_token) || !hash_equals($csrf_token, $posted_token)) {
            throw new Exception('Phien giao dich khong hop le, vui long tai lai trang.');
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
    <title>Doi Thoi Vang - Chu Be Rong Online</title>
    <link rel="icon" href="/images/favicon-48x48.ico" type="image/x-icon">
    <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
    <link rel="stylesheet" href="/view/static/css/w3.css?v=1.01">
    <link rel="stylesheet" href="/view/static/css/styleSheet.css?v=1.1">
    <script src="/view/static/js/disable_devtools.js"></script>
    <style>
        .exchange-wrap {
            max-width: 520px;
            margin: 0 auto;
            color: #2d1600;
        }
        .exchange-panel {
            background: #fff8ec;
            border: 1px solid #f6b35d;
            border-radius: 8px;
            padding: 14px;
            margin: 10px;
            text-align: center;
        }
        .exchange-panel h2 {
            color: #7c2d12;
            font-size: 18px;
            margin: 0 0 8px;
        }
        .balance-line {
            font-weight: 800;
            color: #7c2d12;
            margin-bottom: 10px;
        }
        .balance-line span {
            color: #dc2626;
        }
        .rate-line {
            color: #6b3a00;
            font-size: 12px;
            margin-bottom: 12px;
        }
        .exchange-form {
            display: grid;
            gap: 10px;
            text-align: left;
        }
        .exchange-form label {
            font-weight: 800;
            color: #7c2d12;
        }
        .exchange-form input[type="number"] {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #f0c27b;
            border-radius: 6px;
            padding: 9px;
            background: #fff;
            color: #111827;
        }
        .exchange-submit {
            border: 0;
            border-radius: 6px;
            background: #f97316;
            color: #fff;
            font-weight: 800;
            padding: 10px;
            cursor: pointer;
        }
        .message {
            border-radius: 6px;
            padding: 9px;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .quick-links {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
            font-size: 12px;
        }
        .quick-links a {
            color: #b45309;
            font-weight: 800;
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
                                    <td><a href="/">Trang Chu</a></td>
                                    <td><a href="/forum.php">Dien Dan</a></td>
                                </tr>
                            </table>
                        </div>

                        <div class="body">
                            <div class="exchange-wrap">
                                <div class="exchange-panel">
                                    <h2>Doi Thoi Vang</h2>

                                    <?php if (!$is_logged_in): ?>
                                        <div class="message error">Ban can dang nhap de doi thoi vang.</div>
                                        <div class="quick-links"><a href="/app/login.php">Dang nhap</a></div>
                                    <?php else: ?>
                                        <?php if ($message !== ''): ?>
                                            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                                                <?php echo htmlspecialchars($message); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="balance-line">
                                            So du: <span><?php echo number_format((int)$user_balance, 0, ',', '.'); ?></span> VND
                                        </div>
                                        <div class="rate-line">
                                            Ti le: 1 thoi vang = <?php echo number_format(GOLD_EXCHANGE_RATE, 0, ',', '.'); ?> VND. Thoat game truoc khi doi de tranh mat dong bo tui do.
                                        </div>

                                        <form class="exchange-form" method="post" action="/app/doi-vang.php">
                                            <input type="hidden" name="action" value="exchange_gold">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                            <label for="gold_amount">So thoi vang muon doi</label>
                                            <input id="gold_amount" name="gold_amount" type="number" min="1" step="1" required>

                                            <button class="exchange-submit" type="submit">Doi Thoi Vang</button>
                                        </form>

                                        <div class="quick-links">
                                            <a href="/app/nap-ngoc.php">Nap Tien</a>
                                            <a href="/forum.php">Ve dien dan</a>
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
