ALTER TABLE `#__xbmusic_playlists` ADD `az_total_length` INT(10) unsigned NOT NULL DEFAULT '0' AFTER `az_weight`;
ALTER TABLE `#__xbmusic_playlists` ADD `az_num_songs` INT(10) unsigned NOT NULL DEFAULT '0' AFTER `az_weight`;
