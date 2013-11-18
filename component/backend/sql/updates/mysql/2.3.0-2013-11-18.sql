ALTER TABLE `#__ars_updatestreams` ADD `jedid` bigint(20) NOT NULL AFTER `folder` ;
ALTER TABLE `#__ars_updatestreams` ADD INDEX KEY `#__ars_updatestreams_jedid` (`jedid`);