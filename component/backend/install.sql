CREATE TABLE IF NOT EXISTS `#__ars_categories` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`title` varchar(255) NOT NULL,
	`alias` varchar(255) NOT NULL,
	`description` mediumtext,
	`type` enum('normal','bleedingedge') NOT NULL DEFAULT 'normal',
	`groups` varchar(255) DEFAULT NULL,
	`directory` varchar(255) NOT NULL DEFAULT 'arsrepo',
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`created_by` int(11) NOT NULL DEFAULT '0',
	`modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`ordering` bigint(20) NOT NULL DEFAULT '0',
	`access` int(11) NOT NULL DEFAULT '0',
	`published` int(11) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__ars_releases` (
	`id` SERIAL,
	`category_id` BIGINT(20) UNSIGNED NOT NULL,
	`version` VARCHAR(20) NOT NULL,
	`alias` VARCHAR(255) NOT NULL,
	`maturity` ENUM('alpha','beta','rc','stable') NOT NULL DEFAULT 'beta',
	`description` MEDIUMTEXT NULL,
	`notes` TEXT NULL,
	`groups` varchar(255) DEFAULT NULL,
	`hits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`created_by` int(11) NOT NULL DEFAULT '0',
	`modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`ordering` bigint(20) unsigned NOT NULL,
	`access` int(11) NOT NULL DEFAULT '0',
	`published` tinyint(1) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__ars_items` (
	`id` SERIAL,
	`release_id` BIGINT(20) NOT NULL,
	`title` VARCHAR(255) NOT NULL,
	`alias` VARCHAR(255) NOT NULL,
	`description` MEDIUMTEXT NOT NULL,
	`type` ENUM('link','file'),
	`filename` VARCHAR(255) NULL DEFAULT '',
	`url` VARCHAR(255) NULL DEFAULT '',
	`groups` varchar(255) DEFAULT NULL,
	`hits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`created_by` int(11) NOT NULL DEFAULT '0',
	`modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`ordering` bigint(20) unsigned NOT NULL,
	`access` int(11) NOT NULL DEFAULT '0',
	`published` tinyint(1) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__ars_log` (
	`id` SERIAL,
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`item_id` BIGINT(20) UNSIGNED NOT NULL,
	`accessed_on` DATETIME NOT NULL,
	`referer` VARCHAR(255) NOT NULL,
	`ip` VARCHAR(255) NOT NULL,
	`country` VARCHAR(3) NOT NULL,
	`authorized` TINYINT(1) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE OR REPLACE VIEW `#__ars_view_releases` AS
SELECT
	`r`.*, `c`.`title` as `cat_title`, `c`.`alias` as `cat_alias`,
	`c`.`type` as `cat_type`, `c`.`groups` as `cat_groups`,
	`c`.`directory` as `cat_directory`, `c`.`access` as `cat_access`,
	`c`.`published` as `cat_published`
FROM
	#__ars_releases AS `r`
	INNER JOIN `#__ars_categories` AS `c` ON(`c`.`id` = `r`.`category_id`);