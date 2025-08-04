/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

DROP TABLE IF EXISTS "#__ars_categories";
DROP TABLE IF EXISTS "#__ars_releases";
DROP TABLE IF EXISTS "#__ars_items";
DROP TABLE IF EXISTS "#__ars_log";
DROP TABLE IF EXISTS "#__ars_updatestreams";
DROP TABLE IF EXISTS "#__ars_autoitemdesc";
DROP TABLE IF EXISTS "#__ars_environments";
DROP TABLE IF EXISTS "#__ars_dlidlabels";

-- Categories fields: remove custom field values, groups, and the fields themselves.
DELETE FROM "#__fields_values" WHERE "field_id" IN (SELECT "field_id" FROM "#__fields" WHERE "context" = 'com_ars.category');
DELETE FROM "#__fields" WHERE "context" = 'com_ars.category';
DELETE FROM "#__fields_groups" WHERE "context" = 'com_ars.category';

-- Releases fields: remove custom field values, groups, and the fields themselves.
DELETE FROM "#__fields_values" WHERE "field_id" IN (SELECT "field_id" FROM "#__fields" WHERE "context" = 'com_ars.release');
DELETE FROM "#__fields" WHERE "context" = 'com_ars.release';
DELETE FROM "#__fields_groups" WHERE "context" = 'com_ars.release';

-- Categories tags: remove UCM content types and entries (but not tags which are globally shared)
DELETE FROM "#__content_types" WHERE "type_alias" = 'com_ars.category';
DELETE FROM "#__ucm_content" WHERE "core_type_alias" = 'com_ars.category';

-- Releases tags: remove UCM content types and entries (but not tags which are globally shared)
DELETE FROM "#__content_types" WHERE "type_alias" = 'com_ars.release';
DELETE FROM "#__ucm_content" WHERE "core_type_alias" = 'com_ars.release';