#adding foriegn keys to playlists, and schedules
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_azplaylists` ADD FOREIGN KEY (`db_stid`) REFERENCES `#__xbmusic_azstations`(`id`) ON DELETE CASCADE;

ALTER TABLE `#__xbmusic_azschedules` ADD FOREIGN KEY (`dbplid`) REFERENCES `#__xbmusic_azplaylists`(`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them


