<?php
// settings.php
$ip_sv = "180.93.54.5";
$dbname_sv = "team2026";
$user_sv = "liodev";
$pass_sv = "liopass";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
?>
