<?php
include_once 'set.php';
include_once 'connect.php';
if ($_login == null) { header("Location: /app/login.php"); exit(); }

$_alert = '';

// Ban/Unban
if (isset($_GET['ban_id'])) {
    $bid = intval($_GET['ban_id']);
    $conn->query("UPDATE account SET ban = 1 WHERE id = $bid");
    $_alert = '<div class="alert alert-success">Đã khóa tài khoản!</div>';
}
if (isset($_GET['unban_id'])) {
    $bid = intval($_GET['unban_id']);
    $conn->query("UPDATE account SET ban = 0 WHERE id = $bid");
    $_alert = '<div class="alert alert-success">Đã mở khóa tài khoản!</div>';
}

// Search
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = '';
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where = "WHERE a.username LIKE '%$s%' OR p.name LIKE '%$s%'";
}

$count_sql = "SELECT COUNT(DISTINCT a.id) as total FROM account a LEFT JOIN player p ON a.id = p.account_id $where";
$total = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $per_page));

$sql = "SELECT a.id, a.username, a.ban, a.is_admin, a.admin, a.vnd, a.tongnap, a.vang, a.thoi_vang, a.create_time, a.last_time_login, a.ip_address, a.active, a.vip,
        p.name as player_name, p.gender, p.head, p.id as player_id, p.clan_id, p.data_point
        FROM account a LEFT JOIN player p ON a.id = p.account_id $where ORDER BY a.id ASC LIMIT $per_page OFFSET $offset";
$users = $conn->query($sql);

// Stats
$stats_total = $conn->query("SELECT COUNT(*) as c FROM account")->fetch_assoc()['c'];
$stats_banned = $conn->query("SELECT COUNT(*) as c FROM account WHERE ban=1")->fetch_assoc()['c'];
$stats_online = $conn->query("SELECT COUNT(*) as c FROM account WHERE server_login > -1")->fetch_assoc()['c'];
$stats_admins = $conn->query("SELECT COUNT(*) as c FROM account WHERE admin=1 OR is_admin=1")->fetch_assoc()['c'];

