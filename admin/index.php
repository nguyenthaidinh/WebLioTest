<?php
include_once 'set.php';
include_once 'connect.php';

if ($_login == null) {
    header("Location: /app/login.php");
    exit();
}

function admin_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_count_query($conn, $sql) {
    try {
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_row();
            return (int)($row[0] ?? 0);
        }
    } catch (Throwable $e) {
        return 0;
    }

    return 0;
}

$total_accounts = admin_count_query($conn, "SELECT COUNT(*) FROM account");
$total_players = admin_count_query($conn, "SELECT COUNT(*) FROM player");
$pending_recharges = admin_count_query($conn, "SELECT COUNT(*) FROM bank_transfers WHERE status = 'pending' AND is_credited = 0");
$today_trades = admin_count_query($conn, "SELECT COUNT(*) FROM history_transaction WHERE time_tran >= CURDATE() AND time_tran < DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
$today_gold_spends = admin_count_query($conn, "SELECT COUNT(*) FROM gold_bar_spend_history WHERE created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)");

$admin_name = $_username ?? 'admin';
$quick_items = [
    [
        'title' => 'Duyệt',
        'desc' => 'Xử lý các giao dịch nạp tiền đang chờ.',
        'href' => '/admin/nap.php',
        'icon' => 'fas fa-wallet',
        'meta' => $pending_recharges . ' chờ duyệt',
        'priority' => true,
    ],
    [
        'title' => 'Lịch sử GD game',
        'desc' => 'Xem lịch sử giao dịch đổi đồ trong game.',
        'href' => '/admin/lsgd.php',
        'icon' => 'fas fa-history',
        'meta' => $today_trades . ' hôm nay',
        'priority' => false,
    ],
    [
        'title' => 'Lịch sử tiêu Thỏi Vàng',
        'desc' => 'Đối soát từng lần người chơi tiêu Thỏi Vàng trong 3 ngày gần nhất.',
        'href' => '/admin/lich-su-tieu-thoi-vang.php',
        'icon' => 'fas fa-coins',
        'meta' => number_format($today_gold_spends, 0, ',', '.') . ' lượt hôm nay',
        'priority' => false,
    ],
    [
        'title' => 'Người dùng',
        'desc' => 'Tra cứu tài khoản, số dư và trạng thái người dùng.',
        'href' => '/admin/users.php',
        'icon' => 'fas fa-users',
        'meta' => number_format($total_accounts, 0, ',', '.') . ' tài khoản',
        'priority' => false,
    ],
    [
        'title' => 'Nhân vật',
        'desc' => 'Xem và sửa thông tin nhân vật.',
        'href' => '/admin/players.php',
        'icon' => 'fas fa-user',
        'meta' => number_format($total_players, 0, ',', '.') . ' nhân vật',
        'priority' => false,
    ],
    [
        'title' => 'Buff vật phẩm',
        'desc' => 'Thêm vật phẩm vào hành trang người chơi.',
        'href' => '/admin/vatpham.php',
        'icon' => 'fas fa-box',
        'meta' => 'Item',
        'priority' => false,
    ],
    [
        'title' => 'Cộng chỉ số',
        'desc' => 'Chỉnh sức mạnh, tiềm năng, HP, KI.',
        'href' => '/admin/chiso.php',
        'icon' => 'fas fa-chart-line',
        'meta' => 'Chỉ số',
        'priority' => false,
    ],
    [
        'title' => 'Lượt quay',
        'desc' => 'Quản lý lượt quay và quà vòng quay.',
        'href' => '/admin/luotquay.php',
        'icon' => 'fas fa-sync-alt',
        'meta' => 'Vòng quay',
        'priority' => false,
    ],
    [
        'title' => 'Vòng quay',
        'desc' => 'Cấu hình phần thưởng vòng quay.',
        'href' => '/admin/tyle-vongquay.php',
        'icon' => 'fas fa-sliders-h',
        'meta' => 'Cấu hình',
        'priority' => false,
    ],
    [
        'title' => 'Gift code',
        'desc' => 'Tạo và quản lý giftcode cho người chơi.',
        'href' => '/admin/giftcode.php',
        'icon' => 'fas fa-gift',
        'meta' => 'Code',
        'priority' => false,
    ],
];

$sidebar_items = [
    ['label' => 'Diễn đàn', 'href' => '/forum.php', 'icon' => 'fas fa-comments'],
    ['label' => 'Duyệt', 'href' => '/admin/nap.php', 'icon' => 'fas fa-wallet'],
    ['label' => 'Lịch sử GD game', 'href' => '/admin/lsgd.php', 'icon' => 'fas fa-history'],
    ['label' => 'Tiêu Thỏi Vàng', 'href' => '/admin/lich-su-tieu-thoi-vang.php', 'icon' => 'fas fa-coins'],
    ['label' => 'Người dùng', 'href' => '/admin/users.php', 'icon' => 'fas fa-users'],
    ['label' => 'Nhân vật', 'href' => '/admin/players.php', 'icon' => 'fas fa-user'],
    ['label' => 'Đăng xuất', 'href' => '/admin?out=1', 'icon' => 'fas fa-sign-out-alt'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Panel - Lio</title>
    <meta name="description" content="Bảng điều khiển admin Lio">
    <meta name="author" content="Lio">
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="../assets/jquery/jquery.min.js"></script>
    <script src="../assets/notify/notify.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        :root {
            --bg: #eef2f7;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --text: #111827;
            --muted: #64748b;
            --line: #e2e8f0;
            --nav: #101827;
            --nav-2: #162033;
            --accent: #f59e0b;
            --accent-dark: #b45309;
            --success: #16a34a;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', Verdana, sans-serif;
            font-size: 14px;
            margin: 0;
            min-height: 100vh;
        }
        a { text-decoration: none; }
        .admin-layout {
            display: grid;
            grid-template-columns: 264px minmax(0, 1fr);
            min-height: 100vh;
        }
        .sidebar {
            background: var(--nav);
            color: #e5e7eb;
            display: flex;
            flex-direction: column;
            padding: 18px;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .brand {
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            gap: 12px;
            padding: 4px 2px 18px;
        }
        .brand-mark {
            align-items: center;
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            border-radius: 8px;
            color: #111827;
            display: inline-flex;
            font-size: 18px;
            font-weight: 900;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
        .brand-title {
            color: #fff;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.1;
        }
        .brand-sub {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .nav-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .7px;
            margin: 22px 0 8px;
            text-transform: uppercase;
        }
        .nav-list {
            display: grid;
            gap: 6px;
        }
        .nav-link {
            align-items: center;
            border-radius: 8px;
            color: #cbd5e1;
            display: flex;
            font-size: 13px;
            font-weight: 800;
            gap: 10px;
            padding: 10px 12px;
        }
        .nav-link i {
            color: #94a3b8;
            width: 18px;
        }
        .nav-link:hover,
        .nav-link.active {
            background: var(--nav-2);
            color: #fff;
            text-decoration: none;
        }
        .nav-link:hover i,
        .nav-link.active i { color: var(--accent); }
        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,0.08);
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.6;
            margin-top: auto;
            padding-top: 14px;
            word-break: break-word;
        }
        .main {
            min-width: 0;
            padding: 24px 28px 34px;
        }
        .topbar {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 22px;
        }
        .eyebrow {
            color: var(--accent-dark);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .page-title {
            color: var(--text);
            font-size: 28px;
            font-weight: 900;
            margin: 4px 0;
        }
        .page-desc {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }
        .user-chip {
            align-items: center;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 999px;
            display: flex;
            gap: 10px;
            padding: 8px 12px;
            white-space: nowrap;
        }
        .user-dot {
            background: var(--success);
            border-radius: 50%;
            height: 9px;
            width: 9px;
        }
        .user-name {
            color: var(--text);
            font-size: 13px;
            font-weight: 900;
        }
        .stats-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 22px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
        }
        .stat-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .stat-icon {
            align-items: center;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            color: var(--accent-dark);
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }
        .stat-note {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }
        .stat-number {
            color: var(--text);
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
        }
        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            margin-top: 7px;
            text-transform: uppercase;
        }
        .toolbar {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin: 6px 0 12px;
        }
        .section-title {
            color: var(--text);
            font-size: 18px;
            font-weight: 900;
            margin: 0;
        }
        .toolbar-hint {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }
        .actions-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .action-card {
            align-items: flex-start;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--text);
            display: grid;
            gap: 12px;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            min-height: 110px;
            padding: 16px;
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .action-card:hover {
            border-color: #f59e0b;
            box-shadow: 0 12px 28px rgba(15,23,42,0.10);
            color: var(--text);
            text-decoration: none;
            transform: translateY(-1px);
        }
        .action-card.priority {
            border-color: #f59e0b;
            box-shadow: inset 3px 0 0 #f59e0b;
        }
        .action-icon {
            align-items: center;
            background: var(--surface-2);
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--accent-dark);
            display: inline-flex;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
        .action-title {
            color: var(--text);
            font-size: 15px;
            font-weight: 900;
            margin: 0 0 5px;
        }
        .action-desc {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
            margin: 0;
        }
        .action-meta {
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            padding: 5px 9px;
            white-space: nowrap;
        }
        .admin-footer {
            color: var(--muted);
            font-size: 12px;
            margin-top: 22px;
            text-align: right;
        }
        .lio-badge {
            background: #111827;
            border-radius: 5px;
            color: #fbbf24;
            display: inline-block;
            font-size: 10px;
            font-weight: 900;
            margin-left: 8px;
            padding: 3px 8px;
            text-transform: uppercase;
        }
        @media (max-width: 1180px) {
            .admin-layout { grid-template-columns: 226px minmax(0, 1fr); }
            .actions-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 820px) {
            .admin-layout { display: block; }
            .sidebar {
                height: auto;
                position: relative;
            }
            .nav-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .sidebar-footer { display: none; }
            .main { padding: 20px 15px 28px; }
            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
            .user-chip { width: 100%; }
        }
        @media (max-width: 560px) {
            .nav-list,
            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }
            .action-card {
                grid-template-columns: 40px minmax(0, 1fr);
            }
            .action-meta {
                grid-column: 2;
                justify-self: start;
            }
            .toolbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">L</div>
                <div>
                    <div class="brand-title">Lio Admin</div>
                    <div class="brand-sub">Quản trị server</div>
                </div>
            </div>

            <div class="nav-label">Menu nhanh</div>
            <nav class="nav-list">
                <a class="nav-link active" href="/admin"><i class="fas fa-home"></i> Tổng quan</a>
                <?php foreach ($sidebar_items as $item) : ?>
                    <a class="nav-link" href="<?php echo admin_h($item['href']); ?>">
                        <i class="<?php echo admin_h($item['icon']); ?>"></i>
                        <?php echo admin_h($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <div><strong>Admin:</strong> <?php echo admin_h($admin_name); ?></div>
                <div><strong>IP:</strong> <?php echo admin_h($_IP); ?></div>
            </div>
        </aside>

        <main class="main">
            <header class="topbar">
                <div>
                    <div class="eyebrow">Bảng điều khiển</div>
                    <h1 class="page-title">Tổng quan admin</h1>
                    <p class="page-desc">Quản lý server nhanh, rõ ràng và dễ bấm hơn.</p>
                </div>
                <div class="user-chip">
                    <span class="user-dot"></span>
                    <span class="user-name"><?php echo admin_h($admin_name); ?></span>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-note">Tổng</div>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_accounts); ?></div>
                    <div class="stat-label">Tài khoản</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-user"></i></div>
                        <div class="stat-note">Game</div>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_players); ?></div>
                    <div class="stat-label">Nhân vật</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                        <div class="stat-note">Cần xử lý</div>
                    </div>
                    <div class="stat-number"><?php echo number_format($pending_recharges); ?></div>
                    <div class="stat-label">Chờ duyệt</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
                        <div class="stat-note">Hôm nay</div>
                    </div>
                    <div class="stat-number"><?php echo number_format($today_trades); ?></div>
                    <div class="stat-label">GD game</div>
                </div>
            </section>

            <div class="toolbar">
                <h2 class="section-title">Chức năng quản trị</h2>
                <div class="toolbar-hint">Bấm vào từng mục để mở trang xử lý</div>
            </div>

            <section class="actions-grid">
                <?php foreach ($quick_items as $item) : ?>
                    <a class="action-card <?php echo !empty($item['priority']) ? 'priority' : ''; ?>" href="<?php echo admin_h($item['href']); ?>">
                        <div class="action-icon"><i class="<?php echo admin_h($item['icon']); ?>"></i></div>
                        <div>
                            <h3 class="action-title"><?php echo admin_h($item['title']); ?></h3>
                            <p class="action-desc"><?php echo admin_h($item['desc']); ?></p>
                        </div>
                        <div class="action-meta"><?php echo admin_h($item['meta']); ?></div>
                    </a>
                <?php endforeach; ?>
            </section>

            <footer class="admin-footer">
                IP: <?php echo admin_h($_IP); ?>
                <span class="lio-badge">Code by Lio</span>
            </footer>
        </main>
    </div>

    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/main.js"></script>
</body>
</html>
