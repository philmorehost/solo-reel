-- Brute-force login protection thresholds (App\Core\BruteForce reads these;
-- the login_attempts table already exists from 001_initial_schema_mysql.sql
-- but nothing wrote to it until this feature was wired up).
INSERT IGNORE INTO `site_config` (`setting_key`, `setting_value`) VALUES ('max_login_attempts', '5');
INSERT IGNORE INTO `site_config` (`setting_key`, `setting_value`) VALUES ('lockout_duration_minutes', '15');
