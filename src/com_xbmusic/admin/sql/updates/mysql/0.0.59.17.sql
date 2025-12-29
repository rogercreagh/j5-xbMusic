#adding column for tracks_sync datetime on playlist
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_azplaylists` ADD `tracks_sync` datetime AFTER `lastsync`;

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them
