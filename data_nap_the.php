<?php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$csrf_token = generate_csrf_token();

$history_card_payments = [];
$total_card_payments = 0;
$total_pages_card = 1;
$current_page_card = 1;
$payments_per_page = 5;

$history_bank_transfers = [];
$is_logged_in = isset($_SESSION['username']);
$account_username = $is_logged_in ? $_SESSION['username'] : '';
$user_avatar = $is_logged_in && isset($user_avatar) ? $user_avatar : '/images/default_avatar.png';
$display_player_name = $is_logged_in && isset($display_player_name) ? $display_player_name : '';
$user_vnd = $is_logged_in && isset($user_vnd) ? $user_vnd : 0;

$current_page_transfer = isset($_GET['page_transfer']) ? max(1, (int)$_GET['page_transfer']) : 1;
$transfers_per_page = 8;
$offset_transfer = ($current_page_transfer - 1) * $transfers_per_page;
$total_bank_transfers = 0;
$total_pages_transfer = 1;

if ($is_logged_in && isset($conn) && $conn instanceof mysqli) {
    $sql_count_transfer = "SELECT COUNT(*) AS total FROM bank_transfers WHERE username = ?";
    $stmt_count_transfer = $conn->prepare($sql_count_transfer);
    if ($stmt_count_transfer) {
        $stmt_count_transfer->bind_param("s", $account_username);
        $stmt_count_transfer->execute();
        $result_count_transfer = $stmt_count_transfer->get_result();
        $row_count_transfer = $result_count_transfer->fetch_assoc();
        $total_bank_transfers = (int)($row_count_transfer['total'] ?? 0);
        $stmt_count_transfer->close();
    } else {
        error_log("Loi prepare dem lich su nap thu cong: " . $conn->error);
    }

    $total_pages_transfer = max(1, (int)ceil($total_bank_transfers / $transfers_per_page));
    if ($current_page_transfer > $total_pages_transfer) {
        $current_page_transfer = $total_pages_transfer;
        $offset_transfer = ($current_page_transfer - 1) * $transfers_per_page;
    }

    $sql_transfer_history = "
        SELECT transaction_id, amount, description, status, created_at, is_credited
        FROM bank_transfers
        WHERE username = ?
        ORDER BY created_at DESC, id DESC
        LIMIT ?, ?
    ";
    $stmt_transfer_history = $conn->prepare($sql_transfer_history);
    if ($stmt_transfer_history) {
        $stmt_transfer_history->bind_param("sii", $account_username, $offset_transfer, $transfers_per_page);
        $stmt_transfer_history->execute();
        $result_transfer_history = $stmt_transfer_history->get_result();
        while ($row = $result_transfer_history->fetch_assoc()) {
            $history_bank_transfers[] = $row;
        }
        $stmt_transfer_history->close();
    } else {
        error_log("Loi prepare lich su nap thu cong: " . $conn->error);
    }
}
?>
