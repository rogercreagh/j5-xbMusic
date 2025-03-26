# v0.0.42.7 adding scheduled column to xbmusic_playlists
ALTER TABLE `#__xbmusic_playlists` ADD `scheduled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show`;
