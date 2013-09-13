ALTER TABLE `#__ars_categories` ADD `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access` ;
ALTER TABLE `#__ars_releases` ADD `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access` ;
ALTER TABLE `#__ars_items` ADD `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access` ;

ALTER TABLE `#__ars_categories` ADD `redirect_unauth` VARCHAR( 255 ) NOT NULL AFTER `show_unauth_links`;
ALTER TABLE `#__ars_releases` ADD `redirect_unauth` VARCHAR( 255 ) NOT NULL AFTER `show_unauth_links`;
ALTER TABLE `#__ars_items` ADD `redirect_unauth` VARCHAR( 255 ) NOT NULL AFTER `show_unauth_links`;