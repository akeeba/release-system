CREATE TABLE IF NOT EXISTS `#__ars_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET latin1 NOT NULL,
  `alias` varchar(255) CHARACTER SET latin1 NOT NULL,
  `description` mediumtext CHARACTER SET latin1,
  `type` enum('normal','bleedingedge') CHARACTER SET latin1 NOT NULL DEFAULT 'normal',
  `groups` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `directory` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT 'arsrepo',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` bigint(20) NOT NULL DEFAULT '0',
  `access` int(11) NOT NULL DEFAULT '0',
  `published` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;