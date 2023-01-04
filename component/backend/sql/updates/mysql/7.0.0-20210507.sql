/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- NULL datetimes
ALTER TABLE `#__ars_categories`
    MODIFY `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_categories`
SET `created` = NULL
WHERE `created` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_categories`
    MODIFY `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_categories`
SET `modified` = NULL
WHERE `modified` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_categories`
    MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_categories`
SET `checked_out_time` = NULL
WHERE `checked_out_time` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_releases`
    MODIFY `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_releases`
SET `created` = NULL
WHERE `created` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_releases`
    MODIFY `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_releases`
SET `modified` = NULL
WHERE `modified` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_releases`
    MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_releases`
SET `checked_out_time` = NULL
WHERE `checked_out_time` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_items`
    MODIFY `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_items`
SET `created` = NULL
WHERE `created` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_items`
    MODIFY `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_items`
SET `modified` = NULL
WHERE `modified` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_items`
    MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_items`
SET `checked_out_time` = NULL
WHERE `checked_out_time` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_updatestreams`
    MODIFY `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_updatestreams`
SET `created` = NULL
WHERE `created` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_updatestreams`
    MODIFY `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_updatestreams`
SET `modified` = NULL
WHERE `modified` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_updatestreams`
    MODIFY `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_updatestreams`
SET `checked_out_time` = NULL
WHERE `checked_out_time` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_log`
    MODIFY `accessed_on` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_log`
SET `accessed_on` = NULL
WHERE `accessed_on` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_dlidlabels`
    MODIFY `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_dlidlabels`
SET `created` = NULL
WHERE `created` = '0000-00-00 00:00:00';

ALTER TABLE `#__ars_dlidlabels`
    MODIFY `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__ars_dlidlabels`
SET `modified` = NULL
WHERE `modified` = '0000-00-00 00:00:00';

-- Convert tables to InnoDB
ALTER TABLE `#__ars_categories`
    ENGINE InnoDB;
ALTER TABLE `#__ars_releases`
    ENGINE InnoDB;
ALTER TABLE `#__ars_items`
    ENGINE InnoDB;
ALTER TABLE `#__ars_log`
    ENGINE InnoDB;
ALTER TABLE `#__ars_updatestreams`
    ENGINE InnoDB;
ALTER TABLE `#__ars_autoitemdesc`
    ENGINE InnoDB;
ALTER TABLE `#__ars_environments`
    ENGINE InnoDB;
ALTER TABLE `#__ars_dlidlabels`
    ENGINE InnoDB;

-- Convert tables to UTF8MB4
ALTER TABLE `#__ars_categories`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_releases`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_items`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_log`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_updatestreams`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_autoitemdesc`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_environments`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `#__ars_dlidlabels`
    DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

-- Drop the JED ID which is no longer used
ALTER TABLE `#__ars_updatestreams`
    DROP COLUMN `jedid`;