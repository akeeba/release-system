ALTER TABLE `#__ars_categories`
    ADD COLUMN
        `asset_id` int(10) UNSIGNED NOT NULL DEFAULT 0
        AFTER `id`;