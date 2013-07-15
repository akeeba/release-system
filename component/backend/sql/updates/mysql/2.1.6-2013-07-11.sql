ALTER TABLE `#__ars_items` ADD INDEX `#__ars_items_release_id` (release_id);
ALTER TABLE `#__ars_items` ADD INDEX `#__ars_items_updatestream` (updatestream);
ALTER TABLE `#__ars_items` ADD INDEX `#__ars_items_published` (published);
ALTER TABLE `#__ars_releases` ADD INDEX `#__ars_releases_category_id` (category_id);
ALTER TABLE `#__ars_releases` ADD INDEX `#__ars_releases_published` (published);
ALTER TABLE `#__ars_categories` ADD INDEX `#__ars_categories_published` (published);
ALTER TABLE `#__ars_updatestreams` ADD INDEX `#__ars_updatestreams_published` (published);