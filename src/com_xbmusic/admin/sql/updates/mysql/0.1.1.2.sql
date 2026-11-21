#adding column azuracast track id in xbmusic_tracks
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_aztrack_history` CHANGE `az_tid` `az_sid` VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'Az UniqueSong_id';

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them
