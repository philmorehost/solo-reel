ALTER TABLE `coin_packages` ADD COLUMN `color_code` varchar(50) DEFAULT '#000000' AFTER `currency`;

-- Insert default packages
TRUNCATE TABLE `coin_packages`;
INSERT INTO `coin_packages` (`name`, `coins`, `price`, `currency`, `color_code`, `sort_order`, `is_active`) VALUES
('Bronze', 500.00, 500.00, 'NGN', '#cd7f32', 1, 1),
('Silver', 1000.00, 900.00, 'NGN', '#c0c0c0', 2, 1),
('Gold', 5000.00, 4000.00, 'NGN', '#ffd700', 3, 1),
('Platinum', 10000.00, 7000.00, 'NGN', '#e5e4e2', 4, 1);

INSERT INTO `site_config` (`setting_key`, `setting_value`) VALUES ('bank_transfer_instruction', 'Please transfer funds to the dedicated virtual account below to instantly fund your wallet.');
