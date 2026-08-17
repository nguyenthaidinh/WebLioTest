<?php
require_once __DIR__ . '/set.php';

if ($_login === null) {
    header('Location: /app/login.php');
    exit();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
$conn->set_charset('utf8mb4');

function gold_history_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gold_history_number($value)
{
    return number_format((int)$value, 0, ',', '.');
}

function gold_history_get_string($key, $default = '')
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function gold_history_bind(mysqli_stmt $stmt, $types, array $params)
{
    if ($types === '') {
        return;
    }

    $bind = [$types];
    foreach ($params as $index => $value) {
        $params[$index] = $value;
        $bind[] = &$params[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

function gold_history_fetch_all(mysqli $conn, $sql, $types = '', array $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Không thể chuẩn bị truy vấn.');
    }

    gold_history_bind($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function gold_history_fetch_one(mysqli $conn, $sql, $types = '', array $params = [])
{
    $rows = gold_history_fetch_all($conn, $sql, $types, $params);
    return $rows[0] ?? [];
}

function gold_history_parse_local_time($value, DateTimeZone $timezone)
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $timezone);
    $errors = DateTimeImmutable::getLastErrors();
    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }
    return $date;
}

function gold_history_encode_cursor($time, $id)
{
    $json = json_encode(['t' => (string)$time, 'i' => (string)$id]);
    return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
}

function gold_history_decode_cursor($value)
{
    if (!is_string($value) || $value === '' || strlen($value) > 180) {
        return null;
    }

    $encoded = strtr($value, '-_', '+/');
    $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
    $decoded = base64_decode($encoded, true);
    $data = $decoded === false ? null : json_decode($decoded, true);

    if (!is_array($data)
        || !isset($data['t'], $data['i'])
        || !is_string($data['t'])
        || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d{1,6})?$/', $data['t'])
        || !ctype_digit((string)$data['i'])
        || (int)$data['i'] <= 0) {
        return null;
    }

    return ['time' => $data['t'], 'id' => (int)$data['i']];
}

function gold_history_query_string(array $overrides = [])
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }
    return http_build_query($params);
}

