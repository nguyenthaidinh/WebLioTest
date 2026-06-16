<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../forum_data.php';
include_once __DIR__ . '/account_info.php';

const LUCKY_GOLD_ITEM_ID = 457;
const CHECKIN_SPIN_REWARD = 1;

$lucky_rewards = [
    ['label' => 'Chuc may man', 'amount' => 0, 'weight' => 3000, 'color' => '#64748b'],
    ['label' => '10 TV', 'amount' => 10, 'weight' => 3000, 'color' => '#22c55e'],
    ['label' => '20 TV', 'amount' => 20, 'weight' => 2200, 'color' => '#14b8a6'],
    ['label' => '50 TV', 'amount' => 50, 'weight' => 800, 'color' => '#3b82f6'],
    ['label' => '100 TV', 'amount' => 100, 'weight' => 500, 'color' => '#a855f7'],
    ['label' => '200 TV', 'amount' => 200, 'weight' => 250, 'color' => '#f97316'],
    ['label' => '300 TV', 'amount' => 300, 'weight' => 150, 'color' => '#eab308'],
    ['label' => '500 TV', 'amount' => 500, 'weight' => 75, 'color' => '#ef4444'],
    ['label' => '1000 TV', 'amount' => 1000, 'weight' => 25, 'color' => '#f43f5e'],
];

$message = $_SESSION['lucky_spin_message'] ?? '';
$message_type = $_SESSION['lucky_spin_message_type'] ?? '';
unset($_SESSION['lucky_spin_message'], $_SESSION['lucky_spin_message_type']);

