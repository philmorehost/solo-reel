-- VIP subscription tier: an alternative billing path alongside the existing
-- pay-per-coin unlock system (not a replacement) — users choose either.
--
-- Registered users only, deliberately breaking from this app's usual
-- user_X/guest_X dual-table split. That split exists to keep FREE actions
-- (likes, saves, history) dedup-safe for guests; a paid recurring grant is a
-- different category — guest_id is a client-reset cookie value with no auth,
-- so gating a real payment on it is unenforceable (clear storage, resubscribe
-- free). No auto-renew engine either: the existing Payhub integration
-- (Api\TransactionController::verifyPayment()) is one-shot reference
-- verification with no recurring-charge webhook, so renewal is just the user
-- repurchasing before/after expiry — each cycle inserts a fresh row so
-- subscription history is preserved rather than overwritten.

CREATE TABLE IF NOT EXISTS `vip_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'NGN',
  `duration_days` int(11) NOT NULL DEFAULT 30,
  `perk_free_unlocks` tinyint(1) NOT NULL DEFAULT 1,
  `perk_ad_free` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vip_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`, `expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `vip_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- payment_transactions.type already exists as enum('coin_purchase','ad_placement')
-- from 016_ad_marketplace.sql — just widen it and add a nullable plan_id FK so
-- the existing Payhub purchase/verify flow can carry a VIP purchase through
-- the exact same pending-row-then-verify pipeline coin purchases already use.
ALTER TABLE `payment_transactions`
  MODIFY `type` enum('coin_purchase','ad_placement','vip_subscription') DEFAULT 'coin_purchase',
  ADD COLUMN `plan_id` int(11) DEFAULT NULL AFTER `type`,
  ADD CONSTRAINT `fk_payment_transactions_plan` FOREIGN KEY (`plan_id`) REFERENCES `vip_plans`(`id`) ON DELETE SET NULL;
