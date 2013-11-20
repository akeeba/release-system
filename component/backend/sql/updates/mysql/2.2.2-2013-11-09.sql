ALTER TABLE `#__ars_categories` ADD COLUMN `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access` ;
ALTER TABLE `#__ars_releases` ADD COLUMN `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access` ;
ALTER TABLE `#__ars_items` ADD COLUMN `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access` ;

ALTER TABLE `#__ars_categories` ADD COLUMN `redirect_unauth` VARCHAR( 255 ) NOT NULL AFTER `show_unauth_links`;
ALTER TABLE `#__ars_releases` ADD COLUMN `redirect_unauth` VARCHAR( 255 ) NOT NULL AFTER `show_unauth_links`;
ALTER TABLE `#__ars_items` ADD COLUMN `redirect_unauth` VARCHAR( 255 ) NOT NULL AFTER `show_unauth_links`;