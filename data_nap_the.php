<?php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$csrf_token = generate_csrf_token();

$is_logged_in = isset($_SESSION['username']);
$account_username = $is_logged_in ? $_SESSION['username'] : '';
$user_avatar = $is_logged_in && isset($user_avatar) ? $user_avatar : '/images/default_avatar.png';
$display_player_name = $is_logged_in && isset($display_player_name) ? $display_player_name : '';
$user_vnd = $is_logged_in && isset($user_vnd) ? $user_vnd : 0;
?>