if (empty($_SESSION['lucky_spin_csrf_token'])) {
    $_SESSION['lucky_spin_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['lucky_spin_csrf_token'];

function lucky_set_message($message, $type = 'error') {
    $_SESSION['lucky_spin_message'] = $message;
    $_SESSION['lucky_spin_message_type'] = $type;
}

function lucky_ensure_checkin_table($conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS lucky_checkins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_id INT NOT NULL,
            checkin_date DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_account_date (account_id, checkin_date),
            KEY idx_account_id (account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($sql)) {
        throw new Exception('Khong the khoi tao bang diem danh: ' . $conn->error);
    }
}

function lucky_decode_items_bag($raw_items_bag) {
    $outer_slots = json_decode($raw_items_bag ?: '[]', true);
    if (!is_array($outer_slots)) {
        return [];
    }

    $items = [];
    foreach ($outer_slots as $slot) {
        if (is_string($slot)) {
            $item = json_decode($slot, true);
            $items[] = (json_last_error() === JSON_ERROR_NONE && is_array($item) && count($item) >= 4)
                ? $item
                : [-1, 0, '[]', 0];
            continue;
        }

        $items[] = (is_array($slot) && count($slot) >= 4) ? $slot : [-1, 0, '[]', 0];
    }

    return $items;
}

function lucky_encode_items_bag($items) {
    $encoded_slots = [];
    foreach ($items as $item) {
        $encoded_slots[] = json_encode($item, JSON_UNESCAPED_UNICODE);
    }

    return json_encode($encoded_slots, JSON_UNESCAPED_UNICODE);
}

function lucky_add_gold_to_bag($items, $amount) {
    $empty_slot_index = -1;

    foreach ($items as $index => &$item) {
        if (is_array($item) && isset($item[0]) && (int)$item[0] === LUCKY_GOLD_ITEM_ID) {
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

    $gold_item = [
        LUCKY_GOLD_ITEM_ID,
        $amount,
        json_encode([[73, 0]], JSON_UNESCAPED_UNICODE),
        round(microtime(true) * 1000)
    ];

    if ($empty_slot_index !== -1) {
        $items[$empty_slot_index] = $gold_item;
    } else {
        $items[] = $gold_item;
    }

    return $items;
}

function lucky_pick_reward($rewards) {
    $total_weight = 0;
    foreach ($rewards as $reward) {
        $total_weight += (int)$reward['weight'];
    }

    $roll = random_int(1, $total_weight);
    $cursor = 0;

    foreach ($rewards as $reward) {
        $cursor += (int)$reward['weight'];
        if ($roll <= $cursor) {
            return $reward;
        }
    }

    return $rewards[0];
}

function lucky_get_account_state($conn, $account_id) {
    $state = ['luotquay' => 0, 'thoi_vang' => 0];
    if (!$account_id) {
        return $state;
    }

    $stmt = $conn->prepare("SELECT luotquay, thoi_vang FROM account WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return $state;
    }

    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $state['luotquay'] = (int)($row['luotquay'] ?? 0);
        $state['thoi_vang'] = (int)($row['thoi_vang'] ?? 0);
    }
    $stmt->close();

    return $state;
}

function lucky_has_checked_in_today($conn, $account_id) {
    if (!$account_id) {
        return false;
    }

    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT id FROM lucky_checkins WHERE account_id = ? AND checkin_date = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $account_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $checked = $result && $result->num_rows > 0;
    $stmt->close();

    return $checked;
}

function lucky_handle_daily_checkin($conn, $account_id) {
    if (!$account_id) {
        throw new Exception('Khong tim thay tai khoan.');
    }

    $today = date('Y-m-d');
    $conn->begin_transaction();

    try {
        $stmt_checkin = $conn->prepare("INSERT INTO lucky_checkins (account_id, checkin_date) VALUES (?, ?)");
        if (!$stmt_checkin) {
            throw new Exception('Loi prepare diem danh: ' . $conn->error);
        }

        $stmt_checkin->bind_param("is", $account_id, $today);
        if (!$stmt_checkin->execute()) {
            $error_no = $stmt_checkin->errno;
            $error_message = $stmt_checkin->error;
            $stmt_checkin->close();
            if ($error_no === 1062) {
                throw new Exception('Hom nay ban da diem danh roi.');
            }
            throw new Exception('Khong the diem danh: ' . $error_message);
        }
        $stmt_checkin->close();

        $stmt_update = $conn->prepare("UPDATE account SET luotquay = luotquay + ? WHERE id = ?");
        if (!$stmt_update) {
            throw new Exception('Loi prepare cong luot quay: ' . $conn->error);
        }
        $spin_reward = CHECKIN_SPIN_REWARD;
        $stmt_update->bind_param("ii", $spin_reward, $account_id);
        if (!$stmt_update->execute() || $stmt_update->affected_rows === 0) {
            $stmt_update->close();
            throw new Exception('Khong the cong luot quay.');
        }
        $stmt_update->close();

        $conn->commit();
        return $spin_reward;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function lucky_handle_spin($conn, $account_id, $rewards) {
    if (!$account_id) {
        throw new Exception('Khong tim thay tai khoan.');
    }

    $conn->begin_transaction();

    try {
        $stmt_account = $conn->prepare("SELECT luotquay FROM account WHERE id = ? FOR UPDATE");
        if (!$stmt_account) {
            throw new Exception('Loi prepare tai khoan: ' . $conn->error);
        }
        $stmt_account->bind_param("i", $account_id);
        $stmt_account->execute();
        $account_result = $stmt_account->get_result();
        $account = $account_result ? $account_result->fetch_assoc() : null;
        $stmt_account->close();

        if (!$account) {
            throw new Exception('Tai khoan khong ton tai.');
        }

        $current_spins = (int)($account['luotquay'] ?? 0);
        if ($current_spins <= 0) {
            throw new Exception('Ban chua co luot quay.');
        }

        $reward = lucky_pick_reward($rewards);
        $reward_amount = (int)$reward['amount'];

        $stmt_update = $conn->prepare("UPDATE account SET luotquay = luotquay - 1, thoi_vang = thoi_vang + ? WHERE id = ? AND luotquay > 0");
        if (!$stmt_update) {
            throw new Exception('Loi prepare cap nhat quay: ' . $conn->error);
        }
        $stmt_update->bind_param("ii", $reward_amount, $account_id);
        if (!$stmt_update->execute() || $stmt_update->affected_rows === 0) {
            $stmt_update->close();
            throw new Exception('Khong the tru luot quay.');
        }
        $stmt_update->close();

        $conn->commit();
        $_SESSION['luotquay'] = $current_spins - 1;

        return $reward;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function lucky_handle_withdraw_gold($conn, $account_id, $player_id) {
    $amount = (int)($_POST['withdraw_amount'] ?? 0);

    if (!$account_id) {
        throw new Exception('Khong tim thay tai khoan.');
    }

    if (!$player_id) {
        throw new Exception('Ban chua co nhan vat trong game.');
    }

    if ($amount <= 0) {
        throw new Exception('So thoi vang rut phai lon hon 0.');
    }

    if ($amount > 1000000) {
        throw new Exception('So thoi vang moi lan rut qua lon.');
    }

    $conn->begin_transaction();

    try {
        $stmt_account = $conn->prepare("SELECT thoi_vang FROM account WHERE id = ? FOR UPDATE");
        if (!$stmt_account) {
            throw new Exception('Loi prepare kho thoi vang: ' . $conn->error);
        }
        $stmt_account->bind_param("i", $account_id);
        $stmt_account->execute();
        $account_result = $stmt_account->get_result();
        $account = $account_result ? $account_result->fetch_assoc() : null;
        $stmt_account->close();

        if (!$account) {
            throw new Exception('Tai khoan khong ton tai.');
        }

        $current_gold = (int)($account['thoi_vang'] ?? 0);
        if ($current_gold < $amount) {
            throw new Exception('Kho thoi vang khong du de rut.');
        }

        $stmt_player = $conn->prepare("SELECT items_bag FROM player WHERE id = ? AND account_id = ? FOR UPDATE");
        if (!$stmt_player) {
            throw new Exception('Loi prepare nhan vat: ' . $conn->error);
        }
        $stmt_player->bind_param("ii", $player_id, $account_id);
        $stmt_player->execute();
        $player_result = $stmt_player->get_result();
        $player = $player_result ? $player_result->fetch_assoc() : null;
        $stmt_player->close();

        if (!$player) {
            throw new Exception('Nhan vat khong ton tai hoac khong thuoc tai khoan nay.');
        }

        $items = lucky_decode_items_bag($player['items_bag'] ?? '[]');
        $items = lucky_add_gold_to_bag($items, $amount);
        $new_items_bag = lucky_encode_items_bag($items);

        if ($new_items_bag === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Khong the ma hoa tui do.');
        }

        $stmt_update_account = $conn->prepare("UPDATE account SET thoi_vang = thoi_vang - ? WHERE id = ? AND thoi_vang >= ?");
        if (!$stmt_update_account) {
            throw new Exception('Loi prepare tru kho thoi vang: ' . $conn->error);
        }
        $stmt_update_account->bind_param("iii", $amount, $account_id, $amount);
        if (!$stmt_update_account->execute() || $stmt_update_account->affected_rows === 0) {
            $stmt_update_account->close();
            throw new Exception('Khong the tru kho thoi vang.');
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
        $_SESSION['thoi_vang'] = $current_gold - $amount;
        return $amount;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

$checkin_table_ready = true;
$checkin_table_error = '';
if ($is_logged_in) {
    try {
        lucky_ensure_checkin_table($conn);
    } catch (Exception $e) {
        $checkin_table_ready = false;
        $checkin_table_error = $e->getMessage();
        error_log($checkin_table_error);
    }
}

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $posted_token = $_POST['csrf_token'] ?? '';
        if (!is_string($posted_token) || !hash_equals($csrf_token, $posted_token)) {
            throw new Exception('Phien thao tac khong hop le, vui long tai lai trang.');
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'daily_checkin') {
            if (!$checkin_table_ready) {
                throw new Exception('Diem danh tam thoi chua san sang.');
            }
            $spin_reward = lucky_handle_daily_checkin($conn, $account_id);
            lucky_set_message('Diem danh thanh cong, ban nhan ' . $spin_reward . ' luot quay.', 'success');
        } elseif ($action === 'lucky_spin') {
            $reward = lucky_handle_spin($conn, $account_id, $lucky_rewards);
            if ((int)$reward['amount'] > 0) {
                lucky_set_message('Chuc mung! Ban nhan duoc ' . $reward['label'] . '. Thoi vang da vao kho cho rut.', 'success');
            } else {
                lucky_set_message('Chuc may man lan sau.', 'warning');
            }
        } elseif ($action === 'withdraw_gold') {
            $withdrawn_amount = lucky_handle_withdraw_gold($conn, $account_id, $current_player_id);
            lucky_set_message('Rut thanh cong ' . number_format($withdrawn_amount, 0, ',', '.') . ' TV vao tui do.', 'success');
        } else {
            throw new Exception('Thao tac khong hop le.');
        }
    } catch (Exception $e) {
        lucky_set_message($e->getMessage(), 'error');
    }

    header("Location: /app/vong-quay.php");
    exit();
}

$account_state = $is_logged_in ? lucky_get_account_state($conn, $account_id) : ['luotquay' => 0, 'thoi_vang' => 0];
$remaining_spins = (int)$account_state['luotquay'];
$pending_gold = (int)$account_state['thoi_vang'];
$checked_in_today = $is_logged_in && $checkin_table_ready ? lucky_has_checked_in_today($conn, $account_id) : false;

$total_weight = array_sum(array_map(function ($reward) {
    return (int)$reward['weight'];
}, $lucky_rewards));

$wheel_gradient_parts = [];
$wheel_cursor = 0;
foreach ($lucky_rewards as $reward) {
    $slice = ((int)$reward['weight'] / $total_weight) * 360;
    $wheel_gradient_parts[] = $reward['color'] . ' ' . $wheel_cursor . 'deg ' . ($wheel_cursor + $slice) . 'deg';
    $wheel_cursor += $slice;
}
$wheel_gradient = implode(', ', $wheel_gradient_parts);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vong Quay May Man - Chu Be Rong Online</title>
    <link rel="icon" href="/images/favicon-48x48.ico" type="image/x-icon">
    <link rel="stylesheet" href="/view/static/css/template.css?v=1.10">
    <link rel="stylesheet" href="/view/static/css/w3.css?v=1.01">
    <link rel="stylesheet" href="/view/static/css/styleSheet.css?v=1.1">
    <script src="/view/static/js/disable_devtools.js"></script>
    <style>
        .lucky-wrap {
            max-width: 760px;
            margin: 0 auto;
            color: #2d1600;
        }
        .lucky-panel {
            background: #fff8ec;
            border: 1px solid #f6b35d;
            border-radius: 8px;
            padding: 14px;
            margin: 10px;
            text-align: center;
        }
        .lucky-panel h2 {
            color: #7c2d12;
            font-size: 18px;
            margin: 0 0 8px;
        }
        .lucky-status {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0 12px;
            font-weight: 800;
        }
        .lucky-status span {
            background: #fff;
            border: 1px solid #f0c27b;
            border-radius: 6px;
            padding: 6px 9px;
        }
        .lucky-note {
            margin: 0 0 10px;
            color: #7c2d12;
            font-size: 12px;
            font-weight: 700;
        }
        .action-row {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin: 10px 0 12px;
        }
        .wheel-area {
            position: relative;
            width: min(78vw, 320px);
            aspect-ratio: 1;
            margin: 12px auto;
        }
        .wheel-pointer {
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 14px solid transparent;
            border-right: 14px solid transparent;
            border-top: 28px solid #b91c1c;
            z-index: 2;
        }
        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(<?php echo htmlspecialchars($wheel_gradient); ?>);
            border: 8px solid #7c2d12;
            box-shadow: inset 0 0 0 4px rgba(255,255,255,0.6), 0 8px 18px rgba(124,45,18,0.25);
            transition: transform 0.7s ease-out;
        }
        .wheel::after {
            content: "";
            position: absolute;
            inset: 23%;
            border-radius: 50%;
            background: #fff8ec;
            border: 3px solid #f0c27b;
        }
        .wheel-center {
            position: absolute;
            inset: 34%;
            z-index: 1;
            border-radius: 50%;
            background: #f97316;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            text-transform: uppercase;
            border: 3px solid #fff;
        }
        .wheel.is-spinning {
            transform: rotate(1440deg);
        }
        .spin-button,
        .checkin-button,
        .withdraw-button {
            border: 0;
            border-radius: 6px;
            color: #fff;
            font-weight: 900;
            padding: 10px 16px;
            cursor: pointer;
        }
        .spin-button {
            background: #f97316;
        }
        .checkin-button {
            background: #16a34a;
        }
        .withdraw-button {
            background: #b45309;
        }
        .spin-button:disabled,
        .checkin-button:disabled,
        .withdraw-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .withdraw-box {
            background: #fff;
            border: 1px solid #f0c27b;
            border-radius: 8px;
            padding: 10px;
            margin: 12px auto;
            max-width: 360px;
        }
        .withdraw-box strong {
            color: #7c2d12;
            display: block;
            margin-bottom: 8px;
        }
        .withdraw-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
        }
        .withdraw-form input {
            border: 1px solid #f0c27b;
            border-radius: 6px;
            padding: 9px;
            min-width: 0;
        }
        .message {
            border-radius: 6px;
            padding: 9px;
            margin: 10px 0;
            font-weight: 800;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .message.warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .reward-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
            gap: 8px;
            margin-top: 14px;
            text-align: left;
        }
        .reward-row {
            background: #fff;
            border: 1px solid #f0c27b;
            border-radius: 6px;
            padding: 8px;
            font-size: 12px;
        }
        .reward-row strong {
            display: block;
            color: #7c2d12;
            margin-bottom: 3px;
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
        @media (max-width: 420px) {
            .withdraw-form {
                grid-template-columns: 1fr;
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
                                    <td><a href="/">Trang Chu</a></td>
                                    <td><a href="/forum.php">Dien Dan</a></td>
                                </tr>
                            </table>
                        </div>

                        <div class="body">
                            <div class="lucky-wrap">
                                <div class="lucky-panel">
                                    <h2>Vong Quay May Man</h2>

                                    <?php if (!$is_logged_in): ?>
                                        <div class="message error">Ban can dang nhap de quay.</div>
                                        <div class="quick-links"><a href="/app/login.php">Dang nhap</a></div>
                                    <?php else: ?>
                                        <div class="lucky-status">
                                            <span>Nhan vat: <?php echo htmlspecialchars($player_name); ?></span>
                                            <span>Luot quay: <?php echo number_format($remaining_spins, 0, ',', '.'); ?></span>
                                            <span>TV cho rut: <?php echo number_format($pending_gold, 0, ',', '.'); ?></span>
                                            <span><?php echo $checked_in_today ? 'Da diem danh hom nay' : 'Chua diem danh hom nay'; ?></span>
                                        </div>
                                        <p class="lucky-note">Moi ngay diem danh nhan 1 luot quay. Nap tien: 10.000 VND = 1 luot quay. Trung TV se vao kho cho rut; thoat game truoc khi rut TV.</p>

                                        <?php if ($checkin_table_error !== ''): ?>
                                            <div class="message error"><?php echo htmlspecialchars($checkin_table_error); ?></div>
                                        <?php endif; ?>

                                        <?php if ($message !== ''): ?>
                                            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                                                <?php echo htmlspecialchars($message); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="action-row">
                                            <form method="post" action="/app/vong-quay.php">
                                                <input type="hidden" name="action" value="daily_checkin">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <button class="checkin-button" type="submit" <?php echo ($checked_in_today || !$checkin_table_ready) ? 'disabled' : ''; ?>>
                                                    Diem danh
                                                </button>
                                            </form>

                                            <form id="luckySpinForm" method="post" action="/app/vong-quay.php">
                                                <input type="hidden" name="action" value="lucky_spin">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <button class="spin-button" type="submit" <?php echo $remaining_spins <= 0 ? 'disabled' : ''; ?>>
                                                    Quay ngay
                                                </button>
                                            </form>
                                        </div>

                                        <?php if ($pending_gold > 0): ?>
                                            <div class="withdraw-box">
                                                <strong>Rut thoi vang vao tui do</strong>
                                                <form class="withdraw-form" method="post" action="/app/vong-quay.php">
                                                    <input type="hidden" name="action" value="withdraw_gold">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input name="withdraw_amount" type="number" min="1" max="<?php echo $pending_gold; ?>" value="<?php echo $pending_gold; ?>" required>
                                                    <button class="withdraw-button" type="submit">Rut</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>

                                        <div class="wheel-area">
                                            <div class="wheel-pointer"></div>
                                            <div id="luckyWheel" class="wheel"></div>
                                            <div class="wheel-center">Quay</div>
                                        </div>

                                        <div class="reward-grid">
                                            <?php foreach ($lucky_rewards as $reward): ?>
                                                <div class="reward-row">
                                                    <strong><?php echo htmlspecialchars($reward['label']); ?></strong>
                                                    Ti le: <?php echo rtrim(rtrim(number_format(((int)$reward['weight'] / $total_weight) * 100, 2), '0'), '.'); ?>%
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="quick-links">
                                            <a href="/forum.php">Ve dien dan</a>
                                            <a href="/app/nap-ngoc.php">Nap tien</a>
                                            <a href="/app/doi-vang.php">Doi thoi vang</a>
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

    <script>
        (function () {
            var form = document.getElementById('luckySpinForm');
            var wheel = document.getElementById('luckyWheel');
            if (!form || !wheel) {
                return;
            }

            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === '1') {
                    return;
                }

                event.preventDefault();
                var button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                }

                wheel.classList.add('is-spinning');
                window.setTimeout(function () {
                    form.dataset.submitting = '1';
                    form.submit();
                }, 720);
            });
        })();
    </script>
</body>
</html>
