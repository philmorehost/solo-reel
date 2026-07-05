-- Add wallet_balance column to users table
ALTER TABLE `users` ADD COLUMN `wallet_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `coin_balance`;
