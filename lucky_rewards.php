<?php
const LUCKY_REWARD_DEFAULTS = [
    ['reward_key' => 'miss', 'label' => 'Chúc may mắn', 'amount' => 0, 'weight' => 3000, 'color' => '#64748b', 'sort_order' => 1],
    ['reward_key' => 'gold_10', 'label' => '10 TV', 'amount' => 10, 'weight' => 3000, 'color' => '#22c55e', 'sort_order' => 2],
    ['reward_key' => 'gold_20', 'label' => '20 TV', 'amount' => 20, 'weight' => 2200, 'color' => '#14b8a6', 'sort_order' => 3],
    ['reward_key' => 'gold_50', 'label' => '50 TV', 'amount' => 50, 'weight' => 800, 'color' => '#3b82f6', 'sort_order' => 4],
    ['reward_key' => 'gold_100', 'label' => '100 TV', 'amount' => 100, 'weight' => 500, 'color' => '#a855f7', 'sort_order' => 5],
    ['reward_key' => 'gold_200', 'label' => '200 TV', 'amount' => 200, 'weight' => 250, 'color' => '#f97316', 'sort_order' => 6],
    ['reward_key' => 'gold_300', 'label' => '300 TV', 'amount' => 300, 'weight' => 150, 'color' => '#eab308', 'sort_order' => 7],
    ['reward_key' => 'gold_500', 'label' => '500 TV', 'amount' => 500, 'weight' => 75, 'color' => '#ef4444', 'sort_order' => 8],
    ['reward_key' => 'gold_1000', 'label' => '1000 TV', 'amount' => 1000, 'weight' => 25, 'color' => '#f43f5e', 'sort_order' => 9],
];

function lucky_rewards_ensure_table($conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS lucky_spin_rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reward_key VARCHAR(50) NOT NULL UNIQUE,
            label VARCHAR(100) NOT NULL,
            amount INT NOT NULL DEFAULT 0,
            weight INT NOT NULL DEFAULT 0,
            color VARCHAR(20) NOT NULL DEFAULT '#64748b',
            sort_order INT NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($sql)) {
        throw new Exception('Không thể khởi tạo bảng tỉ lệ vòng quay: ' . $conn->error);
    }
}

function lucky_rewards_seed_defaults($conn) {
    lucky_rewards_ensure_table($conn);

    $stmt = $conn->prepare("
        INSERT IGNORE INTO lucky_spin_rewards (reward_key, label, amount, weight, color, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('Lỗi prepare seed tỉ lệ vòng quay: ' . $conn->error);
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
            throw new Exception('Không thể seed tỉ lệ vòng quay: ' . $error);
        }
    }

    $stmt->close();
}

function lucky_rewards_load($conn) {
    lucky_rewards_seed_defaults($conn);

    $result = $conn->query("
        SELECT reward_key, label, amount, weight, color, sort_order
        FROM lucky_spin_rewards
        ORDER BY sort_order ASC, id ASC
    ");

    if (!$result) {
        throw new Exception('Không thể đọc tỉ lệ vòng quay: ' . $conn->error);
    }

    $rewards = [];
    while ($row = $result->fetch_assoc()) {
        $rewards[] = [
            'reward_key' => $row['reward_key'],
            'label' => $row['label'],
            'amount' => (int)$row['amount'],
            'weight' => max(0, (int)$row['weight']),
            'color' => $row['color'],
            'sort_order' => (int)$row['sort_order'],
        ];
    }
    $result->free();

    return $rewards;
}

function lucky_rewards_total_weight($rewards) {
    $total = 0;
    foreach ($rewards as $reward) {
        $total += max(0, (int)$reward['weight']);
    }
    return $total;
}

function lucky_rewards_weight_to_percent($weight, $total_weight = 10000) {
    if ($total_weight <= 0) {
        return 0;
    }
    return ((int)$weight / $total_weight) * 100;
}

function lucky_rewards_percent_to_weight($percent) {
    return (int)round(((float)$percent) * 100);
}
?>
