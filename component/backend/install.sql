CREATE TABLE IF NOT EXISTS `#__ars_categories` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(255) NOT NULL,
    `description` mediumtext,
    `type` enum('normal','bleedingedge') NOT NULL DEFAULT 'normal',
    `groups` varchar(255) DEFAULT NULL,
    `directory` varchar(255) NOT NULL DEFAULT 'arsrepo',
    `created` datetime NOT NULL,
    `created_by` int(11) NOT NULL DEFAULT '0',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
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
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by` int(11) NOT NULL DEFAULT '0',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` bigint(20) unsigned NOT NULL,
    `access` int(11) NOT NULL DEFAULT '0',
    `published` tinyint(1) NOT NULL DEFAULT '1'
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
    `published` tinyint(1) NOT NULL DEFAULT '1'
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__ars_log` (
    `id` SERIAL,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `item_id` BIGINT(20) UNSIGNED NOT NULL,
    `accessed_on` DATETIME NOT NULL,
    `referer` VARCHAR(255) NOT NULL,
    `ip` VARCHAR(255) NOT NULL,
    `country` VARCHAR(3) NOT NULL,
    `authorized` TINYINT(1) NOT NULL DEFAULT '1'
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__ars_updatestreams` (
  `id` SERIAL,
  `name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(255) NOT NULL,
  `type` ENUM('components','libraries','modules','packages','plugins','files','templates') NOT NULL DEFAULT 'components',
  `element` VARCHAR(255) NOT NULL,
  `category` BIGINT(20) UNSIGNED NOT NULL,
  `packname` VARCHAR(255),
  `created` datetime NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `published` int(11) NOT NULL DEFAULT '1'
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__ars_autoitemdesc` (
  `id` SERIAL,
  `category` bigint(20) unsigned NOT NULL,
  `packname` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` MEDIUMTEXT NOT NULL,
  `published` int(11) NOT NULL DEFAULT '1'
) DEFAULT CHARSET=utf8;
