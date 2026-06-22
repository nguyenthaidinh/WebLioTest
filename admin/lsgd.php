<?php
include_once 'set.php';
include_once 'connect.php';

if ($_login == null) {
    header("Location: /app/login.php");
    exit();
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

function lsgd_query_string($overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null || ($key === 'page' && (int)$value <= 1)) {
            unset($params[$key]);
        }
    }
    return http_build_query($params);
}

function lsgd_valid_date($value) {
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
}

function lsgd_player_name($value) {
    return trim(preg_replace('/\s*\(\d+\)\s*$/', '', (string)$value));
}

function lsgd_player_id($value) {
    if (preg_match('/\((\d+)\)\s*$/', (string)$value, $matches)) {
        return $matches[1];
    }
    return '';
}

function lsgd_text_len($value) {
    $value = (string)$value;
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function lsgd_text_cut($value, $limit) {
    $value = (string)$value;
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function lsgd_compact_text($value) {
    return trim(preg_replace('/\s+/', ' ', (string)$value));
}

function lsgd_short_text($value, $limit = 220) {
    $value = lsgd_compact_text($value);
    if ($value === '') {
        return '-';
    }
    if (lsgd_text_len($value) <= $limit) {
        return $value;
    }
    return lsgd_text_cut($value, $limit) . '...';
}

function lsgd_extract_gold($value) {
    if (preg_match('/Gold:\s*([^,]+)/i', (string)$value, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

function lsgd_render_trade_text($value, $limit = 220) {
    $full = trim((string)$value);
    if ($full === '') {
        return '<span class="muted">-</span>';
    }

    $compact = lsgd_compact_text($full);
    $short = lsgd_short_text($full, $limit);
    $gold = lsgd_extract_gold($full);
    $html = '';

    if ($gold !== '') {
        $html .= '<div class="gold-chip">Gold: ' . lsgd_h($gold) . '</div>';
    }

    $html .= '<div class="preview-text">' . lsgd_h($short) . '</div>';

    if (lsgd_text_len($compact) > $limit) {
        $html .= '<details class="detail-box"><summary>Xem day du</summary><pre>' . lsgd_h($full) . '</pre></details>';
    }

    return $html;
}

function lsgd_render_bag_panel($title, $value) {
    $full = trim((string)$value);
    if ($full === '') {
        $full = '-';
    }

    return '<div class="bag-panel">'
        . '<div class="bag-title">' . lsgd_h($title) . '</div>'
        . '<pre>' . lsgd_h($full) . '</pre>'
        . '</div>';
}

function lsgd_render_bags($tx) {
    return '<details class="bag-detail">'
        . '<summary>Xem tui truoc/sau</summary>'
        . '<div class="bag-grid">'
        . lsgd_render_bag_panel('Nguoi 1 truoc GD', $tx['bag_1_before_tran'] ?? '')
        . lsgd_render_bag_panel('Nguoi 1 sau GD', $tx['bag_1_after_tran'] ?? '')
        . lsgd_render_bag_panel('Nguoi 2 truoc GD', $tx['bag_2_before_tran'] ?? '')
        . lsgd_render_bag_panel('Nguoi 2 sau GD', $tx['bag_2_after_tran'] ?? '')
        . '</div>'
        . '</details>';
}

function lsgd_render_player_link($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '<span class="muted">-</span>';
    }

    $name = lsgd_player_name($value);
    $id = lsgd_player_id($value);
    $query = $name !== '' ? $name : $value;
    $meta = $id !== '' ? '<span class="player-id">ID ' . lsgd_h($id) . '</span>' : '';

    return '<a class="player-link" href="/admin/players.php?q=' . urlencode($query) . '">'
        . lsgd_h($value)
        . '</a>'
        . $meta;
}

$search = lsgd_get_string('q');
$scope = lsgd_get_string('scope', 'all');
$date_from = lsgd_get_string('from');
$date_to = lsgd_get_string('to');

$allowed_scopes = ['all', 'players', 'items'];
if (!in_array($scope, $allowed_scopes, true)) {
    $scope = 'all';
}

if ($date_from !== '' && !lsgd_valid_date($date_from)) {
    $date_from = '';
}
if ($date_to !== '' && !lsgd_valid_date($date_to)) {
    $date_to = '';
}

$conditions = [];
if ($search !== '') {
    $safe_search = $conn->real_escape_string($search);
    $like = "'%" . $safe_search . "%'";

    if ($scope === 'players') {
        $search_condition = "(player_1 LIKE $like OR player_2 LIKE $like)";
    } elseif ($scope === 'items') {
        $search_condition = "(item_player_1 LIKE $like OR item_player_2 LIKE $like OR bag_1_before_tran LIKE $like OR bag_2_before_tran LIKE $like OR bag_1_after_tran LIKE $like OR bag_2_after_tran LIKE $like)";
    } else {
        $search_condition = "(player_1 LIKE $like OR player_2 LIKE $like OR item_player_1 LIKE $like OR item_player_2 LIKE $like OR bag_1_before_tran LIKE $like OR bag_2_before_tran LIKE $like OR bag_1_after_tran LIKE $like OR bag_2_after_tran LIKE $like)";
    }

    if (ctype_digit($search)) {
        $search_condition = "(id = " . (int)$search . " OR $search_condition)";
    }

    $conditions[] = $search_condition;
}

if ($date_from !== '') {
    $conditions[] = "time_tran >= '" . $conn->real_escape_string($date_from) . " 00:00:00'";
}
if ($date_to !== '') {
    $conditions[] = "time_tran <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
}

$where_sql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$page = max(1, lsgd_get_int('page', 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$db_error = '';

$summary = [
    'total_rows' => 0,
    'first_time' => null,
    'latest_time' => null,
];

$summary_result = $conn->query("
    SELECT
        COUNT(*) AS total_rows,
        MIN(time_tran) AS first_time,
        MAX(time_tran) AS latest_time
    FROM history_transaction
    $where_sql
");

if ($summary_result) {
    $summary = array_merge($summary, $summary_result->fetch_assoc());
} else {
    $db_error = 'Khong the doc lich su giao dich game: ' . $conn->error;
}

$today_count = 0;
$today_result = $conn->query("
    SELECT COUNT(*) AS total_rows
    FROM history_transaction
    WHERE time_tran >= CURDATE()
      AND time_tran < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
");
if ($today_result) {
    $today_row = $today_result->fetch_assoc();
    $today_count = (int)($today_row['total_rows'] ?? 0);
}

$total_rows = (int)($summary['total_rows'] ?? 0);
$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

$transactions = $conn->query("
    SELECT
        id,
        player_1,
        player_2,
        item_player_1,
        item_player_2,
        bag_1_before_tran,
        bag_2_before_tran,
        bag_1_after_tran,
        bag_2_after_tran,
        time_tran
    FROM history_transaction
    $where_sql
    ORDER BY time_tran DESC, id DESC
    LIMIT $per_page OFFSET $offset
");

if (!$transactions && $db_error === '') {
    $db_error = 'Khong the doc danh sach giao dich game: ' . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>LSGD Game - Admin</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="../assets/jquery/jquery.min.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .admin-header { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); border-bottom: 2px solid rgba(249,115,22,0.4); padding: 12px 0; }
        .admin-header a { color: #febb12 !important; font-weight: 700; margin-right: 14px; text-decoration: none; }
        .page-title { background: linear-gradient(135deg, #f97316, #febb12); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 28px; font-weight: 900; margin: 25px 0 8px; text-align: center; }
        .page-subtitle { color: #aab2c8; font-size: 14px; margin-bottom: 24px; text-align: center; }
        .stats-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
        .stat-box { background: rgba(30,30,60,0.72); border: 1px solid rgba(249,115,22,0.18); border-radius: 12px; flex: 1; min-width: 180px; padding: 15px; text-align: center; }
        .stat-num { color: #febb12; font-size: 22px; font-weight: 900; line-height: 1.2; }
        .stat-label { color: #9ca3af; font-size: 10px; font-weight: 800; letter-spacing: .5px; margin-top: 5px; text-transform: uppercase; }
        .gc-card { background: rgba(30,30,60,0.82); border: 1px solid rgba(249,115,22,0.2); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.28); margin-bottom: 25px; padding: 22px; }
        .filter-grid { align-items: end; display: grid; gap: 10px; grid-template-columns: 1.45fr .75fr .75fr .75fr auto; }
        .filter-grid label { color: #febb12; display: block; font-size: 11px; font-weight: 900; margin-bottom: 6px; text-transform: uppercase; }
        .form-control { background: rgba(15,15,40,0.85) !important; border: 1px solid rgba(249,115,22,0.28) !important; border-radius: 8px !important; color: #e5e7eb !important; min-height: 38px; }
        .form-control:focus { border-color: #f97316 !important; box-shadow: 0 0 0 2px rgba(249,115,22,0.18) !important; }
        .btn-search { background: linear-gradient(135deg, #f97316, #ea580c); border: 0; border-radius: 8px; color: #fff; font-weight: 900; min-height: 38px; padding: 0 20px; }
        .clear-link { color: #cbd5e1; display: inline-block; font-size: 12px; font-weight: 700; margin-top: 12px; text-decoration: none; }
        .clear-link:hover { color: #febb12; text-decoration: none; }
        .table-wrap { overflow-x: auto; }
        .tx-table { border-collapse: separate; border-spacing: 0 8px; min-width: 1180px; width: 100%; }
        .tx-table thead th { background: rgba(249,115,22,0.10); border: 0; color: #febb12; font-size: 11px; font-weight: 900; padding: 10px 8px; text-transform: uppercase; white-space: nowrap; }
        .tx-table tbody tr { background: rgba(20,20,50,0.68); }
        .tx-table tbody tr:hover { background: rgba(249,115,22,0.08); }
        .tx-table td { border: 0; color: #e5e7eb; font-size: 12px; padding: 10px 8px; vertical-align: top; }
        .id-cell { color: #febb12; font-weight: 900; white-space: nowrap; }
        .time-cell { color: #cbd5e1; min-width: 125px; white-space: nowrap; }
        .player-cell { min-width: 170px; }
        .player-link { color: #febb12; display: block; font-weight: 900; text-decoration: none; }
        .player-link:hover { color: #fff1a8; text-decoration: none; }
        .player-id { color: #8b95ad; display: block; font-size: 10px; font-weight: 800; margin-top: 2px; text-transform: uppercase; }
        .item-cell { max-width: 300px; min-width: 250px; word-break: break-word; }
        .gold-chip { background: rgba(254,187,18,0.12); border: 1px solid rgba(254,187,18,0.25); border-radius: 999px; color: #febb12; display: inline-block; font-size: 10px; font-weight: 900; margin-bottom: 6px; padding: 2px 8px; }
        .preview-text { color: #dbe4f0; line-height: 1.45; }
        .muted { color: #8b95ad; }
        details summary { color: #febb12; cursor: pointer; font-size: 11px; font-weight: 900; margin-top: 7px; }
        .detail-box pre, .bag-panel pre { background: rgba(8,8,28,0.78); border: 1px solid rgba(249,115,22,0.14); border-radius: 8px; color: #dbe4f0; font-size: 11px; line-height: 1.45; margin: 8px 0 0; max-height: 220px; overflow: auto; padding: 10px; white-space: pre-wrap; word-break: break-word; }
        .bag-detail { min-width: 160px; }
        .bag-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(260px, 1fr)); margin-top: 10px; width: 650px; }
        .bag-title { color: #febb12; font-size: 11px; font-weight: 900; margin-bottom: 5px; text-transform: uppercase; }
        .admin-alert { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; color: #f87171; font-weight: 800; margin-bottom: 16px; padding: 12px; text-align: center; }
        .empty-state { color: #9ca3af; padding: 34px 10px; text-align: center; }
        .pagination-wrap { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 16px; }
        .pagination-wrap a, .pagination-wrap span { border: 1px solid rgba(249,115,22,0.25); border-radius: 7px; color: #d1d5db; font-size: 12px; font-weight: 900; padding: 6px 10px; text-decoration: none; }
        .pagination-wrap a.active, .pagination-wrap a:hover { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; }
        @media (max-width: 1000px) {
            .filter-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .filter-grid { grid-template-columns: 1fr; }
            .gc-card { padding: 16px; }
            .page-title { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <a href="/admin"><i class="fas fa-arrow-left"></i> Menu admin</a>
            <a href="/admin/players.php"><i class="fas fa-user"></i> Nhan vat</a>
            <a href="/admin/nap.php"><i class="fas fa-check-circle"></i> Duyet nap</a>
        </div>
    </div>

    <div class="container" style="max-width: 1280px;">
        <h1 class="page-title">Lich Su Giao Dich Game</h1>
        <p class="page-subtitle">Theo doi giao dich doi do giua nguoi choi, item hai ben dua va tui truoc/sau khi giao dich.</p>

        <?php if ($db_error !== '') : ?>
            <div class="admin-alert"><?php echo lsgd_h($db_error); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format($total_rows); ?></div>
                <div class="stat-label">Giao dich trong bo loc</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format($today_count); ?></div>
                <div class="stat-label">Giao dich hom nay</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo lsgd_h($summary['latest_time'] ?: '-'); ?></div>
                <div class="stat-label">Moi nhat</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo $page . '/' . $total_pages; ?></div>
                <div class="stat-label">Trang hien tai</div>
            </div>
        </div>

        <div class="gc-card">
            <form method="get" action="">
                <div class="filter-grid">
                    <div>
                        <label for="q">Tim kiem</label>
                        <input class="form-control" id="q" name="q" value="<?php echo lsgd_h($search); ?>" placeholder="Ten nhan vat, ID giao dich, item, vang...">
                    </div>
                    <div>
                        <label for="scope">Pham vi</label>
                        <select class="form-control" id="scope" name="scope">
                            <?php foreach (['all' => 'Tat ca', 'players' => 'Nguoi choi', 'items' => 'Item/tui do'] as $key => $label) : ?>
                                <option value="<?php echo lsgd_h($key); ?>" <?php echo $scope === $key ? 'selected' : ''; ?>><?php echo lsgd_h($label); ?></option>
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
            <?php if ($search !== '' || $scope !== 'all' || $date_from !== '' || $date_to !== '') : ?>
                <a class="clear-link" href="/admin/lsgd.php"><i class="fas fa-times"></i> Xoa bo loc</a>
            <?php endif; ?>
        </div>

        <div class="gc-card table-wrap">
            <?php if ($transactions && $transactions->num_rows > 0) : ?>
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thoi gian</th>
                            <th>Nguoi 1</th>
                            <th>Nguoi 2</th>
                            <th>Nguoi 1 dua</th>
                            <th>Nguoi 2 dua</th>
                            <th>Tui truoc/sau</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tx = $transactions->fetch_assoc()) : ?>
                            <tr>
                                <td class="id-cell">#<?php echo (int)$tx['id']; ?></td>
                                <td class="time-cell"><?php echo lsgd_h($tx['time_tran']); ?></td>
                                <td class="player-cell"><?php echo lsgd_render_player_link($tx['player_1']); ?></td>
                                <td class="player-cell"><?php echo lsgd_render_player_link($tx['player_2']); ?></td>
                                <td class="item-cell"><?php echo lsgd_render_trade_text($tx['item_player_1']); ?></td>
                                <td class="item-cell"><?php echo lsgd_render_trade_text($tx['item_player_2']); ?></td>
                                <td><?php echo lsgd_render_bags($tx); ?></td>
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
                <div class="empty-state">Khong co giao dich game nao khop voi bo loc.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
