<?php
// settings.php
$ip_sv = "127.0.0.1";
$dbname_sv = "team2026";
$user_sv = "nro";
$pass_sv = "nropass";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
?>
