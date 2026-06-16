<?php
include_once '../cauhinh.php';
include_once '../connect.php';
include_once '../config.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_IP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $_IP = trim($forwarded_ips[0]);
}

$_login = null;
$_user = $_SESSION['account'] ?? $_SESSION['username'] ?? null;
$_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

if ($_user !== null && !isset($_SESSION['account'])) {
    $_SESSION['account'] = $_user;
}
if ($_user !== null && !isset($_SESSION['username'])) {
    $_SESSION['username'] = $_user;
}
if ($_user_id !== null && !isset($_SESSION['id'])) {
    $_SESSION['id'] = $_user_id;
}
if ($_user_id !== null && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_user_id;
}

if ($_user !== null || $_user_id !== null) {
    if ($_user_id !== null) {
        $stmt = $conn->prepare("SELECT id, username, password, gioithieu, admin, is_admin, vnd, tongnap, active FROM account WHERE id = ? LIMIT 1");
        if (!$stmt) {
            die("Khong the kiem tra quyen admin.");
        }
        $stmt->bind_param("i", $_user_id);
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, gioithieu, admin, is_admin, vnd, tongnap, active FROM account WHERE username = ? LIMIT 1");
        if (!$stmt) {
            die("Khong the kiem tra quyen admin.");
        }
        $stmt->bind_param("s", $_user);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $user_arr = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user_arr) {
        header("Location: /app/logout.php");
        exit();
    }

    $_login = "on";
    $_username = htmlspecialchars($user_arr['username'] ?? '');
    $_password = htmlspecialchars($user_arr['password'] ?? '');
    $_gioithieu = htmlspecialchars($user_arr['gioithieu'] ?? '');
    $_admin = (int)($user_arr['admin'] ?? 0);
    $_is_admin = (int)($user_arr['is_admin'] ?? 0);
    $_coin = (int)($user_arr['vnd'] ?? 0);
    $_tcoin = htmlspecialchars((string)($user_arr['tongnap'] ?? 0));
    $_status = (int)($user_arr['active'] ?? 0);

    $_SESSION['id'] = (int)$user_arr['id'];
    $_SESSION['user_id'] = (int)$user_arr['id'];
    $_SESSION['account'] = $user_arr['username'];
    $_SESSION['username'] = $user_arr['username'];
    $_SESSION['is_admin'] = ($_admin === 1 || $_is_admin === 1) ? 1 : 0;

    switch ($_status) {
        case 1:
            $isPremium_name = '<span style="color:green;font-weight: bold;">Da kich hoat</span>';
            break;
        case 0:
            $isPremium_name = '<span style="color:#007BFF;font-weight: bold;"><a href="/active">Kich hoat ngay</a></span>';
            break;
        case -1:
            $isPremium_name = '<span style="color:red;font-weight: bold;">Dang bi khoa</span>';
            break;
        default:
            $isPremium_name = '';
            break;
    }

    if ($_admin !== 1 && $_is_admin !== 1) {
        echo '<script>alert("Ban khong phai la admin!"); window.location.href="../index.php"</script>';
        exit();
    }
} else {
    $_login = null;
}

if (isset($_GET['out'])) {
    session_destroy();
    header("Location:/");
    exit();
}
?>
