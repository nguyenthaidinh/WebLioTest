-- Nhật ký từng lần người chơi tiêu Thỏi Vàng.
-- Website chỉ cho phép xem dữ liệu trong 72 giờ gần nhất; bảng vẫn giữ dữ liệu
-- để quản trị viên có thể chủ động chọn chính sách lưu/xóa ở phía máy chủ game.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hợp đồng ghi dữ liệu dành cho máy chủ game:
-- Chỉ INSERT sau khi thao tác trừ Thỏi Vàng và trao phần thưởng/dịch vụ đều
-- thành công. balance_before - balance_after phải bằng amount.
--
-- INSERT INTO gold_bar_spend_history
--     (player_id, account_id, player_name, account_username, amount,
--      balance_before, balance_after, action_code, reason, details, reference_id)
-- VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
