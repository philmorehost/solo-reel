-- 1. Create Genres
INSERT IGNORE INTO `genres` (`name`, `slug`) VALUES
('Sci-Fi Action', 'sci-fi-action'),
('Romantic Comedy', 'romantic-comedy');

-- 2. Create Shelves
INSERT INTO `shelves` (`name`, `slug`, `emoji`, `sort_order`) VALUES
('Trending Now', 'trending-now', '🔥', 1),
('Binge-Worthy Romance', 'binge-worthy-romance', '❤️', 2);

-- 3. Create Series: King of War Tech
INSERT INTO `series` (`title`, `slug`, `synopsis`, `genre`, `status`, `cover_image`, `hero_image`, `is_featured`, `shelf_id`)
VALUES (
    'King of War Tech',
    'king-of-war-tech',
    'A legendary soldier returns to the city disguised as a lowly tech support worker to uncover the conspiracy behind his fallen comrades.',
    'Sci-Fi Action',
    'ongoing',
    '/assets/uploads/cover_king.jpg',
    '/assets/uploads/hero_king.jpg',
    1,
    (SELECT id FROM `shelves` WHERE `slug` = 'trending-now')
);

SET @series1_id = LAST_INSERT_ID();

-- Episodes for King of War Tech
INSERT INTO `episodes` (`series_id`, `episode_number`, `title`, `slug`, `video_url`, `thumbnail_url`, `is_free`, `coin_cost`)
VALUES
(@series1_id, 1, 'The Return', 'king-of-war-tech-ep1', '/assets/video_placeholder.mp4', '/assets/uploads/thumb_ep1.jpg', 1, 0.00),
(@series1_id, 2, 'Hidden Power', 'king-of-war-tech-ep2', '/assets/video_placeholder.mp4', '/assets/uploads/thumb_ep2.jpg', 0, 10.00);

-- 4. Create Series: Accidentally Sexted My Enemy
INSERT INTO `series` (`title`, `slug`, `synopsis`, `genre`, `status`, `cover_image`, `hero_image`, `is_featured`, `shelf_id`)
VALUES (
    'Accidentally Sexted My Enemy',
    'accidentally-sexted-my-enemy',
    'After a drunken night out, the CEO’s fiery rival sends a spicy text to the wrong number. Now they are trapped in a secret office romance.',
    'Romantic Comedy',
    'ongoing',
    '/assets/uploads/cover_sexted.jpg',
    '/assets/uploads/hero_sexted.jpg',
    1,
    (SELECT id FROM `shelves` WHERE `slug` = 'binge-worthy-romance')
);

SET @series2_id = LAST_INSERT_ID();

-- Episodes for Accidentally Sexted My Enemy
INSERT INTO `episodes` (`series_id`, `episode_number`, `title`, `slug`, `video_url`, `thumbnail_url`, `is_free`, `coin_cost`)
VALUES
(@series2_id, 1, 'The Mistake', 'accidentally-sexted-ep1', '/assets/video_placeholder.mp4', '/assets/uploads/thumb_ep1.jpg', 1, 0.00),
(@series2_id, 2, 'Office Politics', 'accidentally-sexted-ep2', '/assets/video_placeholder.mp4', '/assets/uploads/thumb_ep2.jpg', 0, 10.00);
