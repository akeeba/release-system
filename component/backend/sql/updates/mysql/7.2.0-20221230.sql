/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

ALTER TABLE `#__ars_autoitemdesc` ADD `show_unauth_links` TINYINT NOT NULL DEFAULT '0' AFTER `access`;
ALTER TABLE `#__ars_autoitemdesc` ADD `redirect_unauth` VARCHAR(255) NOT NULL DEFAULT '' AFTER `show_unauth_links`;
