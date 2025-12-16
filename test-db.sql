-- Adminer 5.4.1 MariaDB 10.8.3-MariaDB-1:10.8.3+maria~jammy dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `api_token`;
CREATE TABLE `api_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7BA2F5EB5F37A13B` (`token`),
  KEY `IDX_7BA2F5EBA76ED395` (`user_id`),
  CONSTRAINT `FK_7BA2F5EBA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `api_token` (`id`, `token`, `expires_at`, `user_id`) VALUES
(1,	'root-token-for-testing-purposes-1234567890abcdef12345678',	'2026-01-15 16:55:29',	1),
(2,	'user1-token-for-testing-purposes-1234567890abcdef1234567',	'2026-01-15 16:55:29',	2),
(3,	'user2-token-for-testing-purposes-1234567890abcdef1234567',	'2026-01-15 16:55:30',	3);

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20251213192829',	'2025-12-16 14:21:33',	34),
('DoctrineMigrations\\Version20251216142906',	'2025-12-16 14:29:35',	152),
('DoctrineMigrations\\Version20251216155729',	'2025-12-16 15:57:43',	153),
('DoctrineMigrations\\Version20251216164839',	'2025-12-16 16:48:49',	208),
('DoctrineMigrations\\Version20251216171410',	'2025-12-16 17:14:20',	93);

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(8) NOT NULL,
  `email` varchar(8) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`),
  UNIQUE KEY `UNIQ_IDENTIFIER_PASSWORD` (`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `user` (`id`, `phone`, `email`, `roles`, `password`) VALUES
(1,	'+0000000',	'r@rt.com',	'[\"ROLE_ROOT\"]',	'$2y$13$FJL0R6piSUYYMKEywXmlgeKsToWNk9tiCgehCJAMIQ/m2qFZj4uES'),
(2,	'+1111111',	'u1@t.com',	'[]',	'$2y$13$FHNrhXG4WY1Isea8peVfGOldXYNIu45jGxfPIV0bOzRQus9.y5KjK'),
(3,	'+9999999',	'u2@t.com',	'[]',	'$2y$13$Hq2Zo806IIvgdRKyKNa22.kJa3dNdXeTv/CZBJw3VBB2yXPqBnR7W'),
(4,	'12345678',	't@5t.com',	'[]',	'$2y$13$IWTokgKYBX8AQc9sJy1TdOtS5g2DUbDY0lZYsf8qQ1hf4OqMtB3qy');

DROP TABLE IF EXISTS `user_profile`;
CREATE TABLE `user_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2025-12-16 17:44:17 UTC
