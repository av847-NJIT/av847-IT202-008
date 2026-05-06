CREATE TABLE `IT202-F26-FighterStats` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `striking_accuracy` decimal(5,2) NOT NULL,
  `takedown_accuracy` decimal(5,2) NOT NULL,
  `significant_strikes_landed` int NOT NULL,
  `significant_strikes_defense` decimal(5,2) NOT NULL,
  `takedown_defense` decimal(5,2) NOT NULL,
  `api_id` varchar(100) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_api` tinyint(1) DEFAULT '0'
)

RENAME TABLE `IT202-F26-FighterStats` TO `IT202-S26-FighterStats`;

TRUNCATE TABLE `IT202-S26-FighterStats`;