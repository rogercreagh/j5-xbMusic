#adding column for server url to apikeys and unique index on juser+keyid
SET FOREIGN_KEY_CHECKS=0; -- to disable them

ALTER TABLE `#__xbmusic_userapikeys` ADD `az_url` VARCHAR(80) NOT NULL DEFAULT 'https://radio.xbone.uk' AFTER `user_id`;

ALTER TABLE `#__xbmusic_userapikeys` ADD UNIQUE `userapiidx` (`user_id`, `az_apikeyid`);

SET FOREIGN_KEY_CHECKS=1; -- to re-enable them


