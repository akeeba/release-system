# AGENTS.md

Akeeba Release System (ARS) — a Joomla package extension for managing software releases and downloads.

## Security

Before any security audit, security review, or `audit-*` skill run — and before reporting any
finding from one — you MUST read `.claude/security-audit-triage.md` — the actors that are out
of scope, finding classes already ruled invalid, controls already in place, and how to
classify hardening versus vulnerabilities.

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

## Git: commit and tag outside the sandbox

Commits and tags are always signed, with a key held in 1Password. The 1Password signing agent is reached
over a local socket that agent sandboxes do not expose, so a sandboxed `git commit` or `git tag` **always**
fails (e.g. `error: 1Password: Could not connect to socket. Is the agent running?`).

Run every `git commit` and `git tag` **outside the sandbox from the first attempt** — in Claude Code with
`dangerouslyDisableSandbox: true`, in other harnesses with their equivalent unsandboxed / escalated
execution. Do not try the sandboxed form first, do not diagnose the failure, and never work around it
with `--no-gpg-sign`, `-c commit.gpgsign=false` or unsigned tags.

## Project memory

Project memory lives in `.claude/memory/`, committed with the code, so that it is shared across machines
and across agentic harnesses (Claude Code, Codex, Qwen Code, Kimi Code, Junie, …). Read the relevant file
**before** starting work that matches its trigger:

| Before you… | Read |
|---|---|
| Fix a finding during a security-audit remediation session | `.claude/memory/security-remediation.md` |
| Add, change or translate language strings, or add a language | `.claude/memory/translations.md` |
| Change the supported PHP/Joomla range, or verify ARS against a Joomla version | `.claude/memory/version-limits.md` |

### Recording new memories

This is the **default and only** place for project memory. Do not write memories for this project to a
harness's private memory store (such as Claude Code's auto-memory under `~/.claude/projects/`); write
them here instead:

- Add to the existing topic file when one fits; otherwise create a new kebab-case `.md` file named after
  the topic, and add a row for it to the table above with a concrete trigger.
- Plain Markdown, no frontmatter. State the rule, then **Why:** (the reason or incident behind it) and
  **How to apply:**. Link related files with relative Markdown links.
- Don't record what the code, Git history or an existing `AGENTS.md` already says — update that
  `AGENTS.md` instead when the rule belongs there. Remove or correct entries that turn out wrong.
- These files are committed: no secrets, credentials, customer data or personal details.
