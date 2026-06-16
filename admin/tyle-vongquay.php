<?php
include_once 'set.php';
include_once 'connect.php';
require_once __DIR__ . '/../lucky_rewards.php';

if ($_login == null) {
    header("Location: /app/login.php");
    exit();
}

if (empty($_SESSION['admin_reward_rate_csrf'])) {
    $_SESSION['admin_reward_rate_csrf'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['admin_reward_rate_csrf'];
$_alert = '';

function reward_rate_alert($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-error';
    return '<div class="admin-alert ' . $class . '">' . htmlspecialchars($message) . '</div>';
}

function reward_rate_reset_defaults($conn) {
    $stmt = $conn->prepare("
        INSERT INTO lucky_spin_rewards (reward_key, label, amount, weight, color, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            label = VALUES(label),
            amount = VALUES(amount),
            weight = VALUES(weight),
            color = VALUES(color),
            sort_order = VALUES(sort_order)
    ");
    if (!$stmt) {
        throw new Exception('Lỗi prepare khôi phục mặc định: ' . $conn->error);
    }

    foreach (LUCKY_REWARD_DEFAULTS as $reward) {
        $stmt->bind_param(
            "ssiisi",
            $reward['reward_key'],
            $reward['label'],
            $reward['amount'],
            $reward['weight'],
            $reward['color'],
            $reward['sort_order']
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('Không thể khôi phục mặc định: ' . $error);
        }
    }

    $stmt->close();
}

try {
    lucky_rewards_seed_defaults($conn);
} catch (Exception $e) {
    $_alert = reward_rate_alert($e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_alert === '') {
    try {
        $posted_token = $_POST['csrf_token'] ?? '';
        if (!is_string($posted_token) || !hash_equals($csrf_token, $posted_token)) {
            throw new Exception('Phiên bảo mật không hợp lệ.');
        }

        $action = $_POST['action'] ?? 'save';
        if ($action === 'reset') {
            reward_rate_reset_defaults($conn);
            $_alert = reward_rate_alert('Đã khôi phục tỉ lệ vòng quay mặc định.');
        } else {
            $rates = $_POST['rates'] ?? [];
            if (!is_array($rates)) {
                throw new Exception('Dữ liệu tỉ lệ không hợp lệ.');
            }

            $rewards = lucky_rewards_load($conn);
            $new_weights = [];
            $total_weight = 0;

            foreach ($rewards as $reward) {
                $key = $reward['reward_key'];
                $raw_percent = $rates[$key] ?? null;
                if (!is_scalar($raw_percent)) {
                    throw new Exception('Thiếu tỉ lệ cho phần thưởng ' . $reward['label'] . '.');
                }

                $percent_text = str_replace(',', '.', trim((string)$raw_percent));
                if ($percent_text === '' || !is_numeric($percent_text)) {
                    throw new Exception('Tỉ lệ của ' . $reward['label'] . ' không hợp lệ.');
                }

                $percent = (float)$percent_text;
                if ($percent < 0 || $percent > 100) {
                    throw new Exception('Tỉ lệ của ' . $reward['label'] . ' phải từ 0 đến 100.');
                }

                $weight = lucky_rewards_percent_to_weight($percent);
                $new_weights[$key] = $weight;
                $total_weight += $weight;
            }

            if ($total_weight !== 10000) {
                throw new Exception('Tổng tỉ lệ phải bằng 100%. Hiện tại là ' . rtrim(rtrim(number_format($total_weight / 100, 2, '.', ''), '0'), '.') . '%.');
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE lucky_spin_rewards SET weight = ? WHERE reward_key = ?");
                if (!$stmt) {
                    throw new Exception('Lỗi prepare cập nhật tỉ lệ: ' . $conn->error);
                }

                foreach ($new_weights as $key => $weight) {
                    $stmt->bind_param("is", $weight, $key);
                    if (!$stmt->execute()) {
                        $error = $stmt->error;
                        $stmt->close();
                        throw new Exception('Không thể cập nhật tỉ lệ: ' . $error);
                    }
                }

                $stmt->close();
                $conn->commit();
                $_alert = reward_rate_alert('Đã cập nhật tỉ lệ vòng quay.');
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    } catch (Exception $e) {
        $_alert = reward_rate_alert($e->getMessage(), 'error');
    }
}

try {
    $rewards = lucky_rewards_load($conn);
} catch (Exception $e) {
    $rewards = LUCKY_REWARD_DEFAULTS;
    if ($_alert === '') {
        $_alert = reward_rate_alert($e->getMessage(), 'error');
    }
}

$total_weight = lucky_rewards_total_weight($rewards);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Chỉnh Tỉ Lệ Vòng Quay - Admin</title>
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
        .admin-alert { border-radius:10px; padding:12px; margin-bottom:16px; font-weight:700; text-align:center; }
        .alert-success { background: rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3); }
        .alert-error { background: rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
        .rate-table { width:100%; border-collapse: separate; border-spacing: 0 6px; }
        .rate-table th { background: rgba(249,115,22,0.1); color:#febb12; font-size:11px; font-weight:800; text-transform:uppercase; padding:10px 8px; }
        .rate-table td { background: rgba(20,20,50,0.65); color:#e5e7eb; padding:10px 8px; font-size:13px; vertical-align:middle; }
        .reward-color { width:22px; height:22px; border-radius:50%; border:2px solid rgba(255,255,255,0.75); display:inline-block; vertical-align:middle; margin-right:8px; }
        .rate-input { width:110px; background: rgba(15,15,40,0.8); border:1px solid rgba(249,115,22,0.35); color:#fff; border-radius:8px; padding:8px 10px; font-weight:800; }
        .btn-main-action { background: linear-gradient(135deg, #f97316, #ea580c); color:#fff; border:0; border-radius:8px; padding:10px 16px; font-weight:900; cursor:pointer; }
        .btn-reset { background: rgba(148,163,184,0.2); color:#e5e7eb; border:1px solid rgba(148,163,184,0.35); border-radius:8px; padding:10px 16px; font-weight:800; cursor:pointer; }
        .summary-row { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-bottom:14px; }
        .summary-pill { border:1px solid rgba(249,115,22,0.25); border-radius:999px; padding:8px 12px; background:rgba(15,15,40,0.55); color:#febb12; font-weight:800; }
        .hint { color:#9ca3af; font-size:12px; text-align:center; margin-top:10px; }
        @media (max-width: 760px) {
            .gc-card { overflow-x:auto; }
            .rate-table { min-width:700px; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <a href="/admin"><i class="fas fa-arrow-left"></i> Về menu admin</a>
            <a href="/admin/luotquay.php"><i class="fas fa-sync-alt"></i> Cấp lượt quay</a>
        </div>
    </div>

    <div class="container" style="max-width: 920px;">
        <h1 class="page-title">Chỉnh Tỉ Lệ Vòng Quay</h1>
        <p class="page-subtitle">Tỉ lệ này chỉ admin thấy. Người chơi chỉ thấy các phần thưởng trên vòng quay.</p>

        <?php echo $_alert; ?>

        <div class="gc-card">
            <div class="summary-row">
                <div class="summary-pill">Tổng hiện tại: <?php echo rtrim(rtrim(number_format(lucky_rewards_weight_to_percent($total_weight), 2, '.', ''), '0'), '.'); ?>%</div>
                <div class="summary-pill">Yêu cầu lưu: 100%</div>
            </div>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="save">

                <table class="rate-table">
                    <thead>
                        <tr>
                            <th>Phần thưởng</th>
                            <th>Số TV</th>
                            <th>Tỉ lệ hiện tại</th>
                            <th>Tỉ lệ mới (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rewards as $reward): ?>
                            <?php $percent = lucky_rewards_weight_to_percent((int)$reward['weight']); ?>
                            <tr>
                                <td>
                                    <span class="reward-color" style="background: <?php echo htmlspecialchars($reward['color']); ?>"></span>
                                    <strong><?php echo htmlspecialchars($reward['label']); ?></strong>
                                </td>
                                <td><?php echo (int)$reward['amount'] > 0 ? number_format((int)$reward['amount'], 0, ',', '.') . ' TV' : '-'; ?></td>
                                <td style="color:#febb12;font-weight:800;"><?php echo rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.'); ?>%</td>
                                <td>
                                    <input class="rate-input" type="number" name="rates[<?php echo htmlspecialchars($reward['reward_key']); ?>]" min="0" max="100" step="0.01" value="<?php echo rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.'); ?>" required>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:16px;">
                    <button class="btn-main-action" type="submit">Lưu tỉ lệ</button>
                    <button class="btn-reset" type="submit" name="action" value="reset" onclick="return confirm('Khôi phục tỉ lệ mặc định?');">Khôi phục mặc định</button>
                </div>
                <div class="hint">Nhập phần trăm cho từng phần thưởng. Tổng tất cả dòng phải bằng đúng 100%.</div>
            </form>
        </div>
    </div>
</body>
</html>
