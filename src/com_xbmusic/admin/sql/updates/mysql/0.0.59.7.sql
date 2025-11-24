#adding column for syncd datetime on azstation, playlist, and schedule
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_azstations` ADD `slug` varchar(190) NOT NULL DEFAULT '' AFTER `alias`;

ALTER TABLE `#__xbmusic_azplaylists` ADD `slug` varchar(190) NOT NULL DEFAULT ''  AFTER `alias`;

ALTER TABLE `#__xbmusic_azschedules` ADD `slug` varchar(190) NOT NULL DEFAULT ''  AFTER `id`;
ALTER TABLE `#__xbmusic_azschedules` ADD `description` mediumtext  AFTER `slug`;

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them


