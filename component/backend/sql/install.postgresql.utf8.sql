/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

CREATE TABLE IF NOT EXISTS "#__ars_categories"
(
    "id"                SERIAL       NOT NULL,
    "asset_id"          INTEGER      NOT NULL DEFAULT 0,
    "title"             VARCHAR(255) NOT NULL,
    "alias"             VARCHAR(255) NOT NULL,
    "description"       TEXT,
    "type"              VARCHAR(20)  NOT NULL DEFAULT 'normal' CHECK ("type" IN ('normal', 'bleedingedge')),
    "directory"         VARCHAR(255) NOT NULL DEFAULT 'arsrepo',
    "created"           TIMESTAMP    NULL     DEFAULT NULL,
    "created_by"        INTEGER      NOT NULL DEFAULT 0,
    "modified"          TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time"  TIMESTAMP    NULL     DEFAULT NULL,
    "ordering"          BIGINT       NOT NULL DEFAULT 0,
    "access"            INTEGER      NOT NULL DEFAULT 0,
    "show_unauth_links" SMALLINT     NOT NULL DEFAULT 0,
    "redirect_unauth"   VARCHAR(255) NOT NULL DEFAULT '',
    "published"         INTEGER      NOT NULL DEFAULT 1,
    "is_supported"      SMALLINT     NOT NULL DEFAULT 1,
    "language"          CHAR(7)      NOT NULL DEFAULT '*',
    PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "#__ars_categories_published" ON "#__ars_categories" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_releases"
(
    "id"                SERIAL       NOT NULL,
    "category_id"       BIGINT       NOT NULL,
    "version"           VARCHAR(255) NOT NULL,
    "alias"             VARCHAR(255) NOT NULL,
    "maturity"          VARCHAR(20)  NOT NULL DEFAULT 'beta' CHECK ("maturity" IN ('alpha', 'beta', 'rc', 'stable')),
    "notes"             TEXT         NULL,
    "hits"              BIGINT       NOT NULL DEFAULT 0,
    "created"           TIMESTAMP    NULL     DEFAULT NULL,
    "created_by"        INTEGER      NOT NULL DEFAULT 0,
    "modified"          TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time"  TIMESTAMP    NULL     DEFAULT NULL,
    "ordering"          BIGINT       NOT NULL,
    "access"            INTEGER      NOT NULL DEFAULT 0,
    "show_unauth_links" SMALLINT     NOT NULL DEFAULT 0,
    "redirect_unauth"   VARCHAR(255) NOT NULL DEFAULT '',
    "published"         SMALLINT     NOT NULL DEFAULT 1,
    "language"          CHAR(7)      NOT NULL DEFAULT '*',
    PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "#__ars_releases_category_id" ON "#__ars_releases" ("category_id");
CREATE INDEX IF NOT EXISTS "#__ars_releases_published" ON "#__ars_releases" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_items"
(
    "id"                SERIAL       NOT NULL,
    "release_id"        BIGINT       NOT NULL,
    "title"             VARCHAR(255) NOT NULL,
    "alias"             VARCHAR(255) NOT NULL,
    "description"       TEXT         NOT NULL,
    "type"              VARCHAR(10) CHECK ("type" IN ('link', 'file')),
    "filename"          VARCHAR(255)          DEFAULT '',
    "url"               VARCHAR(255)          DEFAULT '',
    "updatestream"      BIGINT                DEFAULT NULL,
    "md5"               VARCHAR(32)           DEFAULT NULL,
    "sha1"              VARCHAR(64)           DEFAULT NULL,
    "sha256"            VARCHAR(64)           DEFAULT NULL,
    "sha384"            VARCHAR(96)           DEFAULT NULL,
    "sha512"            VARCHAR(128)          DEFAULT NULL,
    "filesize"          INTEGER               DEFAULT NULL,
    "hits"              BIGINT       NOT NULL DEFAULT 0,
    "created"           TIMESTAMP    NULL     DEFAULT NULL,
    "created_by"        INTEGER      NOT NULL DEFAULT 0,
    "modified"          TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time"  TIMESTAMP    NULL     DEFAULT NULL,
    "ordering"          BIGINT       NOT NULL,
    "access"            INTEGER      NOT NULL DEFAULT 0,
    "show_unauth_links" SMALLINT     NOT NULL DEFAULT 0,
    "redirect_unauth"   VARCHAR(255) NOT NULL DEFAULT '',
    "published"         SMALLINT     NOT NULL DEFAULT 1,
    "language"          CHAR(7)      NOT NULL DEFAULT '*',
    "environments"      VARCHAR(255)          DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "#__ars_items_release_id" ON "#__ars_items" ("release_id");
CREATE INDEX IF NOT EXISTS "#__ars_items_updatestream" ON "#__ars_items" ("updatestream");
CREATE INDEX IF NOT EXISTS "#__ars_items_published" ON "#__ars_items" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_log"
(
    "id"          SERIAL       NOT NULL,
    "user_id"     BIGINT       NOT NULL,
    "item_id"     BIGINT       NOT NULL,
    "accessed_on" TIMESTAMP    NULL     DEFAULT NULL,
    "referer"     VARCHAR(255) NOT NULL,
    "ip"          VARCHAR(255) NOT NULL,
    "authorized"  SMALLINT     NOT NULL DEFAULT 1,
    PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "#__ars_log_accessed" ON "#__ars_log" ("accessed_on");
CREATE INDEX IF NOT EXISTS "#__ars_log_authorized" ON "#__ars_log" ("authorized");
CREATE INDEX IF NOT EXISTS "#__ars_log_itemid" ON "#__ars_log" ("item_id");
CREATE INDEX IF NOT EXISTS "#__ars_log_userid" ON "#__ars_log" ("user_id");

CREATE TABLE IF NOT EXISTS "#__ars_updatestreams"
(
    "id"               SERIAL       NOT NULL,
    "name"             VARCHAR(255) NOT NULL,
    "alias"            VARCHAR(255) NOT NULL,
    "type"             VARCHAR(20)  NOT NULL DEFAULT 'components'
        CHECK ("type" IN ('components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates')),
    "element"          VARCHAR(255) NOT NULL,
    "category"         BIGINT       NOT NULL,
    "packname"         VARCHAR(255),
    "client_id"        INTEGER      NOT NULL DEFAULT 1,
    "folder"           VARCHAR(255)          DEFAULT '',
    "created"          TIMESTAMP    NULL     DEFAULT NULL,
    "created_by"       INTEGER      NOT NULL DEFAULT 0,
    "modified"         TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"      INTEGER      NOT NULL DEFAULT 0,
    "checked_out"      INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time" TIMESTAMP    NULL     DEFAULT NULL,
    "published"        INTEGER      NOT NULL DEFAULT 1,
    PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "#__ars_updatestreams_published" ON "#__ars_updatestreams" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_autoitemdesc"
(
    "id"                SERIAL       NOT NULL,
    "category"          BIGINT       NOT NULL,
    "packname"          VARCHAR(255)          DEFAULT NULL,
    "title"             VARCHAR(255) NOT NULL,
    "access"            INTEGER      NOT NULL DEFAULT 0,
    "show_unauth_links" SMALLINT     NOT NULL DEFAULT 0,
    "redirect_unauth"   VARCHAR(255) NOT NULL DEFAULT '',
    "description"       TEXT         NOT NULL,
    "environments"      VARCHAR(100)          DEFAULT NULL,
    "created"           TIMESTAMP    NULL     DEFAULT NULL,
    "created_by"        INTEGER      NOT NULL DEFAULT 0,
    "modified"          TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out"       INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time"  TIMESTAMP    NULL     DEFAULT NULL,
    "published"         INTEGER      NOT NULL DEFAULT 1,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__ars_environments"
(
    "id"               SERIAL       NOT NULL,
    "title"            VARCHAR(100) NOT NULL DEFAULT '',
    "xmltitle"         VARCHAR(20)  NOT NULL DEFAULT '1.0',
    "created"          TIMESTAMP    NULL     DEFAULT NULL,
    "created_by"       INTEGER      NOT NULL DEFAULT 0,
    "modified"         TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"      INTEGER      NOT NULL DEFAULT 0,
    "checked_out"      INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time" TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__ars_dlidlabels"
(
    "id"               SERIAL       NOT NULL,
    "user_id"          BIGINT       NOT NULL,
    "primary"          SMALLINT     NOT NULL DEFAULT 0,
    "title"            VARCHAR(255) NOT NULL DEFAULT '',
    "dlid"             CHAR(32)     NOT NULL,
    "published"        SMALLINT     NOT NULL DEFAULT 1,
    "created_by"       BIGINT       NOT NULL DEFAULT 0,
    "created"          TIMESTAMP    NULL     DEFAULT NULL,
    "modified_by"      BIGINT       NOT NULL DEFAULT 0,
    "modified"         TIMESTAMP    NULL     DEFAULT NULL,
    "checked_out"      INTEGER      NOT NULL DEFAULT 0,
    "checked_out_time" TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY ("id")
);

INSERT INTO "#__ars_environments" ("id", "title", "xmltitle")
VALUES (1, 'Joomla! 1.5', 'joomla/1.5'),
       (2, 'Joomla! 1.6', 'joomla/1.6'),
       (3, 'Joomla! 1.7', 'joomla/1.7'),
       (4, 'Joomla! 2.5', 'joomla/2.5'),
       (5, 'Joomla! 3.x', 'joomla/3'),
       (6, 'Joomla! 3.0', 'joomla/3.0'),
       (7, 'Joomla! 3.1', 'joomla/3.1'),
       (8, 'Joomla! 3.2', 'joomla/3.2'),
       (9, 'Joomla! 3.3', 'joomla/3.3'),
       (10, 'Joomla! 3.4', 'joomla/3.4'),
       (11, 'Joomla! 3.5', 'joomla/3.5'),
       (28, 'Joomla! 3.6', 'joomla/3.6'),
       (29, 'Joomla! 3.7', 'joomla/3.7'),
       (30, 'Joomla! 3.8', 'joomla/3.8'),
       (31, 'Joomla! 3.9', 'joomla/3.9'),
       (42, 'Joomla! 3.10', 'joomla/3.10'),
       (32, 'Joomla! 4.0', 'joomla/4.0'),
       (33, 'Joomla! 4.1', 'joomla/4.1'),
       (12, 'Linux (32-bit)', 'linux/x86'),
       (13, 'Linux (64-bit)', 'linux/x86-64'),
       (14, 'macOS', 'macosx/10'),
       (15, 'WHMCS 4.5.2', 'whmcs/4.5.2'),
       (16, 'Windows 7', 'win/7'),
       (17, 'Windows XP', 'win/xp'),
       (34, 'Windows 8', 'win/8'),
       (35, 'Windows 10', 'win/10'),
       (18, 'WordPress 3.2+', 'wordpress/3'),
       (19, 'WordPress 4.x', 'wordpress/4'),
       (36, 'WordPress 5.x', 'wordpress/5'),
       (37, 'ClassicPress 1.x', 'classicpress/1'),
       (20, 'ePub', 'epub/3.0'),
       (21, 'PDF', 'pdf/1.5'),
       (22, 'PHP 5.2', 'php/5.2'),
       (23, 'PHP 5.3', 'php/5.3'),
       (24, 'PHP 5.4', 'php/5.4'),
       (25, 'PHP 5.5', 'php/5.5'),
       (26, 'PHP 5.6', 'php/5.6'),
       (27, 'PHP 7.0', 'php/7.0'),
       (38, 'PHP 7.1', 'php/7.1'),
       (39, 'PHP 7.2', 'php/7.2'),
       (40, 'PHP 7.3', 'php/7.3'),
       (41, 'PHP 8.0', 'php/8.0'),
       (43, 'PHP 8.1', 'php/8.1')
ON CONFLICT (id) DO NOTHING;

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- UCM Content types (for tags)
DELETE
FROM "#__content_types"
WHERE "type_alias" = 'com_ars.category';

DELETE
FROM "#__content_types"
WHERE "type_alias" = 'com_ars.release';

INSERT INTO "#__content_types"
("type_id", "type_title", "type_alias", "table", "rules", "field_mappings", "router", "content_history_options")
VALUES (19, 'ARS Category', 'com_ars.category',
        '{   		"special": {   			"dbtable": "#__ars_categories",   			"key": "id",   			"type": "CategoryTable",   			"prefix": "Akeeba\\\\Component\\\\ARS\\\\Administrator\\\\Table\\\\",   			"config": "array()"   		},   		"common": {   			"dbtable": "#__ucm_content",   			"key": "ucm_id",   			"type": "Corecontent",   			"prefix": "Joomla\\\\CMS\\\\Table\\\\",   			"config": "array()"   		}   	}',
        '',
        '{   		"common": {   			"core_content_item_id": "id",   			"core_title": "title",   			"core_state": "published",   			"core_alias": "alias",   			"core_created_time": "created",   			"core_modified_time": "modified",   			"core_body": "description",   			"core_hits": "null",   			"core_publish_up": "null",   			"core_publish_down": "null",   			"access": "access",   			"core_params": "null",   			"core_featured": "null",   			"core_metadata": "null",   			"core_language": "language",   			"core_images": "null",   			"core_urls": "null",   			"core_version": "null",   			"core_ordering": "ordering",   			"core_metakey": "null",   			"core_metadesc": "null",   			"core_catid": "null",   			"asset_id": "asset_id"   		},   		"special": {   			"type": "type",   			"directory": "directory",   			"show_unauth_links": "show_unauth_links",   			"redirect_unauth": "redirect_unauth",   			"is_supported": "is_supported"   		}   	}   ',
        'Akeeba\\Component\\ATS\\Site\\Helper\\Route::getCategoryRoute', '');

INSERT INTO "#__content_types" ("type_id", "type_title", "type_alias", "table", "rules", "field_mappings", "router",
                                "content_history_options")
VALUES (20, 'ARS Release', 'com_ars.release',
        '{   		"special": {   			"dbtable": "#__ars_releases",   			"key": "id",   			"type": "ReleaseTable",   			"prefix": "Akeeba\\\\Component\\\\ARS\\\\Administrator\\\\Table\\\\",   			"config": "array()"   		},   		"common": {   			"dbtable": "#__ucm_content",   			"key": "ucm_id",   			"type": "Corecontent",   			"prefix": "Joomla\\\\CMS\\\\Table\\\\",   			"config": "array()"   		}   	}',
        '',
        '{   		"common": {   			"core_content_item_id": "id",   			"core_title": "version",   			"core_state": "published",   			"core_alias": "alias",   			"core_created_time": "created",   			"core_modified_time": "modified",   			"core_body": "notes",   			"core_hits": "hits",   			"core_publish_up": "null",   			"core_publish_down": "null",   			"access": "access",   			"core_params": "null",   			"core_featured": "null",   			"core_metadata": "null",   			"core_language": "language",   			"core_images": "null",   			"core_urls": "null",   			"core_version": "null",   			"core_ordering": "ordering",   			"core_metakey": "null",   			"core_metadesc": "null",   			"core_catid": "null",   			"asset_id": "null"   		},   		"special": {   			"category_id": "category_id",   			"maturity": "maturity",   			"show_unauth_links": "show_unauth_links",   			"redirect_unauth": "redirect_unauth"   		}   	}',
        'Akeeba\\Component\\ATS\\Site\\Helper\\Route::getReleaseRoute', '');