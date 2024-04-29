# sql installation file for component xbMusic 0.0.4.0 25th April 2024
# NB no data is installed with this file, default categories are created by the installation script

INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) 
VALUES
	('XbMusic Album', 
	'com_xbmusic.album', 
	'{"special":{"dbtable":"#__xbmusic_albums","key":"id","type":"XbmusicTable","prefix":"Joomla\\Component\\Xbmusic\\Administrator\\Table\\","config":"array()"},	    "common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}',
	 '',
	'{"common": {"core_content_item_id": "id", "core_title": "title", "core_state": "status", "core_alias": "alias", "core_body": "description",  "core_catid": "catid" }}',
	'XbMusicHelperRoute::getAlbumRoute',	 
	'{"formFile":"administrator\/components\/com_xbmusic\/forms\\/album.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["checked_out", "checked_out_time"], "convertToInt":["ordering"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}, {"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}, 			{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"}, 			{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ] }'
	),

	('XbMusic Artist', 
	'com_xbmusic.artist', 
	'{"special":{"dbtable":"#__xbmusic_artists","key":"id","type":"XbmusicTable","prefix":"Joomla\\Component\\Xbmusic\\Administrator\\Table\\","config":"array()"},	    "common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}',
	 '',
	'{"common": {"core_content_item_id": "id", "core_title": "name", "core_state": "status", "core_alias": "alias", "core_body": "description",  "core_catid": "catid" }}',
	'XbMusicHelperRoute::getArtistRoute',	 
	'{"formFile":"administrator\/components\/com_xbmusic\/forms\\/artist.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["checked_out", "checked_out_time"], "convertToInt":["ordering"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}, {"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}, 			{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"}, 			{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ] }'
	),

	('XbMusic Playlist', 
	'com_xbmusic.playlist', 
	'{"special":{"dbtable":"#__xbmusic_playlists","key":"id","type":"XbmusicTable","prefix":"Joomla\\Component\\Xbmusic\\Administrator\\Table\\","config":"array()"},	    "common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}',
	 '',
	'{"common": {"core_content_item_id": "id", "core_title": "title", "core_state": "status", "core_alias": "alias", "core_body": "description",  "core_catid": "catid" }}',
	'XbMusicHelperRoute::getPlaylistRoute',	 
	'{"formFile":"administrator\/components\/com_xbmusic\/forms\\/playlist.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["checked_out", "checked_out_time"], "convertToInt":["ordering"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}, {"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}, 			{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"}, 			{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ] }'
	),

	('XbMusic Song', 
	'com_xbmusic.song', 
	'{"special":{"dbtable":"#__xbmusic_songs","key":"id","type":"XbmusicTable","prefix":"Joomla\\Component\\Xbmusic\\Administrator\\Table\\","config":"array()"},	    "common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}',
	 '',
	'{"common": {"core_content_item_id": "id", "core_title": "title", "core_state": "status", "core_alias": "alias", "core_body": "description",  "core_catid": "catid" }}',
	'XbMusicHelperRoute::getSongRoute',	 
	'{"formFile":"administrator\/components\/com_xbmusic\/forms\\/song.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["checked_out", "checked_out_time"], "convertToInt":["ordering"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}, {"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}, 			{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"}, 			{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ] }'
	),

	('XbMusic Track', 
	'com_xbmusic.track', 
	'{"special":{"dbtable":"#__xbmusic_tracks","key":"id","type":"XbmusicTable","prefix":"Joomla\\Component\\Xbmusic\\Administrator\\Table\\","config":"array()"},	    "common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}',
	 '',
	'{"common": {"core_content_item_id": "id", "core_title": "title", "core_state": "status", "core_alias": "alias", "core_body": "description",  "core_catid": "catid" }}',
	'XbMusicHelperRoute::getTrackRoute',	 
	'{"formFile":"administrator\/components\/com_xbmusic\/forms\\/track.xml", "hideFields":["checked_out","checked_out_time"], "ignoreChanges":["checked_out", "checked_out_time"], "convertToInt":["ordering"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}, {"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}, 			{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"}, 			{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ] }'
	);


CREATE TABLE IF NOT EXISTS `#__xbmusic_albums` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL DEFAULT '',
  `alias` varchar(190) NOT NULL DEFAULT '',
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext,
  `artwork` mediumtext NOT NULL DEFAULT '',
  `rel_date` date,
  `format` varchar(10) NOT NULL DEFAULT '',
  `disccnt` int(3) NOT NULL DEFAULT 1,
  `ext_links` mediumtext,
  `catid` int(10) NOT NULL  DEFAULT '0',
  `access` int(10) NOT NULL  DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '0',
  `created` datetime,
  `created_by` int(10) NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `checked_out` int(10) unsigned,
  `checked_out_time` datetime,
  `modified` datetime,
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metadata` mediumtext NOT NULL DEFAULT '',
  `ordering` int(10) NOT NULL DEFAULT '0',
  `params` mediumtext NOT NULL DEFAULT '',
  `note` mediumtext,
  PRIMARY KEY (`id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX `albumaliasindex` ON `#__xbmusic_albums` (`alias`);

