<?php
include_once 'set.php';
include_once 'connect.php';
if ($_login == null) { header("Location: /app/login.php"); exit(); }

$_alert = '';

// Handle edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_player'])) {
    $pid = intval($_POST['player_id']);
    $aid = intval($_POST['account_id']);
    $new_name = $conn->real_escape_string(trim($_POST['p_name']));
    $new_gender = intval($_POST['p_gender']);
    $sm = floatval($_POST['p_sm']);
    $tn = floatval($_POST['p_tn']);
    $hp = floatval($_POST['p_hp']);
    $mp = floatval($_POST['p_mp']);
    $sdg = floatval($_POST['p_sdg']);
    $giap = floatval($_POST['p_giap']);
    $cm = floatval($_POST['p_cm']);
    $gold = floatval($_POST['p_gold']);
    $gem = floatval($_POST['p_gem']);
    $ruby = intval($_POST['p_ruby']);
    $vnd = intval($_POST['p_vnd']);
    $vip = intval($_POST['p_vip']);

    // Get current data_point and data_inventory
    $cur = $conn->query("SELECT data_point, data_inventory FROM player WHERE id=$pid")->fetch_assoc();
    if ($cur) {
        $dp = json_decode($cur['data_point'], true);
        $di = json_decode($cur['data_inventory'], true);
        $dp[1] = $sm; $dp[2] = $tn; $dp[5] = $hp; $dp[6] = $mp;
        $dp[7] = $sdg; $dp[8] = $giap; $dp[9] = $cm;
        $di[0] = $gold; $di[1] = $gem; $di[2] = $ruby;
        $new_dp = json_encode($dp);
        $new_di = json_encode($di);

        $conn->query("UPDATE player SET name='$new_name', gender=$new_gender, data_point='$new_dp', data_inventory='$new_di' WHERE id=$pid");
        $conn->query("UPDATE account SET vnd=$vnd, vip=$vip WHERE id=$aid");
        $_alert = '<div class="alert alert-success" style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);border-radius:10px;padding:12px;text-align:center;margin-bottom:15px;">✅ Đã cập nhật nhân vật <strong>' . htmlspecialchars($new_name) . '</strong> thành công!</div>';
    }
}

// Load item_template for name lookup
$item_names = [];
$ir = $conn->query("SELECT id, NAME as name, icon_id FROM item_template");
if ($ir) { while ($r = $ir->fetch_assoc()) { $item_names[$r['id']] = $r; } }

// Gender stats
$g_counts = [0 => 0, 1 => 0, 2 => 0];
$gr = $conn->query("SELECT gender, COUNT(*) as c FROM player GROUP BY gender");
if ($gr) { while ($r = $gr->fetch_assoc()) { $g_counts[$r['gender']] = $r['c']; } }
$total_players = array_sum($g_counts);

// Search
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = $search !== '' ? "WHERE p.name LIKE '%" . $conn->real_escape_string($search) . "%' OR a.username LIKE '%" . $conn->real_escape_string($search) . "%'" : '';

// Get players
$sql = "SELECT p.*, a.username, a.vang, a.thoi_vang, a.vnd, a.vip, a.ban
        FROM player p JOIN account a ON a.id = p.account_id $where ORDER BY p.id ASC";
$players = $conn->query($sql);

// Helper: parse items string to array of [id, qty]
function parseItems($raw) {
    if (empty($raw)) return [];
    $items = [];
    // Format: ["[id,qty,\"...\",timestamp]", ...]
    preg_match_all('/\[(-?\d+),(\d+),/', $raw, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $id = intval($m[1]);
        $qty = intval($m[2]);
        if ($id >= 0) $items[] = ['id' => $id, 'qty' => $qty];
    }
    return $items;
}

