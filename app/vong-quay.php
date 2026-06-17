<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../forum_data.php';
include_once __DIR__ . '/account_info.php';
require_once __DIR__ . '/../lucky_rewards.php';

const LUCKY_GOLD_ITEM_ID = 457;
const CHECKIN_SPIN_REWARD = 1;

$reward_config_error = '';
try {
    $lucky_rewards = lucky_rewards_load($conn);
    if (lucky_rewards_total_weight($lucky_rewards) <= 0) {
        throw new Exception('Tổng tỉ lệ vòng quay phải lớn hơn 0.');
    }
} catch (Exception $e) {
    $reward_config_error = $e->getMessage();
    error_log($reward_config_error);
    $lucky_rewards = LUCKY_REWARD_DEFAULTS;
}

$message = $_SESSION['lucky_spin_message'] ?? '';
$message_type = $_SESSION['lucky_spin_message_type'] ?? '';
$spin_result = $_SESSION['lucky_spin_result'] ?? null;
unset($_SESSION['lucky_spin_message'], $_SESSION['lucky_spin_message_type'], $_SESSION['lucky_spin_result']);

if (empty($_SESSION['lucky_spin_csrf_token'])) {
    $_SESSION['lucky_spin_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['lucky_spin_csrf_token'];

function lucky_set_message($message, $type = 'error', $result = null) {
    $_SESSION['lucky_spin_message'] = $message;
    $_SESSION['lucky_spin_message_type'] = $type;
    if ($result !== null) {
        $_SESSION['lucky_spin_result'] = $result;
    }
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
        throw new Exception('Không thể khởi tạo bảng điểm danh: ' . $conn->error);
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

function lucky_find_reward_index($rewards, $spin_result) {
    if (!is_array($spin_result)) {
        return null;
    }

    $result_key = isset($spin_result['reward_key']) ? (string)$spin_result['reward_key'] : '';
    if ($result_key !== '') {
        foreach ($rewards as $index => $reward) {
            if ((string)$reward['reward_key'] === $result_key) {
                return $index;
            }
        }
    }

    $result_label = isset($spin_result['label']) ? (string)$spin_result['label'] : '';
    $has_amount = array_key_exists('amount', $spin_result);
    $result_amount = $has_amount ? (int)$spin_result['amount'] : null;

    foreach ($rewards as $index => $reward) {
        if (
            $result_label !== ''
            && (string)$reward['label'] === $result_label
            && (!$has_amount || (int)$reward['amount'] === $result_amount)
        ) {
            return $index;
        }
    }

    return null;
}

function lucky_wheel_rotation_for_index($index, $segment_degrees, $turns = 8) {
    if ($index === null || $segment_degrees <= 0) {
        return null;
    }

    $center_angle = -90 + ($index * $segment_degrees) + ($segment_degrees / 2);
    $target_rotation = fmod(360 - fmod($center_angle, 360), 360);
    if ($target_rotation < 0) {
        $target_rotation += 360;
    }

    return ($turns * 360) + $target_rotation;
}

function lucky_format_degrees($degrees) {
    return rtrim(rtrim(number_format((float)$degrees, 4, '.', ''), '0'), '.');
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
        throw new Exception('Không tìm thấy tài khoản.');
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
                throw new Exception('Hôm nay bạn đã điểm danh rồi.');
            }
            throw new Exception('Không thể điểm danh: ' . $error_message);
        }
        $stmt_checkin->close();

        $stmt_update = $conn->prepare("UPDATE account SET luotquay = luotquay + ? WHERE id = ?");
        if (!$stmt_update) {
            throw new Exception('Lỗi prepare cộng lượt quay: ' . $conn->error);
        }
        $spin_reward = CHECKIN_SPIN_REWARD;
        $stmt_update->bind_param("ii", $spin_reward, $account_id);
        if (!$stmt_update->execute() || $stmt_update->affected_rows === 0) {
            $stmt_update->close();
            throw new Exception('Không thể cộng lượt quay.');
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
        throw new Exception('Không tìm thấy tài khoản.');
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
            throw new Exception('Bạn chưa có lượt quay.');
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
            throw new Exception('Không thể trừ lượt quay.');
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
        throw new Exception('Không tìm thấy tài khoản.');
    }

    if (!$player_id) {
        throw new Exception('Bạn chưa có nhân vật trong game.');
    }

    if ($amount <= 0) {
        throw new Exception('Số thỏi vàng rút phải lớn hơn 0.');
    }

    if ($amount > 1000000) {
        throw new Exception('Số thỏi vàng mỗi lần rút quá lớn.');
    }

    $conn->begin_transaction();

    try {
        $stmt_account = $conn->prepare("SELECT thoi_vang FROM account WHERE id = ? FOR UPDATE");
        if (!$stmt_account) {
            throw new Exception('Lỗi prepare kho thỏi vàng: ' . $conn->error);
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
            throw new Exception('Kho thỏi vàng không đủ để rút.');
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
            throw new Exception('Nhân vật không tồn tại hoặc không thuộc tài khoản này.');
        }

        $items = lucky_decode_items_bag($player['items_bag'] ?? '[]');
        $items = lucky_add_gold_to_bag($items, $amount);
        $new_items_bag = lucky_encode_items_bag($items);

        if ($new_items_bag === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Không thể mã hóa túi đồ.');
        }

        $stmt_update_account = $conn->prepare("UPDATE account SET thoi_vang = thoi_vang - ? WHERE id = ? AND thoi_vang >= ?");
        if (!$stmt_update_account) {
            throw new Exception('Lỗi prepare trừ kho thỏi vàng: ' . $conn->error);
        }
        $stmt_update_account->bind_param("iii", $amount, $account_id, $amount);
        if (!$stmt_update_account->execute() || $stmt_update_account->affected_rows === 0) {
            $stmt_update_account->close();
            throw new Exception('Không thể trừ kho thỏi vàng.');
        }
        $stmt_update_account->close();

        $stmt_update_bag = $conn->prepare("UPDATE player SET items_bag = ? WHERE id = ? AND account_id = ?");
        if (!$stmt_update_bag) {
            throw new Exception('Lỗi prepare cập nhật túi đồ: ' . $conn->error);
        }
        $stmt_update_bag->bind_param("sii", $new_items_bag, $player_id, $account_id);
        if (!$stmt_update_bag->execute()) {
            $stmt_update_bag->close();
            throw new Exception('Không thể cập nhật túi đồ.');
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
            throw new Exception('Phiên thao tác không hợp lệ, vui lòng tải lại trang.');
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'daily_checkin') {
            if (!$checkin_table_ready) {
                throw new Exception('Điểm danh tạm thời chưa sẵn sàng.');
            }
            $spin_reward = lucky_handle_daily_checkin($conn, $account_id);
            lucky_set_message('Điểm danh thành công, bạn nhận ' . $spin_reward . ' lượt quay.', 'success');
        } elseif ($action === 'lucky_spin') {
            $reward = lucky_handle_spin($conn, $account_id, $lucky_rewards);
            if ((int)$reward['amount'] > 0) {
                lucky_set_message(
                    'Chúc mừng! Bạn trúng ' . $reward['label'] . '.',
                    'success',
                    ['type' => 'win', 'reward_key' => $reward['reward_key'], 'label' => $reward['label'], 'amount' => (int)$reward['amount']]
                );
            } else {
                lucky_set_message(
                    'Chúc may mắn lần sau.',
                    'warning',
                    ['type' => 'miss', 'reward_key' => $reward['reward_key'], 'label' => $reward['label'], 'amount' => 0]
                );
            }
        } elseif ($action === 'withdraw_gold') {
            $withdrawn_amount = lucky_handle_withdraw_gold($conn, $account_id, $current_player_id);
            lucky_set_message('Rút thành công ' . number_format($withdrawn_amount, 0, ',', '.') . ' TV vào túi đồ.', 'success');
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

$wheel_gradient_parts = [];
$wheel_segment_count = max(1, count($lucky_rewards));
$wheel_segment_degrees = 360 / $wheel_segment_count;
$wheel_cursor = 0;
foreach ($lucky_rewards as $reward) {
    $wheel_gradient_parts[] = $reward['color'] . ' ' . round($wheel_cursor, 4) . 'deg ' . round($wheel_cursor + $wheel_segment_degrees, 4) . 'deg';
    $wheel_cursor += $wheel_segment_degrees;
}
$wheel_gradient = implode(', ', $wheel_gradient_parts);
$wheel_segment_css = rtrim(rtrim(number_format($wheel_segment_degrees, 4, '.', ''), '0'), '.');
$wheel_result_index = lucky_find_reward_index($lucky_rewards, $spin_result);
$wheel_settled_rotation = lucky_wheel_rotation_for_index($wheel_result_index, $wheel_segment_degrees);
$wheel_settled_rotation_text = $wheel_settled_rotation !== null ? lucky_format_degrees($wheel_settled_rotation) : '';
$wheel_class = 'wheel' . ($wheel_settled_rotation_text !== '' ? ' has-result' : '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vòng Quay May Mắn - Chú Bé Rồng Online</title>
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
            background: linear-gradient(180deg, #fffaf0 0%, #fff3d8 100%);
            border: 1px solid #f6b35d;
            border-radius: 10px;
            padding: 16px;
            margin: 10px;
            text-align: center;
            box-shadow: 0 10px 26px rgba(124, 45, 18, 0.18);
        }
        .lucky-panel h2 {
            color: #7c2d12;
            font-size: 20px;
            margin: 0 0 8px;
            font-weight: 900;
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
            border-radius: 999px;
            padding: 7px 11px;
            box-shadow: 0 2px 5px rgba(124, 45, 18, 0.08);
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
            margin: 12px 0 12px;
        }
        .spin-result-card {
            position: relative;
            overflow: hidden;
            max-width: 520px;
            margin: 12px auto 14px;
            border-radius: 10px;
            padding: 14px;
            border: 1px solid rgba(250, 204, 21, 0.8);
            background: linear-gradient(135deg, #fff7cc 0%, #fff 42%, #ffedd5 100%);
            box-shadow: 0 8px 22px rgba(180, 83, 9, 0.25);
            color: #7c2d12;
        }
        .spin-result-card::before {
            content: "";
            position: absolute;
            inset: -40% auto auto -20%;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.45), transparent 68%);
            pointer-events: none;
        }
        .spin-result-card.miss {
            border-color: rgba(148, 163, 184, 0.55);
            background: linear-gradient(135deg, #f8fafc 0%, #fff 55%, #e2e8f0 100%);
            box-shadow: 0 8px 18px rgba(51, 65, 85, 0.16);
        }
        .result-eyebrow {
            position: relative;
            display: inline-block;
            border-radius: 999px;
            padding: 4px 11px;
            background: #7c2d12;
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .spin-result-card.miss .result-eyebrow {
            background: #64748b;
        }
        .result-title {
            position: relative;
            margin-top: 8px;
            font-size: 16px;
            font-weight: 900;
            color: #7c2d12;
        }
        .result-prize {
            position: relative;
            margin-top: 6px;
            font-size: 30px;
            line-height: 1;
            font-weight: 900;
            color: #ea580c;
            text-shadow: 0 2px 0 rgba(255,255,255,0.9);
        }
        .spin-result-card.miss .result-prize {
            font-size: 22px;
            color: #475569;
        }
        .result-desc {
            position: relative;
            margin: 8px auto 0;
            color: #7c2d12;
            font-size: 12px;
            font-weight: 800;
            max-width: 420px;
        }
        .result-actions {
            position: relative;
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .result-link {
            border: 0;
            border-radius: 6px;
            background: #f97316;
            color: #fff;
            font-weight: 900;
            padding: 8px 12px;
            text-decoration: none;
            font-size: 12px;
        }
        .result-link.secondary {
            background: #7c2d12;
        }
        .result-link:hover {
            color: #fff;
            text-decoration: none;
        }
        .wheel-area {
            position: relative;
            width: min(82vw, 370px);
            aspect-ratio: 1;
            margin: 14px auto 18px;
        }
        .wheel-pointer {
            position: absolute;
            top: -9px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 17px solid transparent;
            border-right: 17px solid transparent;
            border-top: 35px solid #dc2626;
            filter: drop-shadow(0 3px 2px rgba(0,0,0,0.28));
            z-index: 5;
        }
        .wheel {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            background:
                repeating-conic-gradient(from -90deg, rgba(255,255,255,0.75) 0deg 1.4deg, transparent 1.4deg <?php echo htmlspecialchars($wheel_segment_css); ?>deg),
                conic-gradient(from -90deg, <?php echo htmlspecialchars($wheel_gradient); ?>);
            border: 10px solid #7c2d12;
            box-shadow:
                inset 0 0 0 5px rgba(255,255,255,0.65),
                inset 0 0 24px rgba(0,0,0,0.16),
                0 12px 26px rgba(124,45,18,0.35);
            transition: transform 4.2s cubic-bezier(0.08, 0.72, 0.08, 1);
        }
        .wheel::after {
            content: "";
            position: absolute;
            inset: 27%;
            border-radius: 50%;
            background: radial-gradient(circle, #fffdf4 0%, #fff4d8 100%);
            border: 4px solid #facc15;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.75);
            z-index: 2;
        }
        .wheel-label {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 84px;
            min-height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translate(-50%, -50%) rotate(var(--angle)) translateY(-132px) rotate(90deg);
            transform-origin: center;
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            line-height: 1.05;
            text-align: center;
            text-shadow: 0 2px 3px rgba(0,0,0,0.45);
            z-index: 1;
            pointer-events: none;
        }
        .wheel-center {
            position: absolute;
            inset: 36%;
            z-index: 3;
            border-radius: 50%;
            background: linear-gradient(180deg, #fb923c 0%, #f97316 100%);
            color: #fff;
            display: grid;
            place-items: center;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-weight: 900;
            font-size: 15px;
            line-height: 1;
            text-align: center;
            text-transform: uppercase;
            border: 4px solid #fff;
            box-shadow: 0 5px 14px rgba(124,45,18,0.28);
            cursor: pointer;
            outline: 0;
            transition: transform 0.18s ease, filter 0.18s ease, box-shadow 0.18s ease;
        }
        .wheel-center:hover {
            filter: brightness(1.06);
            transform: scale(1.03);
            box-shadow: 0 7px 18px rgba(124,45,18,0.34);
        }
        .wheel-center:active {
            transform: scale(0.98);
        }
        .wheel-center:disabled {
            background: linear-gradient(180deg, #cbd5e1 0%, #94a3b8 100%);
            cursor: not-allowed;
            filter: none;
            transform: none;
        }
        .wheel.has-result {
            transform: rotate(var(--settled-rotation, 0deg));
        }
        .wheel.is-spinning {
            transform: rotate(var(--spin-rotation, 2880deg));
        }
        .spin-button,
        .checkin-button,
        .withdraw-button {
            border: 0;
            border-radius: 8px;
            color: #fff;
            font-weight: 900;
            padding: 11px 18px;
            cursor: pointer;
            box-shadow: 0 5px 12px rgba(124,45,18,0.16);
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
            background: linear-gradient(180deg, #fff 0%, #fff8ec 100%);
            border: 1px solid #f0c27b;
            border-radius: 10px;
            padding: 12px;
            margin: 12px auto;
            max-width: 410px;
            box-shadow: 0 5px 14px rgba(124,45,18,0.12);
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
            .wheel-label {
                width: 68px;
                font-size: 9px;
                transform: translate(-50%, -50%) rotate(var(--angle)) translateY(-108px) rotate(90deg);
            }
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
                                    <td><a href="/">Trang Chủ</a></td>
                                    <td><a href="/forum.php">Diễn Đàn</a></td>
                                </tr>
                            </table>
                        </div>

                        <div class="body">
                            <div class="lucky-wrap">
                                <div class="lucky-panel">
                                    <h2>Vòng Quay May Mắn</h2>

                                    <?php if (!$is_logged_in): ?>
                                        <div class="message error">Bạn cần đăng nhập để quay.</div>
                                        <div class="quick-links"><a href="/app/login.php">Đăng nhập</a></div>
                                    <?php else: ?>
                                        <div class="lucky-status">
                                            <span>Nhân vật: <?php echo htmlspecialchars($player_name); ?></span>
                                            <span>Lượt quay: <?php echo number_format($remaining_spins, 0, ',', '.'); ?></span>
                                            <span>TV chờ rút: <?php echo number_format($pending_gold, 0, ',', '.'); ?></span>
                                            <span><?php echo $checked_in_today ? 'Đã điểm danh hôm nay' : 'Chưa điểm danh hôm nay'; ?></span>
                                        </div>
                                        <p class="lucky-note">Mỗi ngày điểm danh nhận 1 lượt quay. Tích lũy 10.000 = 1 lượt quay. Trúng TV sẽ vào kho chờ rút; thoát game trước khi rút TV.</p>

                                        <?php if ($checkin_table_error !== ''): ?>
                                            <div class="message error"><?php echo htmlspecialchars($checkin_table_error); ?></div>
                                        <?php endif; ?>

                                        <?php if (is_array($spin_result)): ?>
                                            <?php
                                                $result_type = ($spin_result['type'] ?? '') === 'win' ? 'win' : 'miss';
                                                $result_label = (string)($spin_result['label'] ?? '');
                                                $result_amount = (int)($spin_result['amount'] ?? 0);
                                            ?>
                                            <div class="spin-result-card <?php echo htmlspecialchars($result_type); ?>" id="spinResult">
                                                <?php if ($result_type === 'win'): ?>
                                                    <div class="result-eyebrow">Kết quả quay</div>
                                                    <div class="result-title">Chúc mừng bạn đã trúng</div>
                                                    <div class="result-prize"><?php echo htmlspecialchars($result_label); ?></div>
                                                    <div class="result-desc">
                                                        <?php echo number_format($result_amount, 0, ',', '.'); ?> TV đã được cộng vào kho chờ rút. Hãy thoát game trước khi rút vào túi đồ.
                                                    </div>
                                                    <div class="result-actions">
                                                        <a class="result-link" href="#withdrawGold">Rút thỏi vàng</a>
                                                        <a class="result-link secondary" href="/app/vong-quay.php">Quay tiếp</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="result-eyebrow">Kết quả quay</div>
                                                    <div class="result-title">Chúc bạn may mắn lần sau</div>
                                                    <div class="result-prize"><?php echo htmlspecialchars($result_label ?: 'Chúc may mắn'); ?></div>
                                                    <div class="result-desc">Lần này chưa trúng TV, bạn có thể điểm danh hoặc tích lũy nạp để nhận thêm lượt quay.</div>
                                                    <div class="result-actions">
                                                        <a class="result-link secondary" href="/app/vong-quay.php">Quay tiếp</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($message !== ''): ?>
                                            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                                                <?php echo htmlspecialchars($message); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="action-row">
                                            <form method="post" action="/app/vong-quay.php">
                                                <input type="hidden" name="action" value="daily_checkin">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <button class="checkin-button" type="submit" <?php echo ($checked_in_today || !$checkin_table_ready) ? 'disabled' : ''; ?>>
                                                    Điểm danh
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
                                            <div class="withdraw-box" id="withdrawGold">
                                                <strong>Rút thỏi vàng vào túi đồ</strong>
                                                <form class="withdraw-form" method="post" action="/app/vong-quay.php">
                                                    <input type="hidden" name="action" value="withdraw_gold">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input name="withdraw_amount" type="number" min="1" max="<?php echo $pending_gold; ?>" value="<?php echo $pending_gold; ?>" required>
                                                    <button class="withdraw-button" type="submit">Rút</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>

                                        <div class="wheel-area">
                                            <div class="wheel-pointer"></div>
                                            <div id="luckyWheel" class="<?php echo htmlspecialchars($wheel_class); ?>"<?php if ($wheel_settled_rotation_text !== ''): ?> style="--settled-rotation: <?php echo htmlspecialchars($wheel_settled_rotation_text); ?>deg;" data-settled-rotation="<?php echo htmlspecialchars($wheel_settled_rotation_text); ?>"<?php endif; ?>>
                                                <?php foreach ($lucky_rewards as $index => $reward): ?>
                                                    <?php $label_angle = -90 + ($index * $wheel_segment_degrees) + ($wheel_segment_degrees / 2); ?>
                                                    <span class="wheel-label" style="--angle: <?php echo round($label_angle, 4); ?>deg;">
                                                        <?php echo htmlspecialchars($reward['label']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <button id="wheelCenterSpin" class="wheel-center" type="button" <?php echo $remaining_spins <= 0 ? 'disabled' : ''; ?>>Quay</button>
                                        </div>

                                        <div class="quick-links">
                                            <a href="/forum.php">Về diễn đàn</a>
                                            <a href="/app/nap-ngoc.php">Nạp tiền</a>
                                            <a href="/app/doi-vang.php">Đổi thỏi vàng</a>
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
            var centerButton = document.getElementById('wheelCenterSpin');
            var result = document.getElementById('spinResult');
            if (result) {
                window.setTimeout(function () {
                    result.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 120);
            }
            if (!form || !wheel) {
                return;
            }

            if (centerButton) {
                centerButton.addEventListener('click', function () {
                    var submitButton = form.querySelector('button[type="submit"]');
                    if (!submitButton || centerButton.disabled || submitButton.disabled) {
                        return;
                    }
                    submitButton.click();
                });
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
                if (centerButton) {
                    centerButton.disabled = true;
                    centerButton.textContent = 'Đang quay';
                }

                var settledRotation = parseFloat(wheel.getAttribute('data-settled-rotation') || '0');
                if (isNaN(settledRotation)) {
                    settledRotation = 0;
                }
                wheel.style.setProperty('--spin-rotation', (settledRotation + 2880) + 'deg');
                wheel.classList.add('is-spinning');
                window.setTimeout(function () {
                    form.dataset.submitting = '1';
                    form.submit();
                }, 4300);
            });
        })();
    </script>
</body>
</html>
