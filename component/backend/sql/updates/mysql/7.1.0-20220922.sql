/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

ALTER TABLE `#__ars_autoitemdesc` ADD `access` INT(11) NOT NULL DEFAULT '0' AFTER `title`;
