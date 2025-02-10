ALTER TABLE `#__xbmusic_artists` ADD `imageinfo` MEDIUMTEXT NULL AFTER `imgurl`;

CREATE TABLE IF NOT EXISTS `#__xbmusic_artistgroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `artist_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role` varchar(255) NOT NULL DEFAULT '',
  `since` varchar(4) NOT NULL DEFAULT '',
  `until` varchar(4) NOT NULL DEFAULT '',
  `note` varchar(255) NOT NULL DEFAULT '',
  `listorder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_artist_id` (`artist_id`),
  KEY `idx_group_id` (`group_id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

