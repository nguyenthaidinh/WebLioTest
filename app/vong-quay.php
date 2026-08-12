<?php
$__lucky_ajax_bootstrap = (
    strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    || strpos(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json') !== false
    || (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1')
    || (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1')
);
$__lucky_ajax_response_sent = false;

if ($__lucky_ajax_bootstrap) {
    ob_start();
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    register_shutdown_function(function () use (&$__lucky_ajax_response_sent) {
        $error = error_get_last();
        if (
            $error
            && !$__lucky_ajax_response_sent
            && in_array((int)$error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)
        ) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Máy chủ lỗi khi xử lý vòng quay. Vui lòng thử lại hoặc báo admin kiểm tra log.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    });
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../forum_data.php';
include_once __DIR__ . '/account_info.php';
require_once __DIR__ . '/../lucky_rewards.php';

if ($__lucky_ajax_bootstrap) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

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

function lucky_format_degrees($degrees) {
    return rtrim(rtrim(number_format((float)$degrees, 4, '.', ''), '0'), '.');
}

function lucky_is_ajax_request() {
    $requested_with = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));

    return $requested_with === 'xmlhttprequest'
        || strpos($accept, 'application/json') !== false
        || (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1')
        || (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1');
}

function lucky_json_response($payload, $status_code = 200) {
    global $__lucky_ajax_response_sent;

    $__lucky_ajax_response_sent = true;
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $json_flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $json_flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($payload, $json_flags);
    if ($json === false) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo '{"ok":false,"message":"Không thể mã hóa dữ liệu vòng quay."}';
        exit();
    }

    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo $json;
    exit();
}

function lucky_build_wheel_segments($rewards) {
    $total_weight = lucky_rewards_total_weight($rewards);
    if ($total_weight <= 0) {
        return [];
    }

    $segments = [];
    $cursor = 0.0;
    foreach ($rewards as $reward) {
        $weight = max(0, (int)$reward['weight']);
        if ($weight <= 0) {
            continue;
        }

        $degrees = ($weight / $total_weight) * 360;
        $start = $cursor;
        $end = $cursor + $degrees;
        $segments[] = [
            'reward_key' => (string)$reward['reward_key'],
            'label' => (string)$reward['label'],
            'amount' => (int)$reward['amount'],
            'color' => (string)$reward['color'],
            'weight' => $weight,
            'start' => $start,
            'end' => $end,
            'degrees' => $degrees,
            'center' => $start + ($degrees / 2),
        ];
        $cursor = $end;
    }

    if (!empty($segments)) {
        $last_index = count($segments) - 1;
        $segments[$last_index]['end'] = 360.0;
        $segments[$last_index]['degrees'] = $segments[$last_index]['end'] - $segments[$last_index]['start'];
        $segments[$last_index]['center'] = $segments[$last_index]['start'] + ($segments[$last_index]['degrees'] / 2);
    }

    return $segments;
}

function lucky_find_wheel_segment($segments, $reward_key) {
    foreach ($segments as $segment) {
        if ((string)$segment['reward_key'] === (string)$reward_key) {
            return $segment;
        }
    }

    return null;
}

function lucky_wheel_target_for_reward($segments, $reward_key) {
    $segment = lucky_find_wheel_segment($segments, $reward_key);
    if ($segment === null) {
        return null;
    }

    $start = (float)$segment['start'];
    $end = (float)$segment['end'];
    $span = max(0.0, $end - $start);
    $landing_angle = $start + ($span / 2);

    if ($span > 3) {
        $padding = min(12.0, max(1.2, $span * 0.14));
        if (($end - $padding) > ($start + $padding)) {
            $min = (int)round(($start + $padding) * 1000);
            $max = (int)round(($end - $padding) * 1000);
            $landing_angle = random_int($min, $max) / 1000;
        }
    }

    $target_modulo = fmod(360 - fmod($landing_angle, 360), 360);
    if ($target_modulo < 0) {
        $target_modulo += 360;
    }

    $turns = random_int(7, 10);

    return [
        'landing_angle' => lucky_format_degrees($landing_angle),
        'target_rotation' => lucky_format_degrees($target_modulo),
        'turns' => $turns,
    ];
}

function lucky_segment_json($segments) {
    $data = [];
    foreach ($segments as $segment) {
        $data[] = [
            'reward_key' => $segment['reward_key'],
            'label' => $segment['label'],
            'amount' => (int)$segment['amount'],
            'start' => (float)$segment['start'],
            'end' => (float)$segment['end'],
            'center' => (float)$segment['center'],
        ];
    }

    return $data;
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
        $stmt_account = $conn->prepare("SELECT luotquay, thoi_vang FROM account WHERE id = ? FOR UPDATE");
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
        $current_gold = (int)($account['thoi_vang'] ?? 0);
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
        $_SESSION['thoi_vang'] = $current_gold + $reward_amount;

        return [
            'reward' => $reward,
            'remaining_spins' => $current_spins - 1,
            'pending_gold' => $current_gold + $reward_amount,
        ];
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

$wheel_segments = lucky_build_wheel_segments($lucky_rewards);
$is_ajax_request = lucky_is_ajax_request();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_logged_in) {
        if ($is_ajax_request) {
            lucky_json_response(['ok' => false, 'message' => 'Bạn cần đăng nhập để thao tác.'], 401);
        }

        lucky_set_message('Bạn cần đăng nhập để thao tác.', 'error');
        header("Location: /app/vong-quay.php");
        exit();
    }

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
            if ($is_ajax_request) {
                lucky_json_response([
                    'ok' => true,
                    'message' => 'Điểm danh thành công, bạn nhận ' . $spin_reward . ' lượt quay.',
                    'state' => lucky_get_account_state($conn, $account_id),
                    'checked_in_today' => true,
                ]);
            }
        } elseif ($action === 'lucky_spin') {
            $spin = lucky_handle_spin($conn, $account_id, $lucky_rewards);
            $reward = $spin['reward'];
            $visual_target = lucky_wheel_target_for_reward($wheel_segments, $reward['reward_key']);
            if ($visual_target === null) {
                throw new Exception('Không thể xác định vị trí phần thưởng trên vòng quay.');
            }

            if ((int)$reward['amount'] > 0) {
                $result_payload = [
                    'type' => 'win',
                    'reward_key' => $reward['reward_key'],
                    'label' => $reward['label'],
                    'amount' => (int)$reward['amount'],
                ];
                lucky_set_message(
                    'Chúc mừng! Bạn trúng ' . $reward['label'] . '.',
                    'success',
                    $result_payload
                );
            } else {
                $result_payload = [
                    'type' => 'miss',
                    'reward_key' => $reward['reward_key'],
                    'label' => $reward['label'],
                    'amount' => 0,
                ];
                lucky_set_message(
                    'Chúc may mắn lần sau.',
                    'warning',
                    $result_payload
                );
            }

            if ($is_ajax_request) {
                lucky_json_response([
                    'ok' => true,
                    'message' => (int)$reward['amount'] > 0
                        ? 'Chúc mừng! Bạn trúng ' . $reward['label'] . '.'
                        : 'Chúc may mắn lần sau.',
                    'result' => array_merge($result_payload, $visual_target),
                    'state' => [
                        'luotquay' => (int)$spin['remaining_spins'],
                        'thoi_vang' => (int)$spin['pending_gold'],
                    ],
                ]);
            }
        } elseif ($action === 'withdraw_gold') {
            $withdrawn_amount = lucky_handle_withdraw_gold($conn, $account_id, $current_player_id);
            lucky_set_message('Rút thành công ' . number_format($withdrawn_amount, 0, ',', '.') . ' TV vào túi đồ.', 'success');
            if ($is_ajax_request) {
                lucky_json_response([
                    'ok' => true,
                    'message' => 'Rút thành công ' . number_format($withdrawn_amount, 0, ',', '.') . ' TV vào túi đồ.',
                    'state' => lucky_get_account_state($conn, $account_id),
                ]);
            }
        } else {
            throw new Exception('Thao tác không hợp lệ.');
        }
    } catch (Exception $e) {
        if ($is_ajax_request) {
            lucky_json_response(['ok' => false, 'message' => $e->getMessage()], 400);
        }

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
foreach ($wheel_segments as $segment) {
    $wheel_gradient_parts[] = $segment['color'] . ' ' . lucky_format_degrees($segment['start']) . 'deg ' . lucky_format_degrees($segment['end']) . 'deg';
}
$wheel_gradient = !empty($wheel_gradient_parts) ? implode(', ', $wheel_gradient_parts) : '#64748b 0deg 360deg';
$wheel_segment_count = max(1, count($wheel_segments));
$wheel_result_key = is_array($spin_result) ? (string)($spin_result['reward_key'] ?? '') : '';
$wheel_settled_target = $wheel_result_key !== '' ? lucky_wheel_target_for_reward($wheel_segments, $wheel_result_key) : null;
$wheel_settled_rotation_text = $wheel_settled_target !== null ? lucky_format_degrees($wheel_settled_target['target_rotation']) : '';
$wheel_class = 'wheel' . ($wheel_settled_rotation_text !== '' ? ' has-result' : '');
$wheel_config = [
    'segments' => lucky_segment_json($wheel_segments),
    'spinDurationMs' => 6200,
];
$wheel_config_json_flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $wheel_config_json_flags |= JSON_INVALID_UTF8_SUBSTITUTE;
}
$wheel_config_json = json_encode($wheel_config, $wheel_config_json_flags);
if ($wheel_config_json === false) {
    $wheel_config_json = '{"segments":[],"spinDurationMs":6200}';
}
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
            max-width: 820px;
            margin: 0 auto;
            color: #2d1600;
        }
        .lucky-panel {
            background: linear-gradient(180deg, #fffaf0 0%, #fff7e4 52%, #fff0cf 100%);
            border: 1px solid #efbd67;
            border-radius: 8px;
            padding: 18px;
            margin: 10px;
            text-align: center;
            box-shadow: 0 14px 32px rgba(124, 45, 18, 0.18);
        }
        .lucky-panel h2 {
            color: #7c2d12;
            font-size: 22px;
            margin: 0 0 10px;
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
            border-radius: 8px;
            padding: 7px 11px;
            box-shadow: 0 2px 5px rgba(124, 45, 18, 0.08);
        }
        .lucky-status strong {
            color: #b45309;
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
            width: min(84vw, 410px);
            aspect-ratio: 1;
            margin: 16px auto 10px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.85) 0 50%, rgba(251,191,36,0.22) 51% 64%, transparent 65%);
            padding: 12px;
            box-sizing: border-box;
        }
        .wheel-pointer {
            position: absolute;
            top: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 16px solid transparent;
            border-right: 16px solid transparent;
            border-top: 34px solid #dc2626;
            filter: drop-shadow(0 3px 2px rgba(0,0,0,0.28));
            z-index: 8;
            transform-origin: 50% 2px;
        }
        .wheel-pointer::after {
            content: "";
            position: absolute;
            left: -5px;
            top: -38px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #7f1d1d;
        }
        .wheel-area.is-spinning .wheel-pointer {
            animation: pointerTick 130ms linear infinite;
        }
        @keyframes pointerTick {
            0%, 100% { transform: translateX(-50%) rotate(0deg); }
            45% { transform: translateX(-50%) rotate(-9deg); }
        }
        .wheel {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            background:
                radial-gradient(circle at 50% 36%, rgba(255,255,255,0.42), transparent 24%),
                conic-gradient(from -90deg, <?php echo htmlspecialchars($wheel_gradient); ?>);
            border: 12px solid #6b2708;
            box-shadow:
                inset 0 0 0 5px rgba(255,255,255,0.65),
                inset 0 0 32px rgba(0,0,0,0.18),
                0 16px 30px rgba(124,45,18,0.34);
            transform: rotate(0deg);
            will-change: transform;
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
        .wheel::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background:
                radial-gradient(circle at 35% 26%, rgba(255,255,255,0.34), transparent 18%),
                radial-gradient(circle at 50% 50%, transparent 0 55%, rgba(0,0,0,0.16) 100%);
            pointer-events: none;
            z-index: 3;
        }
        .wheel-separator {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 50%;
            height: 1px;
            background: rgba(255,255,255,0.74);
            transform: rotate(calc(var(--angle) - 90deg));
            transform-origin: 0 50%;
            z-index: 1;
            pointer-events: none;
        }
        .wheel-label {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 92px;
            min-height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translate(-50%, -50%) rotate(var(--angle)) translateY(-145px) rotate(90deg);
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
            z-index: 5;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 28%, #fed7aa 0%, #fb923c 28%, #ea580c 100%);
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
        .wheel-area.is-spinning .wheel-center,
        .wheel-area.is-settling .wheel-center {
            pointer-events: none;
        }
        .spin-live-status {
            min-height: 28px;
            margin: 8px auto 4px;
            color: #7c2d12;
            font-size: 12px;
            font-weight: 900;
        }
        .spin-live-status.is-active {
            color: #b45309;
        }
        .reward-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin: 8px auto 14px;
            max-width: 620px;
        }
        .reward-pill {
            align-items: center;
            background: rgba(255,255,255,0.78);
            border: 1px solid #f0c27b;
            border-radius: 8px;
            color: #5b2b05;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            gap: 5px;
            padding: 5px 8px;
        }
        .reward-pill i {
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.75);
            display: inline-block;
            height: 9px;
            width: 9px;
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
            .lucky-panel {
                padding: 13px;
            }
            .wheel-area {
                padding: 9px;
            }
            .wheel-label {
                width: 64px;
                font-size: 9px;
                transform: translate(-50%, -50%) rotate(var(--angle)) translateY(-116px) rotate(90deg);
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
                                            <span>Nhân vật: <strong><?php echo htmlspecialchars($player_name); ?></strong></span>
                                            <span>Lượt quay: <strong id="remainingSpinsText"><?php echo number_format($remaining_spins, 0, ',', '.'); ?></strong></span>
                                            <span>TV chờ rút: <strong id="pendingGoldText"><?php echo number_format($pending_gold, 0, ',', '.'); ?></strong></span>
                                            <span id="checkinStatusText"><?php echo $checked_in_today ? 'Đã điểm danh hôm nay' : 'Chưa điểm danh hôm nay'; ?></span>
                                        </div>
                                        <p class="lucky-note">Mỗi ngày điểm danh nhận 1 lượt quay. Tích lũy 10.000 = 1 lượt quay. Trúng TV sẽ vào kho chờ rút; thoát game trước khi rút TV.</p>

                                        <?php if ($checkin_table_error !== ''): ?>
                                            <div class="message error"><?php echo htmlspecialchars($checkin_table_error); ?></div>
                                        <?php endif; ?>

                                        <div id="spinResultSlot">
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
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="result-eyebrow">Kết quả quay</div>
                                                        <div class="result-title">Chúc bạn may mắn lần sau</div>
                                                        <div class="result-prize"><?php echo htmlspecialchars($result_label ?: 'Chúc may mắn'); ?></div>
                                                        <div class="result-desc">Lần này chưa trúng TV, bạn có thể điểm danh hoặc tích lũy nạp để nhận thêm lượt quay.</div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($message !== ''): ?>
                                                <div class="message <?php echo htmlspecialchars($message_type); ?>">
                                                    <?php echo htmlspecialchars($message); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

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

                                        <div id="withdrawGoldSlot">
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
                                        </div>

                                        <div class="wheel-area" id="wheelArea">
                                            <div class="wheel-pointer"></div>
                                            <div id="luckyWheel" class="<?php echo htmlspecialchars($wheel_class); ?>" data-current-rotation="<?php echo htmlspecialchars($wheel_settled_rotation_text !== '' ? $wheel_settled_rotation_text : '0'); ?>"<?php if ($wheel_settled_rotation_text !== ''): ?> style="--settled-rotation: <?php echo htmlspecialchars($wheel_settled_rotation_text); ?>deg;" data-settled-rotation="<?php echo htmlspecialchars($wheel_settled_rotation_text); ?>"<?php endif; ?>>
                                                <?php foreach ($wheel_segments as $segment): ?>
                                                    <span class="wheel-separator" style="--angle: <?php echo htmlspecialchars(lucky_format_degrees($segment['start'])); ?>deg;"></span>
                                                    <?php if ((float)$segment['degrees'] >= 7): ?>
                                                        <span class="wheel-label" style="--angle: <?php echo htmlspecialchars(lucky_format_degrees($segment['center'])); ?>deg;">
                                                            <?php echo htmlspecialchars($segment['label']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <button id="wheelCenterSpin" class="wheel-center" type="button" <?php echo $remaining_spins <= 0 ? 'disabled' : ''; ?>>Quay</button>
                                        </div>
                                        <div class="spin-live-status" id="spinLiveStatus"></div>

                                        <div class="reward-pills">
                                            <?php foreach ($wheel_segments as $segment): ?>
                                                <span class="reward-pill"><i style="background: <?php echo htmlspecialchars($segment['color']); ?>"></i><?php echo htmlspecialchars($segment['label']); ?></span>
                                            <?php endforeach; ?>
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
        window.luckyWheelConfig = <?php echo $wheel_config_json; ?>;
    </script>
    <script>
        (function () {
            var form = document.getElementById('luckySpinForm');
            var wheel = document.getElementById('luckyWheel');
            var wheelArea = document.getElementById('wheelArea');
            var centerButton = document.getElementById('wheelCenterSpin');
            var resultSlot = document.getElementById('spinResultSlot');
            var liveStatus = document.getElementById('spinLiveStatus');
            var remainingSpinsText = document.getElementById('remainingSpinsText');
            var pendingGoldText = document.getElementById('pendingGoldText');
            var withdrawGoldSlot = document.getElementById('withdrawGoldSlot');
            var config = window.luckyWheelConfig || {};
            var baseDuration = Number(config.spinDurationMs || 6200);
            var currentRotation = Number(wheel ? wheel.getAttribute('data-current-rotation') : 0) || 0;

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatNumber(value) {
                return new Intl.NumberFormat('vi-VN').format(Number(value || 0));
            }

            function normalizeDegrees(value) {
                var result = Number(value) % 360;
                return result < 0 ? result + 360 : result;
            }

            function secureRandomUnit() {
                if (window.crypto && window.crypto.getRandomValues) {
                    var data = new Uint32Array(1);
                    window.crypto.getRandomValues(data);
                    return data[0] / 4294967295;
                }

                return Math.random();
            }

            function setLiveStatus(text, active) {
                if (!liveStatus) {
                    return;
                }

                liveStatus.textContent = text || '';
                liveStatus.classList.toggle('is-active', !!active);
            }

            function setSpinningState(isSpinning, canSpinAgain) {
                var submitButton = form ? form.querySelector('button[type="submit"]') : null;
                if (submitButton) {
                    submitButton.disabled = isSpinning || !canSpinAgain;
                    submitButton.textContent = isSpinning ? 'Đang quay...' : 'Quay ngay';
                }
                if (centerButton) {
                    centerButton.disabled = isSpinning || !canSpinAgain;
                    centerButton.textContent = isSpinning ? 'Đang quay' : 'Quay';
                }
                if (wheelArea) {
                    wheelArea.classList.toggle('is-spinning', isSpinning);
                }
                if (wheel) {
                    wheel.classList.toggle('is-spinning', isSpinning);
                }
            }

            function updateState(state) {
                if (!state) {
                    return;
                }

                var remaining = Number(state.luotquay || 0);
                var pendingGold = Number(state.thoi_vang || 0);
                if (remainingSpinsText) {
                    remainingSpinsText.textContent = formatNumber(remaining);
                }
                if (pendingGoldText) {
                    pendingGoldText.textContent = formatNumber(pendingGold);
                }
                renderWithdrawBox(pendingGold);
            }

            function renderWithdrawBox(pendingGold) {
                if (!withdrawGoldSlot) {
                    return;
                }

                if (pendingGold <= 0) {
                    withdrawGoldSlot.innerHTML = '';
                    return;
                }

                var token = form ? form.querySelector('input[name="csrf_token"]').value : '';
                withdrawGoldSlot.innerHTML =
                    '<div class="withdraw-box" id="withdrawGold">' +
                        '<strong>Rút thỏi vàng vào túi đồ</strong>' +
                        '<form class="withdraw-form" method="post" action="/app/vong-quay.php">' +
                            '<input type="hidden" name="action" value="withdraw_gold">' +
                            '<input type="hidden" name="csrf_token" value="' + escapeHtml(token) + '">' +
                            '<input name="withdraw_amount" type="number" min="1" max="' + pendingGold + '" value="' + pendingGold + '" required>' +
                            '<button class="withdraw-button" type="submit">Rút</button>' +
                        '</form>' +
                    '</div>';
            }

            function resultHtml(result) {
                var label = escapeHtml(result.label || 'Chúc may mắn');
                var amount = Number(result.amount || 0);

                if (result.type === 'win') {
                    return '' +
                        '<div class="spin-result-card win" id="spinResult">' +
                            '<div class="result-eyebrow">Kết quả quay</div>' +
                            '<div class="result-title">Chúc mừng bạn đã trúng</div>' +
                            '<div class="result-prize">' + label + '</div>' +
                            '<div class="result-desc">' + formatNumber(amount) + ' TV đã được cộng vào kho chờ rút. Hãy thoát game trước khi rút vào túi đồ.</div>' +
                            '<div class="result-actions"><a class="result-link" href="#withdrawGold">Rút thỏi vàng</a></div>' +
                        '</div>';
                }

                return '' +
                    '<div class="spin-result-card miss" id="spinResult">' +
                        '<div class="result-eyebrow">Kết quả quay</div>' +
                        '<div class="result-title">Chúc bạn may mắn lần sau</div>' +
                        '<div class="result-prize">' + label + '</div>' +
                        '<div class="result-desc">Lần này chưa trúng TV, bạn có thể điểm danh hoặc tích lũy nạp để nhận thêm lượt quay.</div>' +
                    '</div>';
            }

            function showMessage(message, type) {
                if (!resultSlot) {
                    return;
                }

                resultSlot.innerHTML = '<div class="message ' + escapeHtml(type || 'error') + '">' + escapeHtml(message) + '</div>';
            }

            function easeOutPhysical(t) {
                return 1 - Math.pow(1 - t, 4.6);
            }

            function animateToRotation(finalRotation, duration) {
                return new Promise(function (resolve) {
                    var startRotation = currentRotation;
                    var travel = finalRotation - startRotation;
                    var startTime = null;

                    function frame(now) {
                        if (startTime === null) {
                            startTime = now;
                        }

                        var progress = Math.min(1, (now - startTime) / duration);
                        var eased = easeOutPhysical(progress);
                        var wobble = 0;
                        if (progress > 0.84 && progress < 0.995) {
                            var settle = (progress - 0.84) / 0.155;
                            wobble = Math.sin(settle * Math.PI * 5) * (1 - progress) * 2.6;
                        }

                        var rotation = startRotation + (travel * eased) + wobble;
                        wheel.style.transform = 'rotate(' + rotation + 'deg)';

                        if (progress < 1) {
                            window.requestAnimationFrame(frame);
                            return;
                        }

                        currentRotation = finalRotation;
                        wheel.style.transform = 'rotate(' + finalRotation + 'deg)';
                        wheel.style.setProperty('--settled-rotation', finalRotation + 'deg');
                        wheel.setAttribute('data-current-rotation', String(finalRotation));
                        wheel.setAttribute('data-settled-rotation', String(finalRotation));
                        wheel.classList.add('has-result');
                        resolve();
                    }

                    window.requestAnimationFrame(frame);
                });
            }

            function nextFinalRotation(targetModulo, turns) {
                var currentModulo = normalizeDegrees(currentRotation);
                var delta = normalizeDegrees(Number(targetModulo) - currentModulo);
                return currentRotation + (Number(turns || 8) * 360) + delta;
            }

            var existingResult = document.getElementById('spinResult');
            if (existingResult) {
                window.setTimeout(function () {
                    existingResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
                event.preventDefault();
                if (form.dataset.spinning === '1') {
                    return;
                }

                var submitButton = form.querySelector('button[type="submit"]');
                if (submitButton && submitButton.disabled) {
                    return;
                }

                form.dataset.spinning = '1';
                wheel.classList.remove('has-result');
                setSpinningState(true, true);
                setLiveStatus('Đang quay... chờ bánh xe dừng để nhận kết quả.', true);

                var formData = new FormData(form);
                formData.set('ajax', '1');
                fetch('/app/vong-quay.php?ajax=1', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.text().then(function (text) {
                            var data;
                            try {
                                data = text ? JSON.parse(text) : {};
                            } catch (error) {
                                var looksLikeHtml = /<!doctype|<html|<head|<body|<style|@import/i.test(text || '');
                                var cleanText = looksLikeHtml
                                    ? ''
                                    : text
                                        .replace(/<br\s*\/?>/gi, '\n')
                                        .replace(/<[^>]*>/g, ' ')
                                        .replace(/\s+/g, ' ')
                                        .trim();
                                data = {
                                    ok: false,
                                    message: cleanText
                                        ? cleanText.slice(0, 220)
                                        : 'Máy chủ đang trả về trang HTML thay vì kết quả quay. Vui lòng tải lại trang rồi thử lại.'
                                };
                            }

                            if (!response.ok || !data.ok) {
                                throw new Error(data.message || 'Không thể quay lúc này.');
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        var result = data.result || {};
                        var targetModulo = Number(result.target_rotation || 0);
                        var turns = Number(result.turns || 8);
                        var duration = baseDuration + Math.round(secureRandomUnit() * 700);
                        var finalRotation = nextFinalRotation(targetModulo, turns);

                        return animateToRotation(finalRotation, duration).then(function () {
                            updateState(data.state);
                            if (resultSlot) {
                                resultSlot.innerHTML = resultHtml(result);
                                var card = document.getElementById('spinResult');
                                if (card) {
                                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }
                            setLiveStatus(result.type === 'win' ? 'Đã cộng thưởng vào kho chờ rút.' : 'Bánh xe đã dừng.', false);
                            return data;
                        });
                    })
                    .then(function (data) {
                        var remaining = Number((data.state || {}).luotquay || 0);
                        setSpinningState(false, remaining > 0);
                    })
                    .catch(function (error) {
                        showMessage(error.message || 'Không thể quay lúc này.', 'error');
                        setLiveStatus('', false);
                        var currentRemaining = remainingSpinsText ? Number(String(remainingSpinsText.textContent).replace(/\D/g, '')) : 0;
                        setSpinningState(false, currentRemaining > 0);
                    })
                    .finally(function () {
                        form.dataset.spinning = '0';
                    });
            });
        })();
    </script>
</body>
</html>
