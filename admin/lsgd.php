<?php
include_once 'set.php';
include_once 'connect.php';

if ($_login == null) {
    header("Location: /app/login.php");
    exit();
}

function lsgd_status_label($status, $is_credited = 0) {
    $status = strtolower((string)$status);
    if ((int)$is_credited === 1 || $status === 'success') {
        return ['Da duyet', 'success'];
    }
    if ($status === 'pending') {
        return ['Cho duyet', 'pending'];
    }
    if ($status === 'rejected') {
        return ['Tu choi', 'rejected'];
    }
    if ($status === 'failed') {
        return ['That bai', 'failed'];
    }
    return ['Khac', 'unknown'];
}

function lsgd_query_string($overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }
    return http_build_query($params);
}

function lsgd_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lsgd_get_string($key, $default = '') {
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function lsgd_get_int($key, $default = 1) {
    $value = $_GET[$key] ?? $default;
    if (is_array($value)) {
        return $default;
    }
    return (int)$value;
}

$allowed_statuses = ['all', 'pending', 'success', 'rejected', 'failed'];
$status_filter = lsgd_get_string('status', 'all');
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}

$search = lsgd_get_string('q');
$date_from = lsgd_get_string('from');
$date_to = lsgd_get_string('to');

if ($date_from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}
if ($date_to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

$conditions = [];
if ($status_filter !== 'all') {
    $conditions[] = "status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($search !== '') {
    $safe_search = $conn->real_escape_string($search);
    $like = "'%" . $safe_search . "%'";
    $conditions[] = "(username LIKE $like OR transaction_id LIKE $like OR description LIKE $like OR sender_bank_name LIKE $like)";
}
if ($date_from !== '') {
    $conditions[] = "created_at >= '" . $conn->real_escape_string($date_from) . " 00:00:00'";
}
if ($date_to !== '') {
    $conditions[] = "created_at <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
}

$where_sql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$page = max(1, lsgd_get_int('page', 1));
$per_page = 30;
$offset = ($page - 1) * $per_page;
$db_error = '';

$summary = [
    'total_rows' => 0,
    'total_amount' => 0,
    'credited_amount' => 0,
    'pending_amount' => 0,
];
$summary_result = $conn->query("
    SELECT
        COUNT(*) AS total_rows,
        COALESCE(SUM(amount), 0) AS total_amount,
        COALESCE(SUM(CASE WHEN is_credited = 1 OR status = 'success' THEN amount ELSE 0 END), 0) AS credited_amount,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_amount
    FROM bank_transfers
    $where_sql
");
if ($summary_result) {
    $summary = array_merge($summary, $summary_result->fetch_assoc());
} else {
    $db_error = 'Khong the doc thong ke giao dich: ' . $conn->error;
}

$total_rows = (int)$summary['total_rows'];
$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

$status_stats = [
    'pending' => 0,
    'success' => 0,
    'rejected' => 0,
    'failed' => 0,
];
$status_result = $conn->query("SELECT status, COUNT(*) AS total FROM bank_transfers GROUP BY status");
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $key = strtolower((string)$row['status']);
        if (isset($status_stats[$key])) {
            $status_stats[$key] = (int)$row['total'];
        }
    }
}

