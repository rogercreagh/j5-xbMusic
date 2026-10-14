#adding column azuracast track id in xbmusic_tracks
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_tracks` CHANGE `az_trid` `az_tid` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Song (track) ID from Azuracast';

CREATE TABLE IF NOT EXISTS `#__xbmusic_aztrack_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `track_id` int(11) NOT NULL DEFAULT '0',
  `az_tid` varchar(20) NOT NULL COMMENT 'Song (track) iID from Azuracast',
  `st_id` int(10) unsigned COMMENT 'local xbmusic station ID (az station id not unique across servers)',
  `az_sh_id` int(10) unsigned NOT NULL COMMENT 'song history ID from azuracast',
  `playtime` datetime,	
  `playlist_id` int(10) COMMENT 'null if playlist not in xbmusic',
  `az_playlist` varchar(30) COMMENT 'Name provided by azuracast',
  `note` mediumtext COMMENT 'used to save track info if not matched',
  PRIMARY KEY (`id`),
  KEY(`track_id`)

 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS=1; -- to re-enable them
