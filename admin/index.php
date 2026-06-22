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

$menu_items = [
    [
        'title' => 'Buff vat pham',
        'desc' => 'Them vat pham vao hanh trang nguoi choi.',
        'href' => '/admin/vatpham.php',
        'icon' => 'fas fa-box-open',
        'tag' => 'Item',
    ],
    [
        'title' => 'Cong chi so',
        'desc' => 'Chinh suc manh, tiem nang, HP, KI va sat thuong.',
        'href' => '/admin/chiso.php',
        'icon' => 'fas fa-bolt',
        'tag' => 'Nhan vat',
    ],
    [
        'title' => 'Cong tien',
        'desc' => 'Duyet va xu ly yeu cau nap tien.',
        'href' => '/admin/nap.php',
        'icon' => 'fas fa-coins',
        'tag' => $pending_recharges . ' cho duyet',
    ],
    [
        'title' => 'LSGD game',
        'desc' => 'Xem lich su giao dich doi do trong game.',
        'href' => '/admin/lsgd.php',
        'icon' => 'fas fa-exchange-alt',
        'tag' => 'Game',
    ],
    [
        'title' => 'Luot quay',
        'desc' => 'Quan ly luot quay va qua vong quay.',
        'href' => '/admin/luotquay.php',
        'icon' => 'fas fa-sync-alt',
        'tag' => 'Vong quay',
    ],
    [
        'title' => 'Ti le quay',
        'desc' => 'Cau hinh ti le phan thuong vong quay.',
        'href' => '/admin/tyle-vongquay.php',
        'icon' => 'fas fa-sliders-h',
        'tag' => 'Cau hinh',
    ],
    [
        'title' => 'Gift code',
        'desc' => 'Tao va quan ly giftcode cho nguoi choi.',
        'href' => '/admin/giftcode.php',
        'icon' => 'fas fa-gift',
        'tag' => 'Code',
    ],
    [
        'title' => 'Nguoi dung',
        'desc' => 'Tra cuu tai khoan, so du va trang thai user.',
        'href' => '/admin/users.php',
        'icon' => 'fas fa-users-cog',
        'tag' => number_format($total_accounts) . ' TK',
    ],
    [
        'title' => 'Nhan vat',
        'desc' => 'Xem va sua thong tin nhan vat trong game.',
        'href' => '/admin/players.php',
        'icon' => 'fas fa-user-astronaut',
        'tag' => number_format($total_players) . ' player',
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Panel - Lio</title>
    <meta name="description" content="Bang dieu khien admin Lio">
    <meta name="author" content="Lio">
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="../assets/jquery/jquery.min.js"></script>
    <script src="../assets/notify/notify.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(249,115,22,0.20), transparent 32%),
                radial-gradient(circle at top right, rgba(254,187,18,0.12), transparent 28%),
                #17172b;
            color: #e5e7eb;
            font-family: 'Segoe UI', Verdana, sans-serif;
            min-height: 100vh;
        }
        .admin-topbar {
            background: rgba(15,15,40,0.78);
            border-bottom: 1px solid rgba(249,115,22,0.25);
            box-shadow: 0 10px 30px rgba(0,0,0,0.22);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .admin-topbar .topbar-inner {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }
        .brand {
            align-items: center;
            color: #febb12;
            display: flex;
            font-size: 15px;
            font-weight: 900;
            gap: 10px;
            letter-spacing: .2px;
            text-decoration: none;
        }
        .brand:hover { color: #ffe08a; text-decoration: none; }
        .brand img { height: 34px; width: auto; }
        .topbar-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }
        .topbar-pill {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px;
            color: #dbe4f0;
            font-size: 12px;
            font-weight: 800;
            padding: 7px 12px;
            text-decoration: none;
        }
        .topbar-pill:hover { background: rgba(249,115,22,0.18); color: #fff; text-decoration: none; }
        .admin-shell {
            margin: 0 auto;
            max-width: 1180px;
            padding: 28px 15px 34px;
        }
        .hero-panel {
            background: linear-gradient(135deg, rgba(30,30,60,0.92), rgba(22,22,48,0.92));
            border: 1px solid rgba(249,115,22,0.22);
            border-radius: 16px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.32);
            display: grid;
            gap: 18px;
            grid-template-columns: 1.2fr .8fr;
            overflow: hidden;
            padding: 24px;
            position: relative;
        }
        .hero-panel:before {
            background: linear-gradient(135deg, rgba(249,115,22,0.18), rgba(254,187,18,0.08));
            border-radius: 999px;
            content: "";
            height: 220px;
            position: absolute;
            right: -80px;
            top: -110px;
            width: 220px;
        }
        .hero-kicker {
            color: #febb12;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .9px;
            text-transform: uppercase;
        }
        .hero-title {
            color: #fff;
            font-size: 32px;
            font-weight: 900;
            margin: 8px 0;
        }
        .hero-subtitle {
            color: #aab2c8;
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
            max-width: 620px;
        }
        .hero-meta {
            align-content: start;
            display: grid;
            gap: 10px;
            position: relative;
            z-index: 1;
        }
        .meta-card {
            background: rgba(15,15,40,0.68);
            border: 1px solid rgba(249,115,22,0.16);
            border-radius: 12px;
            padding: 12px 14px;
        }
        .meta-label {
            color: #8f9bb5;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .6px;
            text-transform: uppercase;
        }
        .meta-value {
            color: #febb12;
            font-size: 13px;
            font-weight: 900;
            margin-top: 3px;
            word-break: break-word;
        }
        .stats-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin: 18px 0;
        }
        .stat-card {
            background: rgba(30,30,60,0.76);
            border: 1px solid rgba(249,115,22,0.16);
            border-radius: 12px;
            padding: 16px;
        }
        .stat-icon {
            align-items: center;
            background: linear-gradient(135deg, #f97316, #febb12);
            border-radius: 10px;
            color: #111827;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            margin-bottom: 10px;
            width: 34px;
        }
        .stat-number {
            color: #fff;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.1;
        }
        .stat-label {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 800;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .section-title {
            align-items: center;
            color: #fff;
            display: flex;
            font-size: 18px;
            font-weight: 900;
            gap: 8px;
            margin: 18px 0 12px;
        }
        .section-title i { color: #febb12; }
        .menu-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .admin-card {
            background: rgba(30,30,60,0.78);
            border: 1px solid rgba(249,115,22,0.16);
            border-radius: 12px;
            color: #dbe4f0;
            display: block;
            min-height: 154px;
            overflow: hidden;
            padding: 18px;
            position: relative;
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease, background .18s ease;
        }
        .admin-card:hover {
            background: rgba(38,38,72,0.90);
            border-color: rgba(249,115,22,0.48);
            color: #fff;
            text-decoration: none;
            transform: translateY(-2px);
        }
        .admin-card:after {
            background: rgba(249,115,22,0.12);
            border-radius: 999px;
            content: "";
            height: 95px;
            position: absolute;
            right: -45px;
            top: -45px;
            width: 95px;
        }
        .card-head {
            align-items: center;
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .card-icon {
            align-items: center;
            background: rgba(249,115,22,0.14);
            border: 1px solid rgba(249,115,22,0.22);
            border-radius: 12px;
            color: #febb12;
            display: inline-flex;
            flex: 0 0 42px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
        .card-title {
            color: #fff;
            font-size: 15px;
            font-weight: 900;
            margin: 0;
            text-transform: uppercase;
        }
        .card-desc {
            color: #aab2c8;
            font-size: 13px;
            line-height: 1.55;
            margin: 14px 0 0;
            min-height: 40px;
            position: relative;
            z-index: 1;
        }
        .card-foot {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            position: relative;
            z-index: 1;
        }
        .card-tag {
            background: rgba(254,187,18,0.13);
            border: 1px solid rgba(254,187,18,0.20);
            border-radius: 999px;
            color: #febb12;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 8px;
            text-transform: uppercase;
        }
        .card-open {
            color: #febb12;
            font-size: 12px;
            font-weight: 900;
        }
        .admin-footer {
            border-top: 1px solid rgba(249,115,22,0.12);
            color: #8f9bb5;
            font-size: 12px;
            margin-top: 24px;
            padding-top: 18px;
            text-align: center;
        }
        .lio-badge {
            background: linear-gradient(135deg,#f97316,#febb12);
            border-radius: 5px;
            color: #111827;
            display: inline-block;
            font-size: 10px;
            font-weight: 900;
            margin-top: 8px;
            padding: 3px 10px;
            text-transform: uppercase;
        }
        @media (max-width: 991px) {
            .hero-panel { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .menu-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .admin-topbar .topbar-inner { align-items: flex-start; flex-direction: column; }
            .topbar-actions { justify-content: flex-start; }
            .hero-panel { padding: 18px; }
            .hero-title { font-size: 25px; }
            .stats-grid, .menu-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <div class="container topbar-inner">
            <a class="brand" href="/admin">
                <img src="../image/logo.png" alt="Lio">
                <span>Admin Lio</span>
            </a>
            <div class="topbar-actions">
                <a class="topbar-pill" href="/forum.php"><i class="fas fa-comments"></i> Quay lai dien dan</a>
                <a class="topbar-pill" href="/admin?out=1"><i class="fas fa-sign-out-alt"></i> Dang xuat</a>
            </div>
        </div>
    </div>

    <main class="admin-shell">
        <section class="hero-panel">
            <div>
                <div class="hero-kicker">Bang dieu khien</div>
                <h1 class="hero-title">Menu admin</h1>
                <p class="hero-subtitle">
                    Quan ly tai khoan, nhan vat, nap tien, giftcode va lich su giao dich game trong mot man hinh gon gang hon.
                </p>
            </div>
            <div class="hero-meta">
                <div class="meta-card">
                    <div class="meta-label">Admin dang nhap</div>
                    <div class="meta-value"><?php echo admin_h($_username ?? 'admin'); ?></div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">IP hien tai</div>
                    <div class="meta-value"><?php echo admin_h($_IP); ?></div>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo number_format($total_accounts); ?></div>
                <div class="stat-label">Tai khoan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-gamepad"></i></div>
                <div class="stat-number"><?php echo number_format($total_players); ?></div>
                <div class="stat-label">Nhan vat</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-number"><?php echo number_format($pending_recharges); ?></div>
                <div class="stat-label">Nap cho duyet</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-random"></i></div>
                <div class="stat-number"><?php echo number_format($today_trades); ?></div>
                <div class="stat-label">GD game hom nay</div>
            </div>
        </section>

        <div class="section-title"><i class="fas fa-th-large"></i> Chuc nang nhanh</div>
        <section class="menu-grid">
            <?php foreach ($menu_items as $item) : ?>
                <a class="admin-card" href="<?php echo admin_h($item['href']); ?>">
                    <div class="card-head">
                        <div class="card-icon"><i class="<?php echo admin_h($item['icon']); ?>"></i></div>
                        <h2 class="card-title"><?php echo admin_h($item['title']); ?></h2>
                    </div>
                    <p class="card-desc"><?php echo admin_h($item['desc']); ?></p>
                    <div class="card-foot">
                        <span class="card-tag"><?php echo admin_h($item['tag']); ?></span>
                        <span class="card-open">Mo <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>

        <footer class="admin-footer">
            <div>IP: <?php echo admin_h($_IP); ?></div>
            <div class="lio-badge">Code by Lio</div>
        </footer>
    </main>

    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/main.js"></script>
</body>
</html>
