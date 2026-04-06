/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Track the filemtime() of the category folder at the time of last scan.
-- NULL means the category has never been scanned, forcing a full scan on first page load.
ALTER TABLE `#__ars_categories`
    ADD COLUMN `last_scan` INT UNSIGNED NULL DEFAULT NULL;

-- Track the filemtime() of each release folder at the time of last checkFiles() run.
-- NULL means the release has never been scanned, forcing checkFiles() on first page load.
ALTER TABLE `#__ars_releases`
    ADD COLUMN `folder_mtime` INT UNSIGNED NULL DEFAULT NULL;
