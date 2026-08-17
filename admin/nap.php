<?php
include_once 'set.php';
include_once 'connect.php';
include_once '../recharge_bonus.php';

if ($_login == null) {
    header("Location: /app/login.php");
    exit();
}

if (empty($_SESSION['admin_recharge_csrf'])) {
    $_SESSION['admin_recharge_csrf'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['admin_recharge_csrf'];
$_alert = '';
$credit_history_ready = false;

try {
    $credit_history_ready = recharge_ensure_credit_history_table($conn);
} catch (Throwable $e) {
    error_log('Không thể khởi tạo bảng recharge_credit_history: ' . $e->getMessage());
}

if (!$credit_history_ready) {
    $_alert = admin_set_alert('Chưa thể khởi tạo bảng đối soát nạp tiền. Tạm khóa thao tác duyệt để tránh cộng tiền thiếu lịch sử.', 'error');
}

function admin_recharge_status_label($status, $is_credited = 0) {
    $status = strtolower((string)$status);
    if ($is_credited || $status === 'success') {
        return ['Đã duyệt', 'success'];
    }
    if ($status === 'pending') {
        return ['Chờ duyệt', 'pending'];
    }
    if ($status === 'rejected') {
        return ['Từ chối', 'rejected'];
    }
    if ($status === 'failed') {
        return ['Thất bại', 'rejected'];
    }
    return ['Khác', 'unknown'];
}

function admin_set_alert($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-error';
    return '<div class="admin-alert ' . $class . '">' . htmlspecialchars($message) . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $credit_history_ready) {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $posted_token)) {
        $_alert = admin_set_alert('Phiên bảo mật không hợp lệ.', 'error');
    } else {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($request_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $_alert = admin_set_alert('Yêu cầu không hợp lệ.', 'error');
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE bank_transfers SET status = 'rejected' WHERE id = ? AND is_credited = 0");
            if ($stmt) {
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $_alert = $stmt->affected_rows > 0
                    ? admin_set_alert('Đã từ chối yêu cầu nạp tiền.')
                    : admin_set_alert('Không thể từ chối yêu cầu này.', 'error');
                $stmt->close();
            } else {
                $_alert = admin_set_alert('Lỗi prepare từ chối: ' . $conn->error, 'error');
            }
        } elseif ($action === 'approve') {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("SELECT username, amount, status, is_credited FROM bank_transfers WHERE id = ? FOR UPDATE");
                if (!$stmt) {
                    throw new Exception('Lỗi prepare lấy yêu cầu: ' . $conn->error);
                }
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $request = $result->fetch_assoc();
                $stmt->close();

                if (!$request) {
                    throw new Exception('Không tìm thấy yêu cầu nạp tiền.');
                }
                if ((int)$request['is_credited'] === 1 || strtolower((string)$request['status']) === 'success') {
                    throw new Exception('Yêu cầu này đã được duyệt trước đó.');
                }

                $amount = (int)$request['amount'];
                $username = $request['username'];
                if ($amount <= 0 || $username === '') {
                    throw new Exception('Du lieu yeu cau khong hop le.');
                }
                $event_amount = recharge_event_amount($amount);
                $total_recharge_increment = $amount;
                $default_bonus_spins = recharge_default_bonus_spins($amount);
                $posted_bonus_spins = $_POST['bonus_spins'] ?? $default_bonus_spins;
                if (is_array($posted_bonus_spins)) {
                    throw new Exception('Số lượt quay không hợp lệ.');
                }
                $bonus_spins = filter_var($posted_bonus_spins, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                if ($bonus_spins === false || $bonus_spins > 1000000) {
                    throw new Exception('Số lượt quay phải là số nguyên từ 0 đến 1.000.000.');
                }

                $bonus_rate = recharge_bonus_rate($amount);
                $bonus_amount = recharge_bonus_amount($amount);
                $credit_amount = recharge_credit_amount($amount);

                $stmt_update_account = $conn->prepare("UPDATE account SET vnd = vnd + ?, tongnap = tongnap + ?, luotquay = luotquay + ? WHERE username = ?");
                if (!$stmt_update_account) {
                    throw new Exception('Lỗi prepare cộng tiền: ' . $conn->error);
                }
                $stmt_update_account->bind_param("iiis", $credit_amount, $total_recharge_increment, $bonus_spins, $username);
                $stmt_update_account->execute();
                if ($stmt_update_account->affected_rows === 0) {
                    throw new Exception('Không tìm thấy tài khoản để cộng tiền.');
                }
                $stmt_update_account->close();

                $event_multiplier = recharge_event_multiplier();
                $credited_by = $_username ?? 'admin';
                $stmt_insert_history = $conn->prepare("
                    INSERT INTO recharge_credit_history (
                        bank_transfer_id, paid_amount, event_multiplier, event_amount,
                        bonus_rate, bonus_amount, credited_amount, total_recharge_increment,
                        bonus_spins, credited_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt_insert_history) {
                    throw new Exception('Lỗi chuẩn bị lưu đối soát nạp tiền: ' . $conn->error);
                }
                $stmt_insert_history->bind_param(
                    "iiiiiiiiis",
                    $request_id,
                    $amount,
                    $event_multiplier,
                    $event_amount,
                    $bonus_rate,
                    $bonus_amount,
                    $credit_amount,
                    $total_recharge_increment,
                    $bonus_spins,
                    $credited_by
                );
                $stmt_insert_history->execute();
                $stmt_insert_history->close();

                $stmt_update_request = $conn->prepare("UPDATE bank_transfers SET status = 'success', is_credited = 1 WHERE id = ? AND is_credited = 0");
                if (!$stmt_update_request) {
                    throw new Exception('Lỗi prepare cập nhật yêu cầu: ' . $conn->error);
                }
                $stmt_update_request->bind_param("i", $request_id);
                $stmt_update_request->execute();
                if ($stmt_update_request->affected_rows !== 1) {
                    $stmt_update_request->close();
                    throw new Exception('Yêu cầu đã được xử lý hoặc không thể cập nhật trạng thái.');
                }
                $stmt_update_request->close();

                $conn->commit();
                $_alert = admin_set_alert(
                    'Đã duyệt nạp ' . number_format($amount, 0, ',', '.') . ' VND cho ' . $username
                    . ': x2 thành ' . number_format($event_amount, 0, ',', '.') . ' VND'
                    . ', khuyến mãi ' . $bonus_rate . '% trên số sau x2 = +' . number_format($bonus_amount, 0, ',', '.') . ' VND'
                    . ', tổng cộng ' . number_format($credit_amount, 0, ',', '.') . ' VND.'
                    . ' Tổng nạp thực tế +' . number_format($total_recharge_increment, 0, ',', '.')
                    . ' và thêm ' . number_format($bonus_spins, 0, ',', '.') . ' lượt quay.'
                );
            } catch (Exception $e) {
                $conn->rollback();
                $_alert = admin_set_alert($e->getMessage(), 'error');
            }
        }
    }
}

$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'success', 'rejected', 'failed', 'all'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'pending';
}

