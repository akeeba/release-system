/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

ALTER TABLE `#__ars_categories`
    ADD COLUMN
        `asset_id` int(10) UNSIGNED NOT NULL DEFAULT 0
        AFTER `id`;