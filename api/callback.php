<?php
function log_disabled_card_callback($message, $type = 'INFO') {
    $log_file = __DIR__ . '/card_api_debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($log_file, $timestamp . " [" . strtoupper($type) . "] " . $message . "\n", FILE_APPEND);
}

log_disabled_card_callback("Callback ignored because manual recharge mode is enabled. Raw POST Data: " . print_r($_POST, true));

http_response_code(200);
echo "Manual recharge mode";
?>