$where_sql = '';
if ($status_filter !== 'all') {
    $where_sql = "WHERE b.status = '" . $conn->real_escape_string($status_filter) . "'";
}

$stats = [
    'pending' => 0,
    'success' => 0,
    'rejected' => 0,
];
$stats_result = $conn->query("SELECT status, COUNT(*) AS total FROM bank_transfers GROUP BY status");
if ($stats_result) {
    while ($row = $stats_result->fetch_assoc()) {
        $key = strtolower((string)$row['status']);
        if (isset($stats[$key])) {
            $stats[$key] = (int)$row['total'];
        }
    }
}

$history_select = $credit_history_ready
    ? ", h.event_multiplier AS saved_event_multiplier,
         h.event_amount AS saved_event_amount,
         h.bonus_rate AS saved_bonus_rate,
         h.bonus_amount AS saved_bonus_amount,
         h.credited_amount AS saved_credited_amount,
         h.total_recharge_increment AS saved_total_recharge_increment,
         h.bonus_spins AS saved_bonus_spins,
         h.credited_by AS saved_credited_by,
         h.credited_at AS saved_credited_at"
    : ", NULL AS saved_event_multiplier,
         NULL AS saved_event_amount,
         NULL AS saved_bonus_rate,
         NULL AS saved_bonus_amount,
         NULL AS saved_credited_amount,
         NULL AS saved_total_recharge_increment,
         NULL AS saved_bonus_spins,
         NULL AS saved_credited_by,
         NULL AS saved_credited_at";
$history_join = $credit_history_ready
    ? "LEFT JOIN recharge_credit_history h ON h.bank_transfer_id = b.id"
    : "";

