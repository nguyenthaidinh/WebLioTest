<?php
include_once 'set.php';
include_once 'connect.php';

if ($_login == null) {
    header("Location: /app/login.php");
    exit();
}

if (empty($_SESSION['admin_spin_csrf'])) {
    $_SESSION['admin_spin_csrf'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['admin_spin_csrf'];
$_alert = '';
$prefill_username_raw = $_GET['username'] ?? '';
$prefill_username = is_string($prefill_username_raw) ? trim($prefill_username_raw) : '';

function spin_admin_alert($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-error';
    return '<div class="admin-alert ' . $class . '">' . htmlspecialchars($message) . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_started = false;
    try {
        $posted_token = $_POST['csrf_token'] ?? '';
        if (!is_string($posted_token) || !hash_equals($csrf_token, $posted_token)) {
            throw new Exception('Phien bao mat khong hop le.');
        }

        $username_raw = $_POST['username'] ?? '';
        $spin_amount_raw = $_POST['spin_amount'] ?? '';

        if (!is_string($username_raw)) {
            throw new Exception('Tai khoan khong hop le.');
        }

        $username = trim($username_raw);
        if ($username === '') {
            throw new Exception('Vui long nhap tai khoan.');
        }
        if (is_array($spin_amount_raw)) {
            throw new Exception('So luot quay khong hop le.');
        }

        $spin_amount = filter_var($spin_amount_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($spin_amount === false || $spin_amount > 1000000) {
            throw new Exception('So luot quay phai la so nguyen tu 1 den 1.000.000.');
        }

        $conn->begin_transaction();
        $transaction_started = true;

        $stmt_account = $conn->prepare("SELECT id, username, luotquay FROM account WHERE username = ? FOR UPDATE");
        if (!$stmt_account) {
            throw new Exception('Loi prepare tai khoan: ' . $conn->error);
        }
        $stmt_account->bind_param("s", $username);
        $stmt_account->execute();
        $result_account = $stmt_account->get_result();
        $account = $result_account ? $result_account->fetch_assoc() : null;
        $stmt_account->close();

        if (!$account) {
            throw new Exception('Khong tim thay tai khoan.');
        }

        $stmt_update = $conn->prepare("UPDATE account SET luotquay = luotquay + ? WHERE id = ?");
        if (!$stmt_update) {
            throw new Exception('Loi prepare cong luot quay: ' . $conn->error);
        }
        $account_id = (int)$account['id'];
        $stmt_update->bind_param("ii", $spin_amount, $account_id);
        if (!$stmt_update->execute() || $stmt_update->affected_rows === 0) {
            $stmt_update->close();
            throw new Exception('Khong the cong luot quay.');
        }
        $stmt_update->close();

        $conn->commit();
        $new_total = (int)$account['luotquay'] + $spin_amount;
        $_alert = spin_admin_alert('Da cong ' . number_format($spin_amount, 0, ',', '.') . ' luot quay cho ' . $account['username'] . '. Tong moi: ' . number_format($new_total, 0, ',', '.') . '.');
        $prefill_username = $account['username'];
    } catch (Exception $e) {
        if ($transaction_started) {
            $conn->rollback();
        }
        $_alert = spin_admin_alert($e->getMessage(), 'error');
        $posted_username = $_POST['username'] ?? $prefill_username;
        $prefill_username = is_string($posted_username) ? trim($posted_username) : $prefill_username;
    }
}

$search_raw = $_GET['q'] ?? '';
$search = is_string($search_raw) ? trim($search_raw) : '';
$where_sql = '';
if ($search !== '') {
    $safe_search = $conn->real_escape_string($search);
    $where_sql = "WHERE a.username LIKE '%$safe_search%' OR p.name LIKE '%$safe_search%'";
}

$accounts = $conn->query("
    SELECT a.id, a.username, a.luotquay, a.thoi_vang, a.vnd, p.name AS player_name
    FROM account a
    LEFT JOIN player p ON a.id = p.account_id
    $where_sql
    ORDER BY a.id DESC
    LIMIT 80
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cap Luot Quay - Admin</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="../assets/jquery/jquery.min.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .admin-header { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); border-bottom: 2px solid rgba(249,115,22,0.4); padding: 12px 0; }
        .admin-header a { color: #febb12 !important; font-weight: 600; text-decoration: none; margin-right: 14px; }
        .page-title { background: linear-gradient(135deg, #f97316, #febb12); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; font-size: 28px; text-align: center; margin: 25px 0 8px; }
        .page-subtitle { text-align: center; color: #9ca3af; font-size: 14px; margin-bottom: 25px; }
        .gc-card { background: rgba(30,30,60,0.8); border: 1px solid rgba(249,115,22,0.2); border-radius: 16px; padding: 22px; margin-bottom: 25px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        .form-control { background: rgba(15,15,40,0.8) !important; border: 1px solid rgba(249,115,22,0.2) !important; color: #e0e0e0 !important; border-radius: 8px !important; }
        .form-control:focus { border-color: #f97316 !important; box-shadow: 0 0 0 0.2rem rgba(249,115,22,0.15) !important; }
        .form-grid { display: grid; grid-template-columns: 1.5fr 1fr auto; gap: 12px; align-items: end; }
        .form-label { color: #febb12; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .btn-main-action { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 8px; padding: 9px 18px; font-weight: 800; cursor: pointer; white-space: nowrap; }
        .btn-search { background: rgba(249,115,22,0.18); color: #febb12; border: 1px solid rgba(249,115,22,0.25); border-radius: 8px; padding: 8px 15px; font-weight: 700; }
        .admin-alert { border-radius:10px; padding:12px; margin-bottom:16px; font-weight:700; text-align:center; }
        .alert-success { background: rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3); }
        .alert-error { background: rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
        .spin-table { width: 100%; border-collapse: separate; border-spacing: 0 5px; }
        .spin-table thead th { background: rgba(249,115,22,0.1); color: #febb12; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 10px 8px; border: none; white-space: nowrap; }
        .spin-table tbody tr { background: rgba(20,20,50,0.65); }
        .spin-table tbody tr:hover { background: rgba(249,115,22,0.08); }
        .spin-table td { padding: 9px 8px; border: none; font-size: 12px; vertical-align: middle; color: #e5e7eb; }
        .quick-fill { color:#60a5fa; font-weight:800; text-decoration:none; }
        .quick-fill:hover { color:#93c5fd; text-decoration:none; }
        @media (max-width: 760px) {
            .form-grid { grid-template-columns: 1fr; }
            .gc-card { overflow-x:auto; }
            .spin-table { min-width: 720px; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <a href="/admin"><i class="fas fa-arrow-left"></i> Ve menu admin</a>
            <a href="/admin/nap.php"><i class="fas fa-money-bill"></i> Duyet nap tien</a>
        </div>
    </div>

    <div class="container" style="max-width: 980px;">
        <h1 class="page-title">Cap Luot Quay</h1>
        <p class="page-subtitle">Cong luot quay thu cong cho tai khoan nguoi choi.</p>

        <?php echo $_alert; ?>

        <div class="gc-card">
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label class="form-label" for="username">Tai khoan</label>
                    <input id="username" class="form-control" type="text" name="username" value="<?php echo htmlspecialchars($prefill_username); ?>" placeholder="Nhap username" required>
                </div>
                <div>
                    <label class="form-label" for="spin_amount">So luot cong</label>
                    <input id="spin_amount" class="form-control" type="number" name="spin_amount" min="1" step="1" value="1" required>
                </div>
                <button class="btn-main-action" type="submit">Cong luot quay</button>
            </form>
        </div>

        <div class="gc-card">
            <form method="get" class="d-flex" style="gap:10px; margin-bottom:14px;">
                <input class="form-control" type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tim username hoac ten nhan vat..." style="flex:1;">
                <button class="btn-search" type="submit">Tim</button>
                <?php if ($search !== ''): ?><a class="btn-search" href="/admin/luotquay.php">Xoa</a><?php endif; ?>
            </form>

            <?php if ($accounts && $accounts->num_rows > 0): ?>
                <table class="spin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tai khoan</th>
                            <th>Nhan vat</th>
                            <th>Luot quay</th>
                            <th>TV cho rut</th>
                            <th>VND</th>
                            <th>Chon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($account = $accounts->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo (int)$account['id']; ?></td>
                                <td><?php echo htmlspecialchars($account['username']); ?></td>
                                <td><?php echo htmlspecialchars($account['player_name'] ?: '-'); ?></td>
                                <td style="color:#febb12;font-weight:800;"><?php echo number_format((int)$account['luotquay'], 0, ',', '.'); ?></td>
                                <td><?php echo number_format((int)$account['thoi_vang'], 0, ',', '.'); ?></td>
                                <td style="color:#4ade80;"><?php echo number_format((int)$account['vnd'], 0, ',', '.'); ?></td>
                                <td><a class="quick-fill" href="/admin/luotquay.php?username=<?php echo urlencode($account['username']); ?>">Chon</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center;color:#9ca3af;padding:24px;">Khong tim thay tai khoan.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
