ALTER TABLE `#__ars_dlidlabels`
    CHANGE `ars_dlidlabel_id`
        `id` bigint unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `created_on`
        `created` datetime NULL DEFAULT NULL;

ALTER TABLE `#__ars_dlidlabels`
    CHANGE `modified_on`
        `modified` datetime NULL DEFAULT NULL;
