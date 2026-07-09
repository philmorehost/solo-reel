-- Content hub redesign support: trailers for the "For You" feed, a
-- "coming soon" series status for the NEW tab, and a "TV Series" genre so
-- the MOVIES tab can define itself as "everything not tagged TV Series"
-- (no separate Movies genre needed).

ALTER TABLE `episodes` ADD COLUMN `trailer_url` varchar(500) NULL AFTER `video_url`;

ALTER TABLE `series` MODIFY `status` enum('ongoing','completed','coming_soon') DEFAULT 'ongoing';

INSERT IGNORE INTO `genres` (`name`, `slug`) VALUES ('TV Series', 'tv-series');
