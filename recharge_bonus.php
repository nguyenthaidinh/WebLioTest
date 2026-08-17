<?php
function recharge_event_multiplier() {
    return 2;
}

function recharge_bonus_tiers() {
    return [
        1000000 => 70,
        500000 => 50,
        300000 => 30,
        200000 => 20,
        100000 => 10,
    ];
}

function recharge_event_amount($paid_amount) {
    return max(0, (int)$paid_amount) * recharge_event_multiplier();
}

function recharge_bonus_rate_for_value($promotion_value) {
    $promotion_value = max(0, (int)$promotion_value);
    foreach (recharge_bonus_tiers() as $threshold => $rate) {
        if ($promotion_value >= $threshold) {
            return $rate;
        }
    }
    return 0;
}

function recharge_bonus_rate($paid_amount) {
    return recharge_bonus_rate_for_value(recharge_event_amount($paid_amount));
}

function recharge_bonus_amount($paid_amount) {
    $event_amount = recharge_event_amount($paid_amount);
    return intdiv($event_amount * recharge_bonus_rate_for_value($event_amount), 100);
}

function recharge_credit_amount($paid_amount) {
    return recharge_event_amount($paid_amount) + recharge_bonus_amount($paid_amount);
}

function recharge_default_bonus_spins($paid_amount) {
    return intdiv(max(0, (int)$paid_amount), 10000);
}

function recharge_paid_amount_for_tier($promotion_threshold) {
    $multiplier = recharge_event_multiplier();
    return intdiv(max(0, (int)$promotion_threshold) + $multiplier - 1, $multiplier);
}

function recharge_ensure_credit_history_table($conn) {
    if (!($conn instanceof mysqli)) {
        return false;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS `recharge_credit_history` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `bank_transfer_id` INT NOT NULL,
            `paid_amount` BIGINT UNSIGNED NOT NULL,
            `event_multiplier` SMALLINT UNSIGNED NOT NULL,
            `event_amount` BIGINT UNSIGNED NOT NULL,
            `bonus_rate` SMALLINT UNSIGNED NOT NULL,
            `bonus_amount` BIGINT UNSIGNED NOT NULL,
            `credited_amount` BIGINT UNSIGNED NOT NULL,
            `total_recharge_increment` BIGINT UNSIGNED NOT NULL,
            `bonus_spins` INT UNSIGNED NOT NULL,
            `credited_by` VARCHAR(50) DEFAULT NULL,
            `credited_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_recharge_credit_transfer` (`bank_transfer_id`),
            KEY `idx_recharge_credit_time` (`credited_at`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return $conn->query($sql) === true;
}
