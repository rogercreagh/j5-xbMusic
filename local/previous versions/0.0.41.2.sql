ALTER TABLE `#__xbmusic_playlists` ADD `az_id` int(10) unsigned NOT NULL Default '0' AFTER `description`;
ALTER TABLE `#__xbmusic_playlists` ADD `az_info` MEDIUMTEXT NULL AFTER `az_id`;
ALTER TABLE `#__xbmusic_tracks` CHANGE `azuracast_id` `az_id` INT(10) NOT NULL DEFAULT '0';
ALTER TABLE `#__xbmusic_tracks` ADD `az_info` MEDIUMTEXT NULL AFTER `az_id`;

CREATE TABLE IF NOT EXISTS `#__xbmusic_azstations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `az_id` int(10) unsigned NOT NULL Default '0',
  `title` varchar(190) NOT NULL,
  `alias` varchar(190) NOT NULL,
  `description` mediumtext,
  `az_info` mediumtext,
  `mediapath` varchar(190),
  `listen_url` varchar(190) NOT NULL DEFAULT '',
  `url` varchar(190) NOT NULL DEFAULT '',
  `public_player_url` varchar(190) NOT NULL DEFAULT '',
  `catid` int(10) NOT NULL  DEFAULT '0',
  `access` int(10) NOT NULL  DEFAULT '1',
  `status` tinyint(3) NOT NULL DEFAULT '1',
  `created` datetime,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `checked_out` int(10) unsigned,
  `checked_out_time` datetime,
  `modified` datetime,
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metadata` mediumtext NOT NULL DEFAULT '',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `params` mediumtext NOT NULL DEFAULT '',
  `note` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE (`az_id`),
  UNIQUE (`alias`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
