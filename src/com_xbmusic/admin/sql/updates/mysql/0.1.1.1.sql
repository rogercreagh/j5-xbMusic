#adding column azuracast track id in xbmusic_tracks
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_tracks` CHANGE `az_tid` `az_tid` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Unique_id from Azuracast';
ALTER TABLE `#__xbmusic_tracks` ADD `az_sid` VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'Song_id from Azuracast' AFTER `az_tid`;

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them
