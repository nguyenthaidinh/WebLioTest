<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../connect.php';

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'success' => false,
    'message' => 'Khong the tao yeu cau nap tien.'
];

function manual_recharge_verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Yeu cau khong hop le.';
    echo json_encode($response);
    exit();
}

if (!manual_recharge_verify_csrf($_POST['csrf_token'] ?? '')) {
    $response['message'] = 'Phien bao mat khong hop le, vui long tai lai trang.';
    echo json_encode($response);
    exit();
}

$username = $_SESSION['username'] ?? '';
if ($username === '') {
    $response['message'] = 'Ban can dang nhap de gui yeu cau nap tien.';
    echo json_encode($response);
    exit();
}

$amount = (int)($_POST['amount'] ?? 0);
$transfer_note = trim((string)($_POST['transfer_note'] ?? ''));

if ($amount < 10000) {
    $response['message'] = 'So tien nap toi thieu la 10.000 VND.';
    echo json_encode($response);
    exit();
}

if ($amount > 100000000) {
    $response['message'] = 'So tien nap vuot gioi han cho phep.';
    echo json_encode($response);
    exit();
}

$note_length = function_exists('mb_strlen') ? mb_strlen($transfer_note, 'UTF-8') : strlen($transfer_note);
if ($note_length > 255) {
    $response['message'] = 'Ghi chu giao dich toi da 255 ky tu.';
    echo json_encode($response);
    exit();
}

if (!($conn instanceof mysqli)) {
    $response['message'] = 'Loi ket noi co so du lieu.';
    echo json_encode($response);
    exit();
}

$stmt_check_user = $conn->prepare("SELECT id FROM account WHERE username = ? LIMIT 1");
if (!$stmt_check_user) {
    $response['message'] = 'Khong the kiem tra tai khoan.';
    echo json_encode($response);
    exit();
}

$stmt_check_user->bind_param("s", $username);
$stmt_check_user->execute();
$stmt_check_user->store_result();
if ($stmt_check_user->num_rows === 0) {
    $stmt_check_user->close();
    $response['message'] = 'Tai khoan khong ton tai.';
    echo json_encode($response);
    exit();
}
$stmt_check_user->close();

$transaction_id = 'MANUAL_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
$description = $transfer_note !== ''
    ? $transfer_note
    : 'Yeu cau nap thu cong tu website';
$status = 'pending';
$sender = 'manual';
$is_credited = 0;

$stmt_insert = $conn->prepare("
    INSERT INTO bank_transfers (
        transaction_id, username, amount, description, status,
        sender_bank_name, created_at, is_credited
    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
");

if (!$stmt_insert) {
    $response['message'] = 'Khong the tao yeu cau nap tien.';
    echo json_encode($response);
    exit();
}

$stmt_insert->bind_param(
    "ssisssi",
    $transaction_id,
    $username,
    $amount,
    $description,
    $status,
    $sender,
    $is_credited
);

if ($stmt_insert->execute()) {
    $response['success'] = true;
    $response['message'] = 'Da gui yeu cau nap tien. Vui long cho admin duyet.';
    $response['transaction_id'] = $transaction_id;
} else {
    $response['message'] = 'Loi khi luu yeu cau nap tien: ' . $stmt_insert->error;
}

$stmt_insert->close();

if (isset($conn) && $conn->ping()) {
    $conn->close();
}

echo json_encode($response);
?>
