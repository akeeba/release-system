/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

ALTER TABLE "#__ars_releases"
    ADD COLUMN "security" SMALLINT NOT NULL DEFAULT 0;