CREATE TABLE IF NOT EXISTS `#__xbmusic_artists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL DEFAULT '',
  `alias` varchar(190) NOT NULL DEFAULT '',
  `description` mediumtext,
  `picture` mediumtext NOT NULL DEFAULT '',
  `year_started` date,
  `year_ended` date,
  `type` varchar(10) NOT NULL DEFAULT '',
  `ext_links` mediumtext,
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
  PRIMARY KEY (`id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX `artistaliasindex` ON `#__xbmusic_artists` (`alias`);

CREATE TABLE IF NOT EXISTS `#__xbmusic_playlists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL DEFAULT '',
  `alias` varchar(190) NOT NULL DEFAULT '',
  `description` mediumtext,
  `picture` mediumtext NOT NULL DEFAULT '',
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
  PRIMARY KEY (`id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX `playlistaliasindex` ON `#__xbmusic_playlists` (`alias`);

CREATE TABLE IF NOT EXISTS `#__xbmusic_songs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL DEFAULT '',
  `alias` varchar(190) NOT NULL DEFAULT '',
  `description` mediumtext,
  `creators` varchar(190) NOT NULL DEFAULT '',
  `date` varchar(190),
  `lyrics` mediumtext,
  `ext_links` mediumtext,
  `catid` int(10) NOT NULL  DEFAULT '0',
  `access` int(10) NOT NULL  DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '0',
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
  PRIMARY KEY (`id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX `songaliasindex` ON `#__xbmusic_songs` (`alias`);

CREATE TABLE IF NOT EXISTS `#__xbmusic_tracks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL DEFAULT '',
  `alias` varchar(190) NOT NULL DEFAULT '',
  `description` mediumtext,
  `picturefile` mediumtext NOT NULL DEFAULT '',
  `id3_picture` blob,
  `picture_meta` mediumtext,
  `id3_tags` mediumtext,
  `filename` varchar(190) NOT NULL,
  `pathname` varchar(190) NOT NULL DEFAULT '',
  `file_meta` mediumtext,
  `song_id` int(11),
  `rec_date` varchar(190),
  `ext_links` mediumtext,
  `catid` int(10) NOT NULL  DEFAULT '0',
  `access` int(10) NOT NULL  DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '0',
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
  PRIMARY KEY (`id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX `trackaliasindex` ON `#__xbmusic_tracks` (`alias`);

CREATE TABLE IF NOT EXISTS `#__xbmusic_artisttrack` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `artist_id` int(10) unsigned NOT NULL DEFAULT '0',
  `track_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role` varchar(255) NOT NULL DEFAULT '',
  `role_note` varchar(255) NOT NULL DEFAULT '',
  `listorder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_artist_id` (`artist_id`),
  KEY `idx_track_id` (`track_id`),
  KEY `idx_role` (`role`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xbmusic_albumtrack` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(10) unsigned NOT NULL DEFAULT '0',
  `track_id` int(10) unsigned NOT NULL DEFAULT '0',
  `discno` int(3) NOT NULL DEFAULT 1,
  `listorder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_album_id` (`album_id`),
  KEY `idx_track_id` (`track_id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xbmusic_playlisttrack` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `playlist_id` int(10) unsigned NOT NULL DEFAULT '0',
  `track_id` int(10) unsigned NOT NULL DEFAULT '0',
  `seqno` int(10) unsigned NOT NULL DEFAULT '0',
  `listorder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_playlist_id` (`playlist_id`),
  KEY `idx_track_id` (`track_id`),
  KEY `idx_role` (`seqno`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xbmusic_medleytrack` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `song_id` int(10) unsigned NOT NULL DEFAULT '0',
  `track_id` int(10) unsigned NOT NULL DEFAULT '0',
  `seqno` int(10) unsigned NOT NULL,
  `listorder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_song_id` (`song_id`),
  KEY `idx_track_id` (`track_id`),
  KEY `idx_role` (`seqno`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xbmusic_groupmember` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `artist_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role` varchar(255) NOT NULL DEFAULT '',
  `role_note` varchar(255) NOT NULL DEFAULT '',
  `from` date,
  `until` date,
  `listorder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_artist_id` (`artist_id`),
  KEY `idx_role` (`role`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


