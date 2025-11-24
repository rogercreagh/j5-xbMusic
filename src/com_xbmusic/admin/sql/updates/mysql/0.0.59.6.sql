#adding column for syncd datetime on azstation, playlist, and schedule
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_azstations` ADD `lastsync` datetime AFTER `modified_by`;

ALTER TABLE `#__xbmusic_azplaylists` ADD `lastsync` datetime AFTER `modified_by`;

ALTER TABLE `#__xbmusic_azschedules` ADD `lastsync` datetime AFTER `modified_by`;

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them


