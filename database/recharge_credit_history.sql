-- Lưu ảnh chụp công thức và số tiền thực tế tại thời điểm admin duyệt nạp.
-- Nhờ đó lịch sử cũ không bị thay đổi khi cấu hình khuyến mãi được sửa về sau.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
