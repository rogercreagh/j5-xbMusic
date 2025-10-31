SET FOREIGN_KEY_CHECKS=0; -- to disable them

CREATE TABLE IF NOT EXISTS `#__xbmusic_userapikeyss` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `az_apikeyid` varchar(20) NOT NULL,
  `az_apikeyval` varchar(40) NOT NULL,
  `az_apicomment` varchar(255) NOT NULL DEFAULT '',	
  `selected` tinyint(1) NOT NULL Default '0',
  PRIMARY KEY (`id`),
#  UNIQUE `userapikeyid` (`user_id`,`az_apikeyval`),
  FOREIGN KEY(`user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__xbmusic_azstations` DROP `az_apikey`;
ALTER TABLE `#__xbmusic_azstations` DROP `az_apiname`;

CREATE TABLE IF NOT EXISTS `#__xbmusic_azplaylists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  
  `title` varchar(190) NOT NULL DEFAULT '',
  `alias` varchar(190) NOT NULL DEFAULT '',
  `scheduledcnt` int(10) unsigned NOT NULL DEFAULT '0',
  `publicschd` int(1) unsigned NOT NULL DEFAULT '1',
  `allowdupes` int(1) unsigned NOT NULL DEFAULT '0',
  `az_plid` int(10) unsigned NOT NULL Default '0',
  `az_name` varchar(20),
  `db_stid` int(10) unsigned NOT NULL Default '0',
  `az_info` mediumtext,
  `az_type` tinyint(3),
  `az_cntper` tinyint(3) unsigned,
  `az_order` varchar(20),
  `az_jingle` boolean,
  `az_weight` tinyint(3) unsigned,
  `az_num_songs` int(10) unsigned NOT NULL DEFAULT '0',
  `az_total_length` int(10) unsigned NOT NULL DEFAULT '0',
  `description` mediumtext,
  `catid` int(10) NOT NULL  DEFAULT '0',
  `access` int(10) NOT NULL  DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '0',
  `created` datetime,
  `created_by` int(10) NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `checked_out` int(10) unsigned,
  `checked_out_time` datetime,
  `modified` datetime,
  `modified_by` int(10) NOT NULL DEFAULT '0',
  `metadata` mediumtext NOT NULL DEFAULT '',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `params` mediumtext NOT NULL DEFAULT '',
  `note` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE (`alias`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__xbmusic_azplaylists`;

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them


