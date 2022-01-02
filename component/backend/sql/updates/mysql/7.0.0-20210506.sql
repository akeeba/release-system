/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Missing columns, #__ars_autoitemdesc

ALTER TABLE `#__ars_autoitemdesc`
    ADD COLUMN
        `created` datetime NULL DEFAULT NULL
        AFTER `environments`;

ALTER TABLE `#__ars_autoitemdesc`
    ADD COLUMN
        `created_by` int(11) NOT NULL DEFAULT '0'
        AFTER `created`;

ALTER TABLE `#__ars_autoitemdesc`
    ADD COLUMN
        `modified` datetime NULL DEFAULT NULL
        AFTER `created_by`;

ALTER TABLE `#__ars_autoitemdesc`
    ADD COLUMN
        `modified_by` int(11) NOT NULL DEFAULT '0'
        AFTER `modified`;

ALTER TABLE `#__ars_autoitemdesc`
    ADD COLUMN
        `checked_out` int(11) NOT NULL DEFAULT '0'
        AFTER `modified_by`;

ALTER TABLE `#__ars_autoitemdesc`
    ADD COLUMN
        `checked_out_time` datetime NULL DEFAULT NULL
        AFTER `checked_out`;

-- Missing columns, #__ars_environments

ALTER TABLE `#__ars_environments`
    ADD COLUMN
        `created` datetime NULL DEFAULT NULL;

ALTER TABLE `#__ars_environments`
    ADD COLUMN
        `created_by` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `#__ars_environments`
    ADD COLUMN
        `modified` datetime NULL DEFAULT NULL;

ALTER TABLE `#__ars_environments`
    ADD COLUMN
        `modified_by` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `#__ars_environments`
    ADD COLUMN
        `checked_out` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `#__ars_environments`
    ADD COLUMN
        `checked_out_time` datetime NULL DEFAULT NULL;

-- Missing columns, #__ars_dlidlabels

ALTER TABLE `#__ars_dlidlabels`
    ADD COLUMN
        `checked_out` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `#__ars_dlidlabels`
    ADD COLUMN
        `checked_out_time` datetime NULL DEFAULT NULL;