function gold_history_display_time($value)
{
    try {
        $date = new DateTimeImmutable((string)$value, new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $date->format('d/m/Y H:i:s');
    } catch (Throwable $e) {
        return (string)$value;
    }
}

$schemaSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `gold_bar_spend_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id` BIGINT NOT NULL,
    `account_id` INT DEFAULT NULL,
    `player_name` VARCHAR(50) NOT NULL,
    `account_username` VARCHAR(50) DEFAULT NULL,
    `amount` INT UNSIGNED NOT NULL,
    `balance_before` INT UNSIGNED DEFAULT NULL,
    `balance_after` INT UNSIGNED DEFAULT NULL,
    `action_code` VARCHAR(64) NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `reference_id` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_gbsh_created` (`created_at`, `id`),
    KEY `idx_gbsh_player` (`player_id`, `created_at`, `id`),
    KEY `idx_gbsh_account` (`account_id`, `created_at`, `id`),
    KEY `idx_gbsh_player_name` (`player_name`, `created_at`, `id`),
    KEY `idx_gbsh_username` (`account_username`, `created_at`, `id`),
    KEY `idx_gbsh_action` (`action_code`, `created_at`, `id`),
    KEY `idx_gbsh_reference` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$databaseError = '';
$schemaReady = false;
try {
    $conn->query($schemaSql);
    $schemaReady = true;
} catch (Throwable $e) {
    error_log('Gold bar spend history schema error: ' . $e->getMessage());
    $databaseError = 'Chưa thể mở dữ liệu lịch sử. Vui lòng kiểm tra quyền tạo bảng hoặc chạy tệp database/gold_bar_spend_history.sql.';
}

$timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
$now = new DateTimeImmutable('now', $timezone);
$windowStart = $now->modify('-3 days');
$rangeWarning = '';

$fromInput = gold_history_get_string('from');
$toInput = gold_history_get_string('to');
$from = gold_history_parse_local_time($fromInput, $timezone) ?? $windowStart;
$to = gold_history_parse_local_time($toInput, $timezone) ?? $now;

if ($from < $windowStart) {
    $from = $windowStart;
    $rangeWarning = 'Thời gian bắt đầu đã được giới hạn về mốc 72 giờ gần nhất.';
}
if ($to > $now) {
    $to = $now;
    $rangeWarning = 'Thời gian kết thúc đã được giới hạn về thời điểm hiện tại.';
}
if ($from > $to) {
    $from = $windowStart;
    $to = $now;
    $rangeWarning = 'Khoảng thời gian không hợp lệ nên đã được đặt lại về 72 giờ gần nhất.';
}

$search = gold_history_get_string('q');
if (function_exists('mb_substr')) {
    $search = mb_substr($search, 0, 80, 'UTF-8');
} else {
    $search = substr($search, 0, 80);
}

$action = gold_history_get_string('action');
if ($action !== '' && !preg_match('/^[a-zA-Z0-9_.:-]{1,64}$/', $action)) {
    $action = '';
}

$allowedLimits = [50, 100, 200];
$limit = (int)gold_history_get_string('limit', '100');
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 100;
}

$conditions = ['created_at >= ?', 'created_at <= ?'];
$types = 'ss';
$params = [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')];

if ($search !== '') {
    $searchConditions = [
        'player_name LIKE ?',
        'account_username LIKE ?',
        'reason LIKE ?',
        'details LIKE ?',
        'reference_id = ?',
    ];
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
    $searchParams = [$like, $like, $like, $like, $search];
    $searchTypes = 'sssss';

    if (ctype_digit($search) && strlen($search) <= 18) {
        array_unshift($searchConditions, 'id = ?', 'player_id = ?', 'account_id = ?');
        $numeric = (int)$search;
        $searchParams = array_merge([$numeric, $numeric, $numeric], $searchParams);
        $searchTypes = 'iii' . $searchTypes;
    }

    $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
    $types .= $searchTypes;
    $params = array_merge($params, $searchParams);
}

if ($action !== '') {
    $conditions[] = 'action_code = ?';
    $types .= 's';
    $params[] = $action;
}

$whereSql = 'WHERE ' . implode(' AND ', $conditions);
$cursorRaw = gold_history_get_string('cursor');
$cursor = gold_history_decode_cursor($cursorRaw);
if ($cursorRaw !== '' && $cursor === null) {
    $rangeWarning = 'Vị trí tải thêm không hợp lệ; danh sách đã quay về các giao dịch mới nhất.';
}

$summary = [
    'transaction_count' => 0,
    'total_amount' => 0,
    'player_count' => 0,
    'mismatch_count' => 0,
];
$categories = [];
$transactions = [];
$hasMore = false;
$nextCursor = '';

if ($schemaReady) {
    try {
        $summary = array_merge($summary, gold_history_fetch_one(
            $conn,
            "SELECT COUNT(*) AS transaction_count,
                    COALESCE(SUM(amount), 0) AS total_amount,
                    COUNT(DISTINCT player_id) AS player_count,
                    COALESCE(SUM(CASE
                        WHEN balance_before IS NOT NULL
                         AND balance_after IS NOT NULL
                         AND balance_before - balance_after <> amount THEN 1
                        ELSE 0 END), 0) AS mismatch_count
             FROM gold_bar_spend_history $whereSql",
            $types,
            $params
        ));

        $categories = gold_history_fetch_all(
            $conn,
            'SELECT action_code, MAX(reason) AS reason, COUNT(*) AS total
             FROM gold_bar_spend_history
             WHERE created_at >= ? AND created_at <= ?
             GROUP BY action_code
             ORDER BY reason ASC
             LIMIT 100',
            'ss',
            [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]
        );

        $listConditions = $conditions;
        $listTypes = $types;
        $listParams = $params;
        if ($cursor !== null) {
            $listConditions[] = '(created_at < ? OR (created_at = ? AND id < ?))';
            $listTypes .= 'ssi';
            $listParams[] = $cursor['time'];
            $listParams[] = $cursor['time'];
            $listParams[] = $cursor['id'];
        }

        $listWhereSql = 'WHERE ' . implode(' AND ', $listConditions);
        $transactions = gold_history_fetch_all(
            $conn,
            "SELECT id, player_id, account_id, player_name, account_username,
                    amount, balance_before, balance_after, action_code, reason,
                    details, reference_id, created_at
             FROM gold_bar_spend_history
             $listWhereSql
             ORDER BY created_at DESC, id DESC
             LIMIT " . ($limit + 1),
            $listTypes,
            $listParams
        );

        if (count($transactions) > $limit) {
            $hasMore = true;
            array_pop($transactions);
        }
        if ($hasMore && !empty($transactions)) {
            $last = $transactions[count($transactions) - 1];
            $nextCursor = gold_history_encode_cursor($last['created_at'], $last['id']);
        }
    } catch (Throwable $e) {
        error_log('Gold bar spend history query error: ' . $e->getMessage());
        $databaseError = 'Không thể đọc lịch sử tiêu Thỏi Vàng lúc này. Vui lòng kiểm tra kết nối và cấu trúc cơ sở dữ liệu.';
    }
}

$fromValue = $from->format('Y-m-d\TH:i');
$toValue = $to->format('Y-m-d\TH:i');
$hasFilters = $search !== '' || $action !== '' || $fromInput !== '' || $toInput !== '' || $limit !== 100;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lịch sử tiêu Thỏi Vàng - Admin</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        :root { --nav:#101827; --ink:#152033; --muted:#64748b; --line:#e2e8f0; --surface:#fff; --bg:#f1f5f9; --gold:#d97706; --gold-soft:#fff7d6; --red:#dc2626; --green:#15803d; }
        * { box-sizing: border-box; }
        body { background:var(--bg); color:var(--ink); font-family:"Segoe UI",Arial,sans-serif; margin:0; }
        .admin-bar { background:var(--nav); border-bottom:3px solid #f59e0b; color:#fff; }
        .admin-bar-inner { align-items:center; display:flex; gap:18px; justify-content:space-between; margin:auto; max-width:1500px; min-height:58px; padding:8px 22px; }
        .brand { align-items:center; color:#fbbf24; display:flex; font-size:17px; font-weight:900; gap:9px; text-decoration:none; }
        .admin-nav { display:flex; flex-wrap:wrap; gap:8px; }
        .admin-nav a { border-radius:7px; color:#dbe3ef; font-size:13px; font-weight:700; padding:7px 10px; text-decoration:none; }
        .admin-nav a:hover,.admin-nav a.active { background:#263449; color:#fbbf24; }
        .page { margin:auto; max-width:1500px; padding:25px 22px 40px; }
        .heading-row { align-items:flex-start; display:flex; gap:16px; justify-content:space-between; margin-bottom:18px; }
        h1 { font-size:28px; font-weight:900; letter-spacing:-.4px; margin:0 0 5px; }
        .subtitle { color:var(--muted); margin:0; }
        .window-badge { background:var(--gold-soft); border:1px solid #f2d675; border-radius:999px; color:#92400e; font-size:12px; font-weight:900; padding:8px 13px; white-space:nowrap; }
        .notice { align-items:flex-start; background:#eff6ff; border:1px solid #bfdbfe; border-radius:11px; color:#1e3a8a; display:flex; font-size:13px; gap:10px; margin-bottom:16px; padding:12px 14px; }
        .notice.warning { background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
        .notice.error { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
        .stats { display:grid; gap:12px; grid-template-columns:repeat(4,minmax(0,1fr)); margin-bottom:16px; }
        .stat { background:var(--surface); border:1px solid var(--line); border-radius:12px; box-shadow:0 3px 12px rgba(15,23,42,.04); padding:15px 17px; }
        .stat-label { color:var(--muted); font-size:11px; font-weight:800; letter-spacing:.45px; margin-bottom:5px; text-transform:uppercase; }
        .stat-value { font-size:23px; font-weight:900; line-height:1.1; }
        .stat-value.gold { color:var(--gold); }
        .stat-value.danger { color:var(--red); }
        .card { background:var(--surface); border:1px solid var(--line); border-radius:13px; box-shadow:0 3px 14px rgba(15,23,42,.045); margin-bottom:16px; padding:17px; }
        .filters { align-items:end; display:grid; gap:11px; grid-template-columns:minmax(250px,1.5fr) minmax(190px,1fr) 185px 185px 110px auto; }
        label { color:#334155; display:block; font-size:11px; font-weight:900; margin-bottom:6px; text-transform:uppercase; }
        .form-control { background:#fff !important; border:1px solid #cbd5e1 !important; border-radius:8px !important; color:var(--ink) !important; font-size:13px; min-height:40px; }
        .form-control:focus { border-color:#f59e0b !important; box-shadow:0 0 0 3px rgba(245,158,11,.14) !important; }
        .btn-filter { background:#d97706; border:0; border-radius:8px; color:#fff; font-weight:900; min-height:40px; padding:0 18px; }
        .reset { color:#64748b; display:inline-block; font-size:12px; font-weight:800; margin-top:11px; text-decoration:none; }
        .table-head { align-items:center; display:flex; gap:10px; justify-content:space-between; margin-bottom:9px; }
        .table-title { font-size:16px; font-weight:900; }
        .batch-info { color:var(--muted); font-size:12px; }
        .table-wrap { overflow:auto; }
        table { border-collapse:separate; border-spacing:0; min-width:1250px; width:100%; }
        th { background:#f8fafc; border-bottom:1px solid var(--line); color:#475569; font-size:10px; font-weight:900; letter-spacing:.35px; padding:10px 9px; text-align:left; text-transform:uppercase; white-space:nowrap; }
        td { border-bottom:1px solid #edf1f6; font-size:12px; padding:11px 9px; vertical-align:top; }
        tr.mismatch td { background:#fff7f7; }
        .tx-id { color:#64748b; font-family:Consolas,monospace; font-weight:800; }
        .time { white-space:nowrap; }
        .player { color:#172554; display:block; font-size:13px; font-weight:900; }
        .meta { color:#77859a; display:block; font-size:10px; margin-top:3px; }
        .amount { background:#fff1f2; border:1px solid #fecdd3; border-radius:999px; color:#be123c; display:inline-block; font-size:13px; font-weight:900; padding:4px 9px; white-space:nowrap; }
        .balance { font-family:Consolas,monospace; font-weight:800; white-space:nowrap; }
        .arrow { color:#94a3b8; margin:0 4px; }
        .reason { font-weight:800; max-width:290px; }
        .code { background:#f1f5f9; border-radius:5px; color:#475569; display:inline-block; font-family:Consolas,monospace; font-size:10px; margin-top:5px; padding:2px 5px; }
        details { max-width:320px; }
        summary { color:#b45309; cursor:pointer; font-size:11px; font-weight:900; }
        details div { background:#f8fafc; border:1px solid var(--line); border-radius:7px; color:#475569; line-height:1.45; margin-top:6px; max-height:170px; overflow:auto; padding:8px; white-space:pre-wrap; word-break:break-word; }
        .check { border-radius:999px; display:inline-block; font-size:10px; font-weight:900; padding:4px 8px; white-space:nowrap; }
        .check.ok { background:#dcfce7; color:#166534; }
        .check.bad { background:#fee2e2; color:#991b1b; }
        .check.unknown { background:#f1f5f9; color:#64748b; }
        .empty { color:var(--muted); padding:45px 15px; text-align:center; }
        .empty i { color:#cbd5e1; display:block; font-size:34px; margin-bottom:10px; }
        .load-more { display:flex; justify-content:center; padding-top:17px; }
        .load-more a { background:var(--nav); border-radius:9px; color:#fff; font-size:13px; font-weight:900; padding:10px 18px; text-decoration:none; }
        @media (max-width:1150px) { .filters { grid-template-columns:1fr 1fr 1fr; } }
        @media (max-width:750px) { .admin-bar-inner,.heading-row { align-items:flex-start; flex-direction:column; } .stats { grid-template-columns:1fr 1fr; } .filters { grid-template-columns:1fr; } .page { padding:18px 12px 30px; } .window-badge { white-space:normal; } }
        @media (max-width:430px) { .stats { grid-template-columns:1fr; } h1 { font-size:23px; } }
    </style>
</head>
<body>
    <header class="admin-bar">
        <div class="admin-bar-inner">
            <a class="brand" href="/admin"><i class="fas fa-shield-alt"></i> Quản trị Lio</a>
            <nav class="admin-nav" aria-label="Điều hướng quản trị">
                <a href="/admin"><i class="fas fa-home"></i> Tổng quan</a>
                <a class="active" href="/admin/lich-su-tieu-thoi-vang.php"><i class="fas fa-coins"></i> Tiêu Thỏi Vàng</a>
                <a href="/admin/lsgd.php"><i class="fas fa-exchange-alt"></i> Giao dịch game</a>
                <a href="/admin/players.php"><i class="fas fa-user"></i> Nhân vật</a>
            </nav>
        </div>
    </header>

    <main class="page">
        <div class="heading-row">
            <div>
                <h1>Lịch sử tiêu Thỏi Vàng</h1>
                <p class="subtitle">Tra cứu từng lần trừ Thỏi Vàng và đối chiếu số dư trước/sau của người chơi.</p>
            </div>
            <div class="window-badge"><i class="far fa-clock"></i> Chỉ xem 72 giờ gần nhất</div>
        </div>

        <div class="notice">
            <i class="fas fa-info-circle"></i>
            <div>Dữ liệu được sắp xếp từ mới đến cũ. Nhật ký chỉ hợp lệ khi máy chủ game ghi sau khi giao dịch hoàn tất; số dư trước trừ số dư sau phải bằng số Thỏi Vàng đã tiêu.</div>
        </div>

        <?php if ($rangeWarning !== '') : ?>
            <div class="notice warning"><i class="fas fa-exclamation-triangle"></i><div><?php echo gold_history_h($rangeWarning); ?></div></div>
        <?php endif; ?>
        <?php if ($databaseError !== '') : ?>
            <div class="notice error"><i class="fas fa-times-circle"></i><div><?php echo gold_history_h($databaseError); ?></div></div>
        <?php endif; ?>

        <section class="stats" aria-label="Thống kê lịch sử">
            <div class="stat"><div class="stat-label">Lượt tiêu trong bộ lọc</div><div class="stat-value"><?php echo gold_history_number($summary['transaction_count']); ?></div></div>
            <div class="stat"><div class="stat-label">Tổng Thỏi Vàng đã tiêu</div><div class="stat-value gold"><?php echo gold_history_number($summary['total_amount']); ?></div></div>
            <div class="stat"><div class="stat-label">Người chơi phát sinh</div><div class="stat-value"><?php echo gold_history_number($summary['player_count']); ?></div></div>
            <div class="stat"><div class="stat-label">Bản ghi cần kiểm tra</div><div class="stat-value <?php echo (int)$summary['mismatch_count'] > 0 ? 'danger' : ''; ?>"><?php echo gold_history_number($summary['mismatch_count']); ?></div></div>
        </section>

        <section class="card">
            <form method="get" action="">
                <div class="filters">
                    <div>
                        <label for="q">Tìm người chơi hoặc giao dịch</label>
                        <input class="form-control" id="q" name="q" value="<?php echo gold_history_h($search); ?>" placeholder="Tên nhân vật, tài khoản, ID, mã đối soát...">
                    </div>
                    <div>
                        <label for="action">Loại phát sinh</label>
                        <select class="form-control" id="action" name="action">
                            <option value="">Tất cả loại phát sinh</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo gold_history_h($category['action_code']); ?>" <?php echo $action === $category['action_code'] ? 'selected' : ''; ?>><?php echo gold_history_h($category['reason']); ?> (<?php echo gold_history_number($category['total']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="from">Từ thời gian</label>
                        <input class="form-control" id="from" name="from" type="datetime-local" min="<?php echo gold_history_h($windowStart->format('Y-m-d\TH:i')); ?>" max="<?php echo gold_history_h($now->format('Y-m-d\TH:i')); ?>" value="<?php echo gold_history_h($fromValue); ?>">
                    </div>
                    <div>
                        <label for="to">Đến thời gian</label>
                        <input class="form-control" id="to" name="to" type="datetime-local" min="<?php echo gold_history_h($windowStart->format('Y-m-d\TH:i')); ?>" max="<?php echo gold_history_h($now->format('Y-m-d\TH:i')); ?>" value="<?php echo gold_history_h($toValue); ?>">
                    </div>
                    <div>
                        <label for="limit">Số dòng/lần</label>
                        <select class="form-control" id="limit" name="limit">
                            <?php foreach ($allowedLimits as $allowedLimit) : ?>
                                <option value="<?php echo $allowedLimit; ?>" <?php echo $limit === $allowedLimit ? 'selected' : ''; ?>><?php echo $allowedLimit; ?> dòng</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn-filter" type="submit"><i class="fas fa-search"></i> Lọc</button>
                </div>
            </form>
            <?php if ($hasFilters || $cursor !== null) : ?>
                <a class="reset" href="/admin/lich-su-tieu-thoi-vang.php"><i class="fas fa-undo"></i> Đặt lại bộ lọc</a>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="table-head">
                <div class="table-title">Chi tiết phát sinh</div>
                <div class="batch-info">Đang hiển thị <?php echo gold_history_number(count($transactions)); ?> bản ghi<?php echo $cursor !== null ? ' tiếp theo' : ' mới nhất'; ?></div>
            </div>
            <div class="table-wrap">
                <?php if (!empty($transactions)) : ?>
                    <table>
                        <thead><tr><th>Mã</th><th>Thời gian</th><th>Người chơi</th><th>Đã tiêu</th><th>Số dư trước → sau</th><th>Lý do</th><th>Đối soát</th><th>Chi tiết</th></tr></thead>
                        <tbody>
                        <?php foreach ($transactions as $transaction) :
                            $hasBalance = $transaction['balance_before'] !== null && $transaction['balance_after'] !== null;
                            $isMatched = $hasBalance && ((int)$transaction['balance_before'] - (int)$transaction['balance_after'] === (int)$transaction['amount']);
                        ?>
                            <tr class="<?php echo $hasBalance && !$isMatched ? 'mismatch' : ''; ?>">
                                <td><span class="tx-id">#<?php echo gold_history_h($transaction['id']); ?></span></td>
                                <td class="time"><?php echo gold_history_h(gold_history_display_time($transaction['created_at'])); ?></td>
                                <td>
                                    <a class="player" href="/admin/players.php?q=<?php echo urlencode($transaction['player_name']); ?>"><?php echo gold_history_h($transaction['player_name']); ?></a>
                                    <span class="meta">Nhân vật #<?php echo gold_history_h($transaction['player_id']); ?><?php echo $transaction['account_username'] ? ' · TK ' . gold_history_h($transaction['account_username']) : ''; ?></span>
                                </td>
                                <td><span class="amount">-<?php echo gold_history_number($transaction['amount']); ?> TV</span></td>
                                <td>
                                    <?php if ($hasBalance) : ?>
                                        <span class="balance"><?php echo gold_history_number($transaction['balance_before']); ?><span class="arrow">→</span><?php echo gold_history_number($transaction['balance_after']); ?></span>
                                    <?php else : ?><span class="meta">Chưa ghi số dư</span><?php endif; ?>
                                </td>
                                <td><div class="reason"><?php echo gold_history_h($transaction['reason']); ?></div><span class="code"><?php echo gold_history_h($transaction['action_code']); ?></span></td>
                                <td>
                                    <?php if (!$hasBalance) : ?><span class="check unknown">Thiếu số dư</span>
                                    <?php elseif ($isMatched) : ?><span class="check ok"><i class="fas fa-check"></i> Khớp</span>
                                    <?php else : ?><span class="check bad"><i class="fas fa-exclamation"></i> Cần kiểm tra</span><?php endif; ?>
                                    <?php if ($transaction['reference_id']) : ?><span class="meta">Mã: <?php echo gold_history_h($transaction['reference_id']); ?></span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (trim((string)$transaction['details']) !== '') : ?>
                                        <details><summary>Xem chi tiết</summary><div><?php echo gold_history_h($transaction['details']); ?></div></details>
                                    <?php else : ?><span class="meta">Không có ghi chú</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="empty"><i class="fas fa-receipt"></i>Chưa có giao dịch tiêu Thỏi Vàng nào trong khoảng thời gian và bộ lọc này.</div>
                <?php endif; ?>
            </div>

            <?php if ($hasMore && $nextCursor !== '') : ?>
                <div class="load-more"><a href="?<?php echo gold_history_h(gold_history_query_string(['cursor' => $nextCursor])); ?>"><i class="fas fa-chevron-down"></i> Xem <?php echo $limit; ?> giao dịch cũ hơn</a></div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
