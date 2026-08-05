# CLAUDE.md

Akeeba Release System (ARS) — a Joomla package extension for managing software releases and downloads.

## Gotchas

- **Categories are NOT `com_categories`.** ARS has its own `#__ars_categories` table. `ARSPseudoCategory` implements Joomla's `CategoryInterface` purely to bridge custom fields and tags — don't reach for the Joomla category API.
- **Joomla version-specific branches are deliberate**, e.g. `createQuery()` on 5.1+, UCM handling on 5.4+. Don't "simplify" them away.
- **Every schema change needs both dialects.** `component/backend/sql/` ships parallel MySQL and PostgreSQL files; changing one and not the other ships a broken install.
- **Two test suites, both PHPUnit 11, neither wired into `composer.json`** — they use whatever
  `phpunit` is on your `PATH`. `phpunit` runs the unit suite in `UnitTest/`; it needs nothing but PHP.
  `tests/integration/docker/run.sh` stands up a throwaway Dockerised Joomla site and runs the
  end-to-end suite against it over real HTTP. Read `tests/README.md` before adding to either. There
  is no Jest; `api.http` still holds the manual REST API requests.

## Conventions

- `defined('_JEXEC') || die;` guard in every PHP file — no exceptions.
- Namespaces do not mirror directory names: `backend/src/` → `Akeeba\Component\ARS\Administrator\`, `frontend/src/` → `...\Site\`, `api/src/` → `...\Api\`.
- Controllers and Models: singular name = edit form / item CRUD, plural name = list view / filtered list.
- FOF was removed; this is plain Joomla native MVC. Don't reintroduce a framework abstraction.

## Build

Phing, driven by a shared `buildfiles` repo that must be checked out as a sibling at `../buildfiles/`. See the `phing-build` skill for targets and configuration.
