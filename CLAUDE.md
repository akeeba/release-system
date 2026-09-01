# CLAUDE.md

Akeeba Release System (ARS) — a Joomla package extension for managing software releases and downloads.

## Gotchas

- **Categories are NOT `com_categories`.** ARS has its own `#__ars_categories` table. `ARSPseudoCategory` implements Joomla's `CategoryInterface` purely to bridge custom fields and tags — don't reach for the Joomla category API.
- **Joomla version-specific branches are deliberate.** Don't "simplify" them away. The `createQuery()`
  branch (5.1+) now lives in exactly one place, `Administrator\Helper\DbQuery::create()`; build every
  query through it rather than calling `createQuery()`/`getQuery(true)` directly. The two copies in
  `component/script.ars.php` are intentional — that file runs before the autoloader exists.
- **Every schema change needs both dialects.** `component/backend/sql/` ships parallel MySQL and PostgreSQL files; changing one and not the other ships a broken install.
- **The e2e matrix pairs Joomla with PHP deliberately.** `JOOMLA_MATRIX` in
  `tests/integration/docker/env.dist` is a list of `JOOMLA:PHP[,PHP...]` pairs, not a cross product.
  The rule: for each supported Joomla version, run the **lowest** and the **highest** PHP that both
  ARS *and* that Joomla version permit, plus any **PHP major-version boundary** that falls inside
  that window. The edges are where incompatibilities live — a version in the middle of a range
  almost never breaks something the edges do not, and each pair costs a full site provision.
  Today: ARS allows `>=8.1 <8.7`; Joomla 5.4 requires PHP 8.1+, Joomla 6.x requires 8.3+; the newest
  published `php:*-fpm` image is 8.5. Hence `5.4:8.1,8.5 6.0:8.3,8.5 6.1:8.3,8.5`. As an
  illustration of the major-boundary clause: were Joomla 4.4 still supported, it would need
  7.4, 8.0 and 8.2 — the two edges *and* 8.0, because a PHP major jump breaks more than a minor one.
  Recompute the pairs whenever a floor moves or a new PHP is released; `run.sh` enforces both floors
  and refuses an impossible pair rather than failing obscurely.
- **Two test suites, both PHPUnit 11, neither wired into `composer.json`** — they use whatever
  `phpunit` is on your `PATH`. `phpunit` runs the unit suite in `UnitTest/`; it needs nothing but PHP.
  `tests/integration/docker/run.sh` stands up a throwaway Dockerised Joomla site and runs the
  end-to-end suite against it over real HTTP. Read `tests/README.md` before adding to either. There
  is no Jest. `assets/http/api.http` is the documented JSON:API request collection (PHPStorm HTTP Client);
  its `http-client.env.json` / `http-client.private.env.json` live next to it.

## JSON:API

The `plg_webservices_ars` plugin registers CRUD routes under `v1/ars/` for `categories`, `releases`, `items`,
`autodescriptions`, `dlidlabels`, `environments` and `updatestreams`. Each one needs three things: a
`component/api/src/Controller/<Plural>Controller.php`, a `component/api/src/View/<Plural>/JsonapiView.php`, and a
route registration in the plugin. There are no API-side models — the Administrator ones are reused, which is why
every controller has to re-apply authorisation itself (see `Controller/Mixin/AssertApiAccess.php`); the back-end
models deliberately do not filter by view level or ownership.

List filters are request parameters mapped onto model state by `Controller/Mixin/PopulateModelState.php`. Adding a
filter means adding it to the mapper *and* to the Administrator list model. Sorting (`list_ordering`,
`list_direction`) is validated by Joomla against the model's `filter_fields`, so a new sortable column has to be
declared there too. Document anything you add in `assets/http/api.http`.

## Conventions

- `defined('_JEXEC') || die;` guard in every PHP file — no exceptions.
- Namespaces do not mirror directory names: `backend/src/` → `Akeeba\Component\ARS\Administrator\`, `frontend/src/` → `...\Site\`, `api/src/` → `...\Api\`.
- Controllers and Models: singular name = edit form / item CRUD, plural name = list view / filtered list.
- FOF was removed; this is plain Joomla native MVC. Don't reintroduce a framework abstraction.

## Build

Phing, driven by a shared `buildfiles` repo that must be checked out as a sibling at `../buildfiles/`. See the `phing-build` skill for targets and configuration.