$transactions = $conn->query("
    SELECT id, transaction_id, username, amount, description, status, sender_bank_name, created_at, is_credited
    FROM bank_transfers
    $where_sql
    ORDER BY created_at DESC, id DESC
    LIMIT $per_page OFFSET $offset
");
if (!$transactions && $db_error === '') {
    $db_error = 'Khong the doc lich su giao dich: ' . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>LSGD - Admin</title>
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
        .stats-row { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 155px; background: rgba(30,30,60,0.6); border: 1px solid rgba(249,115,22,0.15); border-radius: 12px; padding: 15px; text-align: center; }
        .stat-box .stat-num { font-size: 22px; font-weight: 800; color: #febb12; }
        .stat-box .stat-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .filter-grid { display: grid; grid-template-columns: 1.5fr 0.9fr 0.9fr 0.9fr auto; gap: 10px; align-items: end; }
        .filter-grid label { display: block; color: #febb12; font-size: 11px; text-transform: uppercase; font-weight: 800; margin-bottom: 5px; }
        .form-control { background: rgba(15,15,40,0.8) !important; border: 1px solid rgba(249,115,22,0.25) !important; color: #e5e7eb !important; border-radius: 8px !important; min-height: 38px; }
        .btn-search { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 8px; min-height: 38px; padding: 0 18px; font-weight: 800; }
        .filter-row { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; margin:18px 0 0; }
        .filter-row a { color:#d1d5db; border:1px solid rgba(249,115,22,0.25); border-radius:8px; padding:7px 12px; text-decoration:none; font-weight:700; font-size:12px; }
        .filter-row a.active, .filter-row a:hover { background: linear-gradient(135deg, #f97316, #ea580c); color:#fff; }
        .tx-table { width: 100%; border-collapse: separate; border-spacing: 0 5px; }
        .tx-table thead th { background: rgba(249,115,22,0.1); color: #febb12; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 10px 8px; border: none; white-space: nowrap; }
        .tx-table tbody tr { background: rgba(20,20,50,0.65); }
        .tx-table tbody tr:hover { background: rgba(249,115,22,0.08); }
        .tx-table td { padding: 9px 8px; border: none; font-size: 12px; vertical-align: middle; color: #e5e7eb; }
        .status-badge { border-radius: 999px; padding: 3px 9px; font-size: 10px; font-weight: 800; color: #fff; display: inline-block; white-space: nowrap; }
        .status-success { background: #16a34a; }
        .status-pending { background: #f59e0b; color: #111827; }
        .status-rejected, .status-failed { background: #dc2626; }
        .status-unknown { background: #64748b; }
        .amount-good { color: #4ade80; font-weight: 800; white-space: nowrap; }
        .note-cell { max-width: 330px; word-break: break-word; }
        .empty-state { text-align:center; color:#9ca3af; padding:30px 10px; }
        .admin-alert { border-radius:10px; padding:12px; margin-bottom:16px; font-weight:700; text-align:center; background: rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
        .pagination-wrap { display:flex; justify-content:center; gap:6px; flex-wrap:wrap; margin-top:16px; }
        .pagination-wrap a, .pagination-wrap span { color:#d1d5db; border:1px solid rgba(249,115,22,0.25); border-radius:7px; padding:6px 10px; text-decoration:none; font-size:12px; font-weight:800; }
        .pagination-wrap a.active, .pagination-wrap a:hover { background: linear-gradient(135deg, #f97316, #ea580c); color:#fff; }
        @media (max-width: 1000px) {
            .filter-grid { grid-template-columns: 1fr 1fr; }
            .gc-card.table-wrap { overflow-x:auto; }
            .tx-table { min-width: 980px; }
        }
        @media (max-width: 600px) {
            .filter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <a href="/admin"><i class="fas fa-arrow-left"></i> Menu admin</a>
            <a href="/admin/nap.php"><i class="fas fa-check-circle"></i> Duyet nap</a>
        </div>
    </div>

    <div class="container" style="max-width: 1240px;">
        <h1 class="page-title">Lich Su Giao Dich</h1>
        <p class="page-subtitle">Theo doi toan bo yeu cau nap tien va trang thai xu ly cua nguoi choi.</p>

        <?php if ($db_error !== '') : ?>
            <div class="admin-alert"><?php echo lsgd_h($db_error); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format($total_rows); ?></div>
                <div class="stat-label">Giao dich trong bo loc</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format((int)$summary['total_amount'], 0, ',', '.'); ?></div>
                <div class="stat-label">Tong tien trong bo loc</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format((int)$summary['credited_amount'], 0, ',', '.'); ?></div>
                <div class="stat-label">Da duyet</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format((int)$summary['pending_amount'], 0, ',', '.'); ?></div>
                <div class="stat-label">Cho duyet</div>
            </div>
        </div>

        <div class="gc-card">
            <form method="get" action="">
                <div class="filter-grid">
                    <div>
                        <label for="q">Tim kiem</label>
                        <input class="form-control" id="q" name="q" value="<?php echo lsgd_h($search); ?>" placeholder="Tai khoan, ma GD, ghi chu, ngan hang">
                    </div>
                    <div>
                        <label for="status">Trang thai</label>
                        <select class="form-control" id="status" name="status">
                            <?php foreach (['all' => 'Tat ca', 'pending' => 'Cho duyet', 'success' => 'Da duyet', 'rejected' => 'Tu choi', 'failed' => 'That bai'] as $key => $label) : ?>
                                <option value="<?php echo lsgd_h($key); ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>><?php echo lsgd_h($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="from">Tu ngay</label>
                        <input class="form-control" id="from" name="from" type="date" value="<?php echo lsgd_h($date_from); ?>">
                    </div>
                    <div>
                        <label for="to">Den ngay</label>
                        <input class="form-control" id="to" name="to" type="date" value="<?php echo lsgd_h($date_to); ?>">
                    </div>
                    <button class="btn-search" type="submit">Loc</button>
                </div>
            </form>

            <div class="filter-row">
                <?php foreach (['all' => 'Tat ca', 'pending' => 'Cho duyet (' . $status_stats['pending'] . ')', 'success' => 'Da duyet (' . $status_stats['success'] . ')', 'rejected' => 'Tu choi (' . $status_stats['rejected'] . ')', 'failed' => 'That bai (' . $status_stats['failed'] . ')'] as $key => $label) : ?>
                    <a class="<?php echo $status_filter === $key ? 'active' : ''; ?>" href="?<?php echo lsgd_h(lsgd_query_string(['status' => $key, 'page' => 1])); ?>"><?php echo lsgd_h($label); ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="gc-card table-wrap">
            <?php if ($transactions && $transactions->num_rows > 0) : ?>
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thoi gian</th>
                            <th>Tai khoan</th>
                            <th>So tien</th>
                            <th>Trang thai</th>
                            <th>Ma giao dich</th>
                            <th>Ngan hang</th>
                            <th>Ghi chu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tx = $transactions->fetch_assoc()) : ?>
                            <?php [$label, $class] = lsgd_status_label($tx['status'], $tx['is_credited']); ?>
                            <tr>
                                <td>#<?php echo (int)$tx['id']; ?></td>
                                <td><?php echo lsgd_h($tx['created_at']); ?></td>
                                <td>
                                    <a style="color:#febb12;font-weight:800;" href="/admin/users.php?q=<?php echo urlencode((string)$tx['username']); ?>">
                                        <?php echo lsgd_h($tx['username']); ?>
                                    </a>
                                </td>
                                <td class="amount-good"><?php echo number_format((int)$tx['amount'], 0, ',', '.'); ?> VND</td>
                                <td><span class="status-badge status-<?php echo lsgd_h($class); ?>"><?php echo lsgd_h($label); ?></span></td>
                                <td><?php echo lsgd_h($tx['transaction_id'] ?: '-'); ?></td>
                                <td><?php echo lsgd_h($tx['sender_bank_name'] ?: '-'); ?></td>
                                <td class="note-cell"><?php echo lsgd_h($tx['description'] ?: '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="pagination-wrap">
                    <?php if ($page > 1) : ?>
                        <a href="?<?php echo lsgd_h(lsgd_query_string(['page' => $page - 1])); ?>">Truoc</a>
                    <?php endif; ?>
                    <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++) :
                    ?>
                        <a class="<?php echo $i === $page ? 'active' : ''; ?>" href="?<?php echo lsgd_h(lsgd_query_string(['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages) : ?>
                        <a href="?<?php echo lsgd_h(lsgd_query_string(['page' => $page + 1])); ?>">Sau</a>
                    <?php endif; ?>
                    <span><?php echo number_format($total_rows); ?> dong</span>
                </div>
            <?php else : ?>
                <div class="empty-state">Khong co giao dich nao khop voi bo loc.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