$requests = $conn->query("
    SELECT b.id, b.transaction_id, b.username, b.amount, b.description, b.status,
           b.sender_bank_name, b.created_at, b.is_credited
           $history_select
    FROM bank_transfers b
    $history_join
    $where_sql
    ORDER BY
        CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END,
        b.created_at DESC,
        b.id DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Duyệt - Admin</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="../assets/jquery/jquery.min.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .admin-header { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); border-bottom: 2px solid rgba(249,115,22,0.4); padding: 12px 0; }
        .admin-header a { color: #febb12 !important; font-weight: 600; text-decoration: none; }
        .page-title { background: linear-gradient(135deg, #f97316, #febb12); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; font-size: 28px; text-align: center; margin: 25px 0 8px; }
        .page-subtitle { text-align: center; color: #9ca3af; font-size: 14px; margin-bottom: 25px; }
        .gc-card { background: rgba(30,30,60,0.8); border: 1px solid rgba(249,115,22,0.2); border-radius: 16px; padding: 22px; margin-bottom: 25px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        .stats-row { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 130px; background: rgba(30,30,60,0.6); border: 1px solid rgba(249,115,22,0.15); border-radius: 12px; padding: 15px; text-align: center; }
        .stat-box .stat-num { font-size: 26px; font-weight: 800; color: #febb12; }
        .stat-box .stat-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .filter-row { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; margin-bottom:16px; }
        .filter-row a { color:#d1d5db; border:1px solid rgba(249,115,22,0.25); border-radius:8px; padding:7px 12px; text-decoration:none; font-weight:700; font-size:12px; }
        .filter-row a.active, .filter-row a:hover { background: linear-gradient(135deg, #f97316, #ea580c); color:#fff; }
        .recharge-table { width: 100%; border-collapse: separate; border-spacing: 0 5px; }
        .recharge-table thead th { background: rgba(249,115,22,0.1); color: #febb12; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 10px 8px; border: none; white-space: nowrap; }
        .recharge-table tbody tr { background: rgba(20,20,50,0.65); }
        .recharge-table tbody tr:hover { background: rgba(249,115,22,0.08); }
        .recharge-table td { padding: 9px 8px; border: none; font-size: 12px; vertical-align: middle; color: #e5e7eb; }
        .status-badge { border-radius: 999px; padding: 3px 9px; font-size: 10px; font-weight: 800; color: #fff; display: inline-block; white-space: nowrap; }
        .status-success { background: #16a34a; }
        .status-pending { background: #f59e0b; color: #111827; }
        .status-rejected { background: #dc2626; }
        .status-unknown { background: #64748b; }
        .btn-action { border:0; border-radius:6px; padding:5px 10px; font-size:11px; font-weight:800; cursor:pointer; margin:2px; }
        .btn-approve { background:#16a34a; color:#fff; }
        .btn-reject { background:#dc2626; color:#fff; }
        .approve-form { display:inline-flex; align-items:center; gap:5px; flex-wrap:wrap; }
        .bonus-spin-input { width:76px; background:rgba(15,15,40,0.8); border:1px solid rgba(249,115,22,0.35); color:#fff; border-radius:6px; padding:5px 7px; font-size:11px; font-weight:800; }
        .spin-unit { color:#febb12; font-size:10px; font-weight:800; }
        .admin-alert { border-radius:10px; padding:12px; margin-bottom:16px; font-weight:700; text-align:center; }
        .alert-success { background: rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3); }
        .alert-error { background: rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
        .empty-state { text-align:center; color:#9ca3af; padding:30px 10px; }
        @media (max-width: 900px) {
            .gc-card { overflow-x:auto; }
            .recharge-table { min-width: 820px; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container"><a href="/admin"><i class="fas fa-arrow-left"></i> Quay lại menu admin</a></div>
    </div>

    <div class="container" style="max-width: 1180px;">
        <h1 class="page-title">Duyệt</h1>
        <p class="page-subtitle">Sự kiện x2: số dư được nhân đôi trước, sau đó khuyến mãi tiếp tục tính trên giá trị đã nhân đôi.</p>

        <?php echo $_alert; ?>

        <div class="stats-row">
            <div class="stat-box"><div class="stat-num"><?php echo number_format($stats['pending']); ?></div><div class="stat-label">Chờ duyệt</div></div>
            <div class="stat-box"><div class="stat-num"><?php echo number_format($stats['success']); ?></div><div class="stat-label">Đã duyệt</div></div>
            <div class="stat-box"><div class="stat-num"><?php echo number_format($stats['rejected']); ?></div><div class="stat-label">Từ chối</div></div>
        </div>

        <div class="filter-row">
            <?php foreach (['pending' => 'Chờ duyệt', 'success' => 'Đã duyệt', 'rejected' => 'Từ chối', 'failed' => 'Thất bại', 'all' => 'Tất cả'] as $key => $label) : ?>
                <a class="<?php echo $status_filter === $key ? 'active' : ''; ?>" href="?status=<?php echo urlencode($key); ?>"><?php echo htmlspecialchars($label); ?></a>
            <?php endforeach; ?>
        </div>

        <div class="gc-card">
            <?php if ($requests && $requests->num_rows > 0) : ?>
                <table class="recharge-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tài khoản</th>
                            <th>Tiền chuyển</th>
                            <th>Tổng VND cộng</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th>Mã/Ghi chú</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = $requests->fetch_assoc()) : ?>
                            <?php
                                [$label, $class] = admin_recharge_status_label($request['status'], $request['is_credited']);
                                $request_amount = (int)$request['amount'];
                                $event_amount = recharge_event_amount($request_amount);
                                $total_recharge_increment = $request_amount;
                                $default_bonus_spins = recharge_default_bonus_spins($request_amount);
                                $bonus_rate = recharge_bonus_rate($request_amount);
                                $bonus_amount = recharge_bonus_amount($request_amount);
                                $credit_amount = recharge_credit_amount($request_amount);
                                $has_saved_credit = $request['saved_credited_amount'] !== null;
                                if ($has_saved_credit) {
                                    $event_amount = (int)$request['saved_event_amount'];
                                    $total_recharge_increment = (int)$request['saved_total_recharge_increment'];
                                    $bonus_rate = (int)$request['saved_bonus_rate'];
                                    $bonus_amount = (int)$request['saved_bonus_amount'];
                                    $credit_amount = (int)$request['saved_credited_amount'];
                                    $default_bonus_spins = (int)$request['saved_bonus_spins'];
                                }
                            ?>
                            <tr>
                                <td>#<?php echo (int)$request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['username']); ?></td>
                                <td style="color:#4ade80;font-weight:800;"><?php echo number_format($request_amount, 0, ',', '.'); ?> VND</td>
                                <td style="color:#febb12;font-weight:800;">
                                    <?php if ((int)$request['is_credited'] === 1 && !$has_saved_credit) : ?>
                                        <span style="color:#fca5a5;">Không xác định</span>
                                        <br><span style="font-size:10px;color:#fca5a5;">Giao dịch cũ không lưu số tiền thực tế đã cộng</span>
                                    <?php else : ?>
                                        <?php echo number_format($credit_amount, 0, ',', '.'); ?> VND
                                        <br><span style="font-size:10px;color:#93c5fd;">x2: <?php echo number_format($event_amount, 0, ',', '.'); ?> · KM <?php echo $bonus_rate; ?>%: +<?php echo number_format($bonus_amount, 0, ',', '.'); ?></span>
                                        <br><span style="font-size:10px;color:#c4b5fd;">Tổng nạp thực tế: +<?php echo number_format($total_recharge_increment, 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($has_saved_credit) : ?>
                                        <br><span style="font-size:10px;color:#86efac;">Đã lưu đối soát<?php echo $request['saved_credited_by'] ? ' bởi ' . htmlspecialchars($request['saved_credited_by']) : ''; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($label); ?></span></td>
                                <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                                <td style="max-width:320px;word-break:break-word;"><?php echo htmlspecialchars($request['description'] ?: $request['transaction_id']); ?></td>
                                <td>
                                    <?php if ($credit_history_ready && (int)$request['is_credited'] === 0 && strtolower((string)$request['status']) !== 'success') : ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm(<?php echo htmlspecialchars(json_encode('Duyệt ' . number_format($request_amount, 0, ',', '.') . ' VND: sau x2 và khuyến mãi sẽ cộng tổng ' . number_format($credit_amount, 0, ',', '.') . ' VND cho ' . $request['username'] . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input class="bonus-spin-input" type="number" name="bonus_spins" min="0" step="1" value="<?php echo $default_bonus_spins; ?>" title="Lượt quay cộng thêm">
                                            <span class="spin-unit">lượt</span>
                                            <button type="submit" class="btn-action btn-approve">Duyệt</button>
                                        </form>
                                        <?php if (strtolower((string)$request['status']) !== 'rejected') : ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Từ chối yêu cầu này?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-action btn-reject">Từ chối</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span style="color:#9ca3af;">Đã xử lý</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="empty-state">Không có yêu cầu nạp tiền trong mục này.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