// Detail view
$detail = null;
$detail_player = null;
if (isset($_GET['view_id'])) {
    $vid = intval($_GET['view_id']);
    $detail = $conn->query("SELECT * FROM account WHERE id=$vid")->fetch_assoc();
    if ($detail) {
        $detail_player = $conn->query("SELECT * FROM player WHERE account_id=$vid")->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Quản Lý Người Dùng - Code by Lio</title>
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
        .gc-card { background: rgba(30,30,60,0.8); border: 1px solid rgba(249,115,22,0.2); border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        .gc-card h5 { color: #febb12; font-weight: 700; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(249,115,22,0.15); }
        .gc-card h5 i { margin-right: 8px; }
        .stats-row { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 100px; background: rgba(30,30,60,0.6); border: 1px solid rgba(249,115,22,0.15); border-radius: 12px; padding: 15px; text-align: center; }
        .stat-box .stat-num { font-size: 26px; font-weight: 800; background: linear-gradient(135deg, #f97316, #febb12); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-box .stat-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .form-control { background: rgba(15,15,40,0.8) !important; border: 1px solid rgba(249,115,22,0.2) !important; color: #e0e0e0 !important; border-radius: 8px !important; }
        .form-control:focus { border-color: #f97316 !important; box-shadow: 0 0 0 0.2rem rgba(249,115,22,0.15) !important; }
        .btn-search { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 8px; padding: 8px 20px; font-weight: 600; }
        .u-table { width: 100%; border-collapse: separate; border-spacing: 0 4px; }
        .u-table thead th { background: rgba(249,115,22,0.1); color: #febb12; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 8px; border: none; white-space: nowrap; }
        .u-table thead th:first-child { border-radius: 8px 0 0 8px; }
        .u-table thead th:last-child { border-radius: 0 8px 8px 0; }
        .u-table tbody tr { background: rgba(20,20,50,0.6); transition: all 0.2s ease; }
        .u-table tbody tr:hover { background: rgba(249,115,22,0.08); }
        .u-table tbody td { padding: 8px; border: none; font-size: 12px; vertical-align: middle; }
        .u-table tbody td:first-child { border-radius: 8px 0 0 8px; }
        .u-table tbody td:last-child { border-radius: 0 8px 8px 0; }
        .badge-admin { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 10px; }
        .badge-banned { background: rgba(239,68,68,0.2); color: #f87171; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }
        .badge-active { background: rgba(34,197,94,0.15); color: #4ade80; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }
        .badge-online { background: rgba(34,197,94,0.3); color: #4ade80; font-size: 9px; padding: 2px 6px; border-radius: 10px; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.6; } }
        .btn-view { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); border-radius: 5px; padding: 3px 10px; font-size: 10px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .btn-view:hover { background: rgba(59,130,246,0.3); color: #93bbfd; }
        .btn-ban { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.2); border-radius: 5px; padding: 3px 10px; font-size: 10px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .btn-ban:hover { background: rgba(239,68,68,0.3); color: #fca5a5; }
        .btn-unban { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.2); border-radius: 5px; padding: 3px 10px; font-size: 10px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .pagination-wrap { display: flex; gap: 6px; justify-content: center; margin-top: 15px; flex-wrap: wrap; }
        .pagination-wrap a { background: rgba(30,30,60,0.6); color: #d1d5db; border: 1px solid rgba(249,115,22,0.15); border-radius: 6px; padding: 6px 12px; font-size: 12px; text-decoration: none; font-weight: 600; transition: all 0.2s; }
        .pagination-wrap a:hover, .pagination-wrap a.active { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border-color: #f97316; }
        /* Detail modal */
        .detail-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; }
        .detail-box { background: #1e1e3e; border: 1px solid rgba(249,115,22,0.3); border-radius: 16px; padding: 30px; max-width: 700px; width: 95%; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .detail-box h4 { color: #febb12; font-weight: 800; margin-bottom: 20px; }
        .detail-box .close-btn { float: right; background: rgba(239,68,68,0.2); color: #f87171; border: none; border-radius: 8px; padding: 6px 14px; font-weight: 700; cursor: pointer; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-item { background: rgba(15,15,40,0.6); border: 1px solid rgba(249,115,22,0.1); border-radius: 8px; padding: 10px; }
        .info-item .info-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .info-item .info-value { font-size: 14px; color: #e0e0e0; font-weight: 600; word-break: break-all; }
        .info-item .info-value.gold { color: #febb12; }
        .info-item .info-value.red { color: #f87171; }
        .info-item .info-value.green { color: #4ade80; }
        .alert { border-radius: 10px; font-size: 14px; }
        .alert-success { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
        .lio-badge-footer { background: linear-gradient(135deg, #f97316, #febb12); color: #000; font-weight: 800; padding: 2px 10px; border-radius: 4px; font-size: 10px; text-transform: uppercase; display: inline-block; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } .u-table { font-size: 10px; } }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container"><a href="/admin"><i class="fas fa-arrow-left"></i> Quay lại menu admin</a></div>
    </div>

    <div class="container" style="max-width: 1100px;">
        <div class="page-title"><i class="fas fa-users"></i> QUẢN LÝ NGƯỜI DÙNG</div>
        <div class="page-subtitle">Xem thông tin và quản lý tài khoản người chơi</div>

        <?php echo $_alert; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box"><div class="stat-num"><?php echo $stats_total; ?></div><div class="stat-label">Tổng tài khoản</div></div>
            <div class="stat-box"><div class="stat-num"><?php echo $stats_online; ?></div><div class="stat-label">Đang online</div></div>
            <div class="stat-box"><div class="stat-num"><?php echo $stats_banned; ?></div><div class="stat-label">Bị khóa</div></div>
            <div class="stat-box"><div class="stat-num"><?php echo $stats_admins; ?></div><div class="stat-label">Admin</div></div>
        </div>

        <!-- Search -->
        <div class="gc-card" style="padding:15px 25px;">
            <form method="GET" class="d-flex" style="gap:10px;">
                <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo tên tài khoản hoặc tên nhân vật..." style="flex:1;">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Tìm</button>
                <?php if ($search): ?><a href="users.php" class="btn-search" style="background:rgba(107,114,128,0.3);"><i class="fas fa-times"></i></a><?php endif; ?>
            </form>
        </div>

        <!-- User table -->
        <div class="gc-card">
            <h5><i class="fas fa-list"></i> Danh Sách (<?php echo $total; ?> tài khoản<?php echo $search ? " - Tìm: \"".htmlspecialchars($search)."\"" : ''; ?>)</h5>
            <div style="overflow-x:auto;">
                <table class="u-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Tài khoản</th><th>Nhân vật</th><th>Trạng thái</th><th>VNĐ</th><th>Vàng</th><th>Đăng nhập cuối</th><th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($users && $users->num_rows > 0): while ($u = $users->fetch_assoc()):
                        $is_ban = $u['ban'] == 1;
                        $is_adm = ($u['admin'] == 1 || $u['is_admin'] == 1);
                    ?>
                        <tr>
                            <td style="color:#6b7280;"><?php echo $u['id']; ?></td>
                            <td style="font-weight:700;color:#e0e0e0;"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td style="color:#febb12;"><?php echo $u['player_name'] ? htmlspecialchars($u['player_name']) : '<span style="color:#6b7280;">—</span>'; ?></td>
                            <td>
                                <?php if ($is_adm): ?><span class="badge-admin">👑 ADMIN</span><?php endif; ?>
                                <?php if ($is_ban): ?><span class="badge-banned">🔒 Khóa</span><?php else: ?><span class="badge-active">✅ Active</span><?php endif; ?>
                            </td>
                            <td style="color:#4ade80;"><?php echo number_format($u['vnd']); ?></td>
                            <td style="color:#febb12;"><?php echo number_format($u['vang']); ?></td>
                            <td style="color:#9ca3af;font-size:11px;"><?php echo date('d/m/Y H:i', strtotime($u['last_time_login'])); ?></td>
                            <td style="white-space:nowrap;">
                                <a href="?view_id=<?php echo $u['id']; ?>&q=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="btn-view"><i class="fas fa-eye"></i> Xem</a>
                                <?php if ($is_ban): ?>
                                    <a href="?unban_id=<?php echo $u['id']; ?>&q=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="btn-unban" onclick="return confirm('Mở khóa tài khoản <?php echo htmlspecialchars($u['username']); ?>?');"><i class="fas fa-unlock"></i></a>
                                <?php else: ?>
                                    <a href="?ban_id=<?php echo $u['id']; ?>&q=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="btn-ban" onclick="return confirm('Khóa tài khoản <?php echo htmlspecialchars($u['username']); ?>?');"><i class="fas fa-lock"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="8" style="text-align:center;color:#6b7280;padding:30px;">Không tìm thấy người dùng nào.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrap">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div style="text-align:center;padding:20px;border-top:1px solid rgba(249,115,22,0.1);margin-top:20px;">
            <small style="color:#6b7280;">IP: <?php echo $_IP; ?></small><br>
            <span class="lio-badge-footer">Code by Lio</span>
        </div>
    </div>

    <!-- Detail Modal -->
    <?php if ($detail): ?>
    <div class="detail-overlay" onclick="if(event.target===this)window.location.href='?q=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>'">
        <div class="detail-box">
            <button class="close-btn" onclick="window.location.href='?q=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>'"><i class="fas fa-times"></i> Đóng</button>
            <h4><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($detail['username']); ?></h4>

            <h6 style="color:#f97316;margin:15px 0 10px;"><i class="fas fa-id-card"></i> Thông tin tài khoản</h6>
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Account ID</div><div class="info-value"><?php echo $detail['id']; ?></div></div>
                <div class="info-item"><div class="info-label">Username</div><div class="info-value"><?php echo htmlspecialchars($detail['username']); ?></div></div>
                <div class="info-item"><div class="info-label">Trạng thái</div><div class="info-value <?php echo $detail['ban'] ? 'red' : 'green'; ?>"><?php echo $detail['ban'] ? '🔒 Đã khóa' : '✅ Hoạt động'; ?></div></div>
                <div class="info-item"><div class="info-label">Admin</div><div class="info-value"><?php echo ($detail['admin'] == 1 || $detail['is_admin'] == 1) ? '👑 Có' : 'Không'; ?></div></div>
                <div class="info-item"><div class="info-label">VNĐ</div><div class="info-value gold"><?php echo number_format($detail['vnd']); ?></div></div>
                <div class="info-item"><div class="info-label">Vàng</div><div class="info-value gold"><?php echo number_format($detail['vang']); ?></div></div>
                <div class="info-item"><div class="info-label">Thỏi vàng</div><div class="info-value gold"><?php echo number_format($detail['thoi_vang']); ?></div></div>
                <div class="info-item"><div class="info-label">Tổng nạp</div><div class="info-value green"><?php echo number_format($detail['tongnap']); ?></div></div>
                <div class="info-item"><div class="info-label">VIP</div><div class="info-value"><?php echo $detail['vip']; ?></div></div>
                <div class="info-item"><div class="info-label">Tích điểm</div><div class="info-value"><?php echo number_format($detail['tichdiem']); ?></div></div>
                <div class="info-item"><div class="info-label">IP</div><div class="info-value"><?php echo htmlspecialchars($detail['ip_address'] ?? '—'); ?></div></div>
                <div class="info-item"><div class="info-label">Server login</div><div class="info-value"><?php echo $detail['server_login'] > -1 ? '<span class="green">Online (SV '.$detail['server_login'].')</span>' : 'Offline'; ?></div></div>
                <div class="info-item"><div class="info-label">Ngày tạo</div><div class="info-value"><?php echo date('d/m/Y H:i', strtotime($detail['create_time'])); ?></div></div>
                <div class="info-item"><div class="info-label">Đăng nhập cuối</div><div class="info-value"><?php echo date('d/m/Y H:i', strtotime($detail['last_time_login'])); ?></div></div>
            </div>

            <?php if ($detail_player): ?>
            <h6 style="color:#f97316;margin:20px 0 10px;"><i class="fas fa-gamepad"></i> Thông tin nhân vật</h6>
            <?php
                $dp = json_decode($detail_player['data_point'], true);
                $gender_text = ['Trái Đất', 'Namek', 'Xayda'];
                $g = isset($gender_text[$detail_player['gender']]) ? $gender_text[$detail_player['gender']] : '?';
            ?>
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Tên nhân vật</div><div class="info-value gold"><?php echo htmlspecialchars($detail_player['name']); ?></div></div>
                <div class="info-item"><div class="info-label">Player ID</div><div class="info-value"><?php echo $detail_player['id']; ?></div></div>
                <div class="info-item"><div class="info-label">Hành tinh</div><div class="info-value"><?php echo $g; ?></div></div>
                <div class="info-item"><div class="info-label">Clan ID</div><div class="info-value"><?php echo $detail_player['clan_id'] > 0 ? $detail_player['clan_id'] : 'Không'; ?></div></div>
                <?php if (is_array($dp)): ?>
                <div class="info-item"><div class="info-label">Sức mạnh</div><div class="info-value"><?php echo number_format($dp[1] ?? 0); ?></div></div>
                <div class="info-item"><div class="info-label">Tiềm năng</div><div class="info-value"><?php echo number_format($dp[2] ?? 0); ?></div></div>
                <div class="info-item"><div class="info-label">HP</div><div class="info-value green"><?php echo number_format($dp[5] ?? 0); ?></div></div>
                <div class="info-item"><div class="info-label">MP</div><div class="info-value" style="color:#60a5fa;"><?php echo number_format($dp[6] ?? 0); ?></div></div>
                <div class="info-item"><div class="info-label">Sức đánh gốc</div><div class="info-value red"><?php echo number_format($dp[7] ?? 0); ?></div></div>
                <div class="info-item"><div class="info-label">Giáp gốc</div><div class="info-value"><?php echo number_format($dp[8] ?? 0); ?></div></div>
                <div class="info-item"><div class="info-label">Chí mạng</div><div class="info-value"><?php echo number_format($dp[9] ?? 0); ?></div></div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p style="color:#6b7280;margin-top:15px;"><i class="fas fa-info-circle"></i> Tài khoản chưa tạo nhân vật.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
