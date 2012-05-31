CREATE TABLE IF NOT EXISTS `#__ars_vgroups` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `created` datetime NOT NULL,
    `created_by` int(11) NOT NULL DEFAULT '0',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` bigint(20) NOT NULL DEFAULT '0',
    `published` int(11) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_categories` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(255) NOT NULL,
    `description` mediumtext,
    `type` enum('normal','bleedingedge') NOT NULL DEFAULT 'normal',
    `groups` varchar(255) DEFAULT NULL,
    `directory` varchar(255) NOT NULL DEFAULT 'arsrepo',
	`vgroup_id` bigint(20) NOT NULL DEFAULT '0',
    `created` datetime NOT NULL,
    `created_by` int(11) NOT NULL DEFAULT '0',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` bigint(20) NOT NULL DEFAULT '0',
    `access` int(11) NOT NULL DEFAULT '0',
    `published` int(11) NOT NULL DEFAULT '1',
	`language` char(7) NOT NULL DEFAULT '*',
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_releases` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT(20) UNSIGNED NOT NULL,
    `version` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `maturity` ENUM('alpha','beta','rc','stable') NOT NULL DEFAULT 'beta',
    `description` MEDIUMTEXT NULL,
    `notes` TEXT NULL,
    `groups` varchar(255) DEFAULT NULL,
    `hits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by` int(11) NOT NULL DEFAULT '0',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` bigint(20) unsigned NOT NULL,
    `access` int(11) NOT NULL DEFAULT '0',
    `published` tinyint(1) NOT NULL DEFAULT '1',
	`language` char(7) NOT NULL DEFAULT '*',
	PRIMARY KEY `id` (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_items` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `release_id` BIGINT(20) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `alias` VARCHAR(255) NOT NULL,
    `description` MEDIUMTEXT NOT NULL,
    `type` ENUM('link','file'),
    `filename` VARCHAR(255) NULL DEFAULT '',
    `url` VARCHAR(255) NULL DEFAULT '',
    `updatestream` BIGINT(20) UNSIGNED DEFAULT NULL,
    `md5` varchar(32) DEFAULT NULL,
    `sha1` varchar(64) DEFAULT NULL,
    `filesize` int(10) unsigned DEFAULT NULL,
    `groups` varchar(255) DEFAULT NULL,
    `hits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `created` datetime NOT NULL,
    `created_by` int(11) NOT NULL DEFAULT '0',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` bigint(20) unsigned NOT NULL,
    `access` int(11) NOT NULL DEFAULT '0',
    `published` tinyint(1) NOT NULL DEFAULT '1',
	`language` char(7) NOT NULL DEFAULT '*',
    `environments` varchar(255) DEFAULT NULL,
	PRIMARY KEY `id` (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_log` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `item_id` BIGINT(20) UNSIGNED NOT NULL,
    `accessed_on` DATETIME NOT NULL,
    `referer` VARCHAR(255) NOT NULL,
    `ip` VARCHAR(255) NOT NULL,
    `country` VARCHAR(3) NOT NULL,
    `authorized` TINYINT(1) NOT NULL DEFAULT '1',
	PRIMARY KEY `id` (`id`),
	KEY `ars_log_accessed` (`accessed_on`),
	KEY `ars_log_authorized` (`authorized`),
	KEY `ars_log_itemid` (`item_id`),
	KEY `ars_log_userid` (`user_id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_updatestreams` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`alias` VARCHAR(255) NOT NULL,
	`type` ENUM('components','libraries','modules','packages','plugins','files','templates') NOT NULL DEFAULT 'components',
	`element` VARCHAR(255) NOT NULL,
	`category` BIGINT(20) UNSIGNED NOT NULL,
	`packname` VARCHAR(255),
	`client_id` int(1) NOT NULL DEFAULT '1',
	`folder` varchar(255) DEFAULT '',
	`created` datetime NOT NULL,
	`created_by` int(11) NOT NULL DEFAULT '0',
	`modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`published` int(11) NOT NULL DEFAULT '1',
	PRIMARY KEY `id` (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_autoitemdesc` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`category` bigint(20) unsigned NOT NULL,
	`packname` varchar(255) DEFAULT NULL,
	`title` varchar(255) NOT NULL,
	`description` MEDIUMTEXT NOT NULL,
	`environments` varchar(100) DEFAULT NULL,
	`published` int(11) NOT NULL DEFAULT '1',
	PRIMARY KEY `id` (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `#__ars_environments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '',
  `xmltitle` varchar(20) NOT NULL DEFAULT '1.0',
  `icon` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;