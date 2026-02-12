# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Akeeba Release System (ARS) is a Joomla 4/5 extension (package type) for managing software releases and downloads. It ships as `pkg_ars` containing one component (`com_ars`), four plugins, and three modules. Current version: 7.4.x. License: GPL-3.0-or-later.

## Build System

Build uses **Phing** with a shared `buildfiles` repository expected at `../buildfiles/`.

```bash
# Full build (transpile JS, compile CSS, package)
phing git -Dversion=7.4.3

# JavaScript only (ES6 → minified via Babel, uses ../buildfiles/node_modules)
phing compile-javascript

# CSS only (SCSS → minified CSS via sass CLI)
phing compile-css
```

There are no automated tests (no PHPUnit, no Jest). The `api.http` file contains HTTP client requests for manual REST API testing.

## Requirements

- PHP ^8.1.0, extensions: json, simplexml (fileinfo suggested)
- Joomla 4.2+ (supports Joomla 4, 5, and experimental 6)
- Node.js + Babel (via buildfiles) for JS transpilation
- Sass CLI for SCSS compilation

## Architecture

### Namespace & Directory Layout

Root namespace: `Akeeba\Component\ARS\`

```
component/
├── api/src/          → Akeeba\Component\ARS\Api\        (REST API, JSON:API standard)
├── backend/src/      → Akeeba\Component\ARS\Administrator\
│   ├── Controller/   → Singular = edit form, Plural = list view
│   ├── Model/        → Singular = item CRUD, Plural = list with filters
│   ├── Table/        → Database table classes (extend AbstractTable)
│   ├── View/         → Admin HTML views
│   ├── Mixin/        → Reusable traits (see below)
│   ├── Helper/       → ComponentParams, Cache, CacheCleaner
│   ├── Extension/    → ArsComponent (boot, DI, custom fields, categories)
│   ├── Dispatcher/   → Backend request dispatcher
│   └── Service/      → DI provider, HTML service, Router
├── frontend/src/     → Akeeba\Component\ARS\Site\
│   ├── Controller/   → Frontend controllers
│   ├── Model/        → Frontend models (BleedingedgeModel, etc.)
│   ├── View/         → HTML + Update views (XML, INI, JSON formats)
│   └── Service/      → SEF Router
└── media/            → CSS (SCSS sources in css/sources/), JS (ES6), fonts, icons
```

### Key Patterns

- **Joomla native MVC**: No framework abstraction (FOF was removed). Uses Joomla's MVCComponent, AdminModel, ListModel, FormController, etc.
- **Trait-based mixins** (`backend/src/Mixin/`): Horizontal code reuse for controllers, models, tables, and views. Key traits: `ControllerCopyTrait`, `ModelCopyTrait`, `TriggerEventTrait`, `TableCreateModifyTrait`, `EnsureUcmTrait`, `ViewToolbarTrait`.
- **AbstractTable**: All table classes extend this base which provides common check/store logic.
- **Service Provider** (`backend/services/provider.php`): DI container registration for the component.
- **Custom category system**: ARS uses its own `#__ars_categories` table (not `com_categories`). The `ARSPseudoCategory` class implements Joomla's CategoryInterface to integrate with custom fields and tags.

### Domain Entities

| Entity | Table | Category Types |
|---|---|---|
| Categories | `#__ars_categories` | `normal`, `bleedingedge` |
| Releases | `#__ars_releases` | Maturity: alpha/beta/rc/stable |
| Items | `#__ars_items` | Type: `file` or `link` |
| Download Logs | `#__ars_log` | — |
| Download IDs | `#__ars_dlidlabels` | — |
| Environments | `#__ars_environments` | — |
| Update Streams | `#__ars_updatestreams` | Format: ini/xml/json |
| Auto-descriptions | `#__ars_autoitems` | — |

### REST API

Endpoint base: `/api/index.php/v1/ars/` — resources: categories, releases, items. Registered by the `plg_webservices_ars` plugin.

### Plugins & Modules

- **plg_content_arsdlid** — Displays Download IDs in content
- **plg_content_arslatest** — Shows latest release info via content plugin syntax
- **plg_editors-xtd_arslink** — Editor button for inserting ARS links
- **plg_webservices_ars** — Registers JSON:API routes
- **mod_arsdownloads** (site) — Downloads widget
- **mod_arsgraph** (admin) — Download analytics chart (uses Chart.js)

## Coding Conventions

- PSR-12 style, fully namespaced, `defined('_JEXEC') || die;` guard in every file
- Type hints on all parameters and return types
- One class per file, namespace matches directory structure
- Language strings use `COM_ARS_` prefix, defined in `backend/language/en-GB/` and `frontend/language/en-GB/`
- Database SQL schemas provided for both MySQL and PostgreSQL (`backend/sql/`)
- Joomla version-specific code handles API differences (e.g., `createQuery()` on 5.1+, UCM handling on 5.4+)

## Package Structure

The installable package (`pkg_ars`) is defined in `pkg_ars.xml` at the repo root. The installation script is `script.ars.php`. Language files for the package itself are in the root `language/` directory.
