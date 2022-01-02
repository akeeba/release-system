/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `ars_dlidlabel_id`
        `id` bigint unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `created_on`
        `created` datetime NULL DEFAULT NULL;

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `modified_on`
        `modified` datetime NULL DEFAULT NULL;

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `label`
        `title` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `enabled`
        `published` tinyint(3) NOT NULL DEFAULT '1';