function formatBigNum($n) {
    if ($n >= 1000000000000) return round($n / 1000000000000, 1) . ' Nghìn Tỷ';
    if ($n >= 1000000000) return round($n / 1000000000, 1) . ' Tỷ';
    if ($n >= 1000000) return round($n / 1000000, 1) . ' Tr';
    if ($n >= 1000) return round($n / 1000, 1) . ' K';
    return number_format($n);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Quản Lý Nhân Vật - Code by Lio</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet">
    <script src="../assets/jquery/jquery.min.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .admin-header { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); border-bottom: 2px solid rgba(249,115,22,0.4); padding: 12px 0; }
        .admin-header a { color: #febb12 !important; font-weight: 600; text-decoration: none; }
        .page-title { background: linear-gradient(135deg, #f97316, #febb12); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; font-size: 26px; text-align: center; margin: 25px 0 5px; }
        .page-sub { text-align: center; color: #9ca3af; font-size: 13px; margin-bottom: 20px; }

        /* Stats bar */
        .stats-bar { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .total-badge { background: linear-gradient(135deg, #f97316, #febb12); color: #000; font-weight: 800; padding: 8px 25px; border-radius: 30px; font-size: 15px; }
        .planet-badge { background: rgba(30,30,60,0.8); border: 1px solid rgba(249,115,22,0.2); border-radius: 12px; padding: 8px 18px; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .planet-badge img { width: 28px; height: 28px; }

        /* Search */
        .search-bar { max-width: 500px; margin: 0 auto 25px; display: flex; gap: 8px; }
        .search-bar input { flex: 1; background: rgba(15,15,40,0.8); border: 1px solid rgba(249,115,22,0.2); color: #e0e0e0; border-radius: 8px; padding: 8px 14px; font-size: 13px; }
        .search-bar input:focus { border-color: #f97316; outline: none; box-shadow: 0 0 0 2px rgba(249,115,22,0.15); }
        .search-bar button { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-weight: 600; cursor: pointer; }

        /* Player card */
        .player-card { background: rgba(25,25,55,0.85); border: 1px solid rgba(249,115,22,0.15); border-radius: 14px; margin-bottom: 18px; padding: 18px; box-shadow: 0 4px 20px rgba(0,0,0,0.25); transition: border-color 0.3s; }
        .player-card:hover { border-color: rgba(249,115,22,0.4); }
        .pc-top { display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap; }

        /* Avatar */
        .pc-avatar { text-align: center; min-width: 80px; }
        .pc-avatar .av-circle { width: 64px; height: 64px; border-radius: 50%; background: rgba(15,15,40,0.8); border: 2px solid rgba(249,115,22,0.3); display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 6px; }
        .pc-avatar .av-name { color: #febb12; font-weight: 800; font-size: 14px; }
        .pc-avatar .av-planet { font-size: 10px; color: #9ca3af; }

        /* Power section */
        .pc-power { min-width: 200px; }
        .pw-row { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; font-size: 12px; }
        .pw-label { color: #9ca3af; font-weight: 600; min-width: 55px; }
        .pw-val { background: rgba(15,15,40,0.7); border: 1px solid rgba(249,115,22,0.1); border-radius: 6px; padding: 2px 10px; color: #e0e0e0; font-weight: 700; flex: 1; font-size: 12px; }
        .pw-sub { color: #6b7280; font-size: 10px; margin-left: 4px; }

        /* Wealth */
        .pc-wealth { min-width: 100px; }
        .w-item { display: flex; align-items: center; gap: 5px; margin-bottom: 4px; font-size: 12px; font-weight: 700; }
        .w-gold { color: #febb12; }
        .w-ruby { color: #ef4444; }
        .w-vnd { color: #4ade80; }

        /* Items grid */
        .pc-items { flex: 1; min-width: 250px; }
        .items-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 700; }
        .items-grid { display: flex; flex-wrap: wrap; gap: 3px; }
        .item-slot { width: 36px; height: 36px; background: rgba(15,15,40,0.6); border: 1px solid rgba(249,115,22,0.1); border-radius: 5px; display: flex; align-items: center; justify-content: center; position: relative; cursor: default; font-size: 10px; color: #6b7280; }
        .item-slot.filled { border-color: rgba(249,115,22,0.3); background: rgba(30,25,50,0.8); }
        .item-slot .item-qty { position: absolute; bottom: 0; right: 1px; font-size: 8px; color: #febb12; font-weight: 800; background: rgba(0,0,0,0.6); padding: 0 3px; border-radius: 3px; line-height: 1.3; }
        .item-slot .item-name-tip { display: none; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.9); color: #febb12; padding: 4px 8px; border-radius: 5px; font-size: 10px; white-space: nowrap; z-index: 100; pointer-events: none; }
        .item-slot:hover .item-name-tip { display: block; }

        .section-divider { border-top: 1px solid rgba(249,115,22,0.08); margin: 12px 0; }

        /* Edit button */
        .btn-edit { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); border-radius: 8px; padding: 6px 16px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; margin-top: 8px; }
        .btn-edit:hover { background: rgba(59,130,246,0.3); color: #93bbfd; }

        /* Edit form */
        .edit-panel { display: none; background: rgba(15,15,40,0.7); border: 1px solid rgba(249,115,22,0.2); border-radius: 12px; padding: 20px; margin-top: 15px; }
        .edit-panel.show { display: block; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .edit-panel h6 { color: #febb12; font-weight: 700; margin-bottom: 14px; font-size: 14px; }
        .edit-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
        .edit-field label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; display: block; font-weight: 600; }
        .edit-field input, .edit-field select { width: 100%; background: rgba(15,15,40,0.9); border: 1px solid rgba(249,115,22,0.15); color: #e0e0e0; border-radius: 6px; padding: 6px 10px; font-size: 13px; font-weight: 600; }
        .edit-field input:focus, .edit-field select:focus { border-color: #f97316; outline: none; box-shadow: 0 0 0 2px rgba(249,115,22,0.15); }
        .btn-save { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 8px; padding: 10px 28px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 14px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(249,115,22,0.3); }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(249,115,22,0.4); }
        .btn-cancel { background: rgba(107,114,128,0.2); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2); border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 14px; margin-left: 8px; }

        /* Footer */
        .admin-footer { text-align: center; padding: 20px; margin-top: 30px; border-top: 1px solid rgba(249,115,22,0.1); }
        .lio-badge { background: linear-gradient(135deg, #f97316, #febb12); color: #000; font-weight: 800; padding: 2px 10px; border-radius: 4px; font-size: 10px; text-transform: uppercase; display: inline-block; }
    </style>
</head>
<body>
<div class="admin-header">
    <div class="container"><a href="/admin"><i class="fas fa-arrow-left"></i> Quay lại menu admin</a></div>
</div>

<div class="container" style="max-width: 1100px;">
    <div class="page-title"><i class="fas fa-gamepad"></i> QUẢN LÝ NHÂN VẬT</div>
    <div class="page-sub">Xem chi tiết thông tin, chỉ số và hành trang nhân vật</div>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="total-badge">👥 Tổng Player: <?php echo $total_players; ?></div>
    </div>
    <div class="stats-bar">
        <div class="planet-badge">🌍 Trái Đất <strong><?php echo $g_counts[0]; ?></strong></div>
        <div class="planet-badge">🟢 Namek <strong><?php echo $g_counts[1]; ?></strong></div>
        <div class="planet-badge">👽 Xayda <strong><?php echo $g_counts[2]; ?></strong></div>
    </div>

    <!-- Search -->
    <form method="GET" class="search-bar">
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo tên nhân vật hoặc tài khoản...">
        <button type="submit"><i class="fas fa-search"></i> Tìm</button>
        <?php if ($search): ?><a href="players.php" style="background:rgba(107,114,128,0.3);color:#fff;border:none;border-radius:8px;padding:8px 14px;text-decoration:none;"><i class="fas fa-times"></i></a><?php endif; ?>
    </form>

    <?php echo $_alert; ?>

    <!-- Player Cards -->
    <?php if ($players && $players->num_rows > 0): while ($p = $players->fetch_assoc()):
        $dp = json_decode($p['data_point'], true);
        $di = json_decode($p['data_inventory'], true); // [gold, gem, ...]
        $bag_items = parseItems($p['items_bag']);
        $body_items = parseItems($p['items_body']);
        $box_items = parseItems($p['items_box']);
        $planets = ['Trái Đất', 'Namek', 'Xayda'];
        $planet_icons = ['🌍', '🟢', '👽'];
        $gender = $p['gender'];
    ?>
    <div class="player-card" id="card-<?php echo $p['id']; ?>">
        <div class="pc-top">
            <!-- Avatar -->
            <div class="pc-avatar">
                <div class="av-circle"><?php echo $planet_icons[$gender] ?? '❓'; ?></div>
                <div class="av-name"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="av-planet"><?php echo $planets[$gender] ?? '?'; ?> | @<?php echo htmlspecialchars($p['username']); ?></div>
                <a class="btn-edit" onclick="toggleEdit(<?php echo $p['id']; ?>)"><i class="fas fa-pen"></i> Sửa</a>
            </div>

            <!-- Power -->
            <div class="pc-power">
                <div class="pw-row">
                    <span class="pw-label">⚔️ SM:</span>
                    <span class="pw-val"><?php echo number_format($dp[1] ?? 0); ?> <span class="pw-sub">≈<?php echo formatBigNum($dp[1] ?? 0); ?></span></span>
                </div>
                <div class="pw-row">
                    <span class="pw-label">💥 SĐG:</span>
                    <span class="pw-val"><?php echo number_format($dp[7] ?? 0); ?> <span class="pw-sub">≈<?php echo formatBigNum($dp[7] ?? 0); ?></span></span>
                </div>
                <div class="pw-row">
                    <span class="pw-label">🛡️ TN:</span>
                    <span class="pw-val"><?php echo number_format($dp[2] ?? 0); ?> <span class="pw-sub">≈<?php echo formatBigNum($dp[2] ?? 0); ?></span></span>
                </div>
                <div class="pw-row">
                    <span class="pw-label">❤️ HP:</span>
                    <span class="pw-val" style="color:#4ade80;"><?php echo number_format($dp[5] ?? 0); ?></span>
                </div>
                <div class="pw-row">
                    <span class="pw-label">💙 MP:</span>
                    <span class="pw-val" style="color:#60a5fa;"><?php echo number_format($dp[6] ?? 0); ?></span>
                </div>
                <div class="pw-row">
                    <span class="pw-label">🛡️ Giáp:</span>
                    <span class="pw-val"><?php echo number_format($dp[8] ?? 0); ?></span>
                </div>
                <div class="pw-row">
                    <span class="pw-label">🎯 CM:</span>
                    <span class="pw-val"><?php echo number_format($dp[9] ?? 0); ?></span>
                </div>
            </div>

            <!-- Wealth -->
            <div class="pc-wealth">
                <div class="w-item w-gold">💰 <?php echo formatBigNum($di[0] ?? 0); ?></div>
                <div class="w-item" style="color:#60a5fa;">💎 <?php echo formatBigNum($di[1] ?? 0); ?></div>
                <div class="w-item w-ruby">❤️ <?php echo number_format($di[2] ?? 0); ?></div>
                <div class="w-item w-vnd">🏷️ VNĐ: <?php echo number_format($p['vnd']); ?></div>
                <div class="w-item" style="color:#c084fc;">👑 VIP: <?php echo $p['vip']; ?></div>
            </div>
        </div>

        <!-- Edit Panel -->
        <div class="edit-panel" id="edit-<?php echo $p['id']; ?>">
            <h6><i class="fas fa-edit"></i> Chỉnh sửa nhân vật: <?php echo htmlspecialchars($p['name']); ?></h6>
            <form method="POST">
                <input type="hidden" name="edit_player" value="1">
                <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                <input type="hidden" name="account_id" value="<?php echo $p['account_id']; ?>">
                <div class="edit-grid">
                    <div class="edit-field"><label>Tên nhân vật</label><input type="text" name="p_name" value="<?php echo htmlspecialchars($p['name']); ?>" required></div>
                    <div class="edit-field"><label>Hành tinh</label><select name="p_gender"><option value="0" <?php echo $gender==0?'selected':''; ?>>Trái Đất</option><option value="1" <?php echo $gender==1?'selected':''; ?>>Namek</option><option value="2" <?php echo $gender==2?'selected':''; ?>>Xayda</option></select></div>
                    <div class="edit-field"><label>⚔️ Sức mạnh</label><input type="number" name="p_sm" value="<?php echo $dp[1] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>🛡️ Tiềm năng</label><input type="number" name="p_tn" value="<?php echo $dp[2] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>❤️ HP</label><input type="number" name="p_hp" value="<?php echo $dp[5] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>💙 MP</label><input type="number" name="p_mp" value="<?php echo $dp[6] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>💥 Sức đánh gốc</label><input type="number" name="p_sdg" value="<?php echo $dp[7] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>🛡️ Giáp gốc</label><input type="number" name="p_giap" value="<?php echo $dp[8] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>🎯 Chí mạng</label><input type="number" name="p_cm" value="<?php echo $dp[9] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>💰 Gold</label><input type="number" name="p_gold" value="<?php echo $di[0] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>💎 Gem</label><input type="number" name="p_gem" value="<?php echo $di[1] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>❤️ Ruby</label><input type="number" name="p_ruby" value="<?php echo $di[2] ?? 0; ?>" step="1"></div>
                    <div class="edit-field"><label>🏷️ VNĐ</label><input type="number" name="p_vnd" value="<?php echo $p['vnd']; ?>" step="1"></div>
                    <div class="edit-field"><label>👑 VIP</label><input type="number" name="p_vip" value="<?php echo $p['vip']; ?>" min="0"></div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Lưu thay đổi</button>
                <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $p['id']; ?>)">Hủy</button>
            </form>
        </div>

        <!-- Items: Body -->
        <div class="section-divider"></div>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div>
                <div class="items-label">🎽 Đang mặc (<?php echo count($body_items); ?>)</div>
                <div class="items-grid">
                    <?php foreach ($body_items as $bi):
                        $iname = isset($item_names[$bi['id']]) ? $item_names[$bi['id']]['name'] : 'ID:'.$bi['id'];
                    ?>
                    <div class="item-slot filled" title="<?php echo htmlspecialchars($iname); ?>">
                        <span style="font-size:9px;color:#febb12;"><?php echo $bi['id']; ?></span>
                        <?php if ($bi['qty'] > 1): ?><span class="item-qty"><?php echo $bi['qty']; ?></span><?php endif; ?>
                        <span class="item-name-tip"><?php echo htmlspecialchars($iname); ?> (x<?php echo $bi['qty']; ?>)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php for ($e = count($body_items); $e < 8; $e++): ?><div class="item-slot"></div><?php endfor; ?>
                </div>
            </div>

            <div style="flex:1;">
                <div class="items-label">🎒 Hành trang (<?php echo count($bag_items); ?>)</div>
                <div class="items-grid">
                    <?php foreach ($bag_items as $bi):
                        $iname = isset($item_names[$bi['id']]) ? $item_names[$bi['id']]['name'] : 'ID:'.$bi['id'];
                    ?>
                    <div class="item-slot filled" title="<?php echo htmlspecialchars($iname); ?>">
                        <span style="font-size:9px;color:#febb12;"><?php echo $bi['id']; ?></span>
                        <?php if ($bi['qty'] > 1): ?><span class="item-qty"><?php echo $bi['qty']; ?></span><?php endif; ?>
                        <span class="item-name-tip"><?php echo htmlspecialchars($iname); ?> (x<?php echo number_format($bi['qty']); ?>)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php for ($e = count($bag_items); $e < 20; $e++): ?><div class="item-slot"></div><?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Rương đồ -->
        <?php if (!empty($box_items)): ?>
        <div class="section-divider"></div>
        <div>
            <div class="items-label">📦 Rương đồ (<?php echo count($box_items); ?>)</div>
            <div class="items-grid">
                <?php foreach ($box_items as $bi):
                    $iname = isset($item_names[$bi['id']]) ? $item_names[$bi['id']]['name'] : 'ID:'.$bi['id'];
                ?>
                <div class="item-slot filled" title="<?php echo htmlspecialchars($iname); ?>">
                    <span style="font-size:9px;color:#febb12;"><?php echo $bi['id']; ?></span>
                    <?php if ($bi['qty'] > 1): ?><span class="item-qty"><?php echo $bi['qty']; ?></span><?php endif; ?>
                    <span class="item-name-tip"><?php echo htmlspecialchars($iname); ?> (x<?php echo number_format($bi['qty']); ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endwhile; else: ?>
    <div class="player-card" style="text-align:center;color:#6b7280;padding:40px;">Không tìm thấy nhân vật nào.</div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="admin-footer">
        <small style="color:#6b7280;">IP: <?php echo $_IP; ?></small><br>
        <span class="lio-badge">Code by Lio</span>
    </div>
</div>
<script>
function toggleEdit(id) {
    var panel = document.getElementById('edit-' + id);
    panel.classList.toggle('show');
    if (panel.classList.contains('show')) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
</script>
</body>
</html>
