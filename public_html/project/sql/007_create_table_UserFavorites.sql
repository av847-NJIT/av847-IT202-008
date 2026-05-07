CREATE TABLE `IT202-S26-UserFavorites` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `fighter_id` int NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `Users`(`id`),
  FOREIGN KEY (`fighter_id`) REFERENCES `IT202-S26-FighterStats`(`id`),
  UNIQUE KEY `unique_favorite` (`user_id`, `fighter_id`)
)