# ARS security-audit triage knowledge

Collected from the full `audit-security` pass run 2026-09-14/15 (see git history for
`security.md`, now gitignored, for the complete finding-by-finding record). This file persists the
durable judgement calls so a future audit doesn't re-raise the same invalid findings, and so any
agent classifying a new finding applies the same standards consistently.

## Actors and trust model

ARS's ACL model is **category-scoped**: `core.create`/`core.edit`/`core.delete`/`core.edit.state` on
`com_ars.category.<id>` govern who may create/edit/delete/publish Releases, Items, Update Streams and
Automatic Item Descriptions within a given category. This is **not** a low-privilege role. A site
owner grants it to anyone they want to let publish software releases — e.g. "a translation team
manager can only publish translations in the category of their specific language team" (see
`ReleaseController::allowAdd()`'s own comment).

**Holding category-scoped create/edit rights is itself a privileged, trusted position**, on par with
any other backend content-manager role. The following are explicitly **out of scope for a code fix**
because the actor who controls the attacker-shaped input is already trusted by the site owner's own
authorization decision:

- Setting up a download **Item's `url`** (Link-type items) — the URL is fetched server-side (proxied
  download, or hashed on save) on behalf of every visitor who downloads the software. A malicious
  holder of category-scoped create/edit rights could already do far worse than SSRF with that access.
  (Ruled invalid at H2, M6.)
- Setting **`redirect_unauth`** (per-record) or **`no_access_url`** (global component config) as an
  open-redirect target. (Ruled invalid at L3, L4, L5, L10.)
- Setting the Environments **`xmltitle`** field, displayed only to other backend users in the same
  admin list — a privileged user "attacking" only themselves or their peers. (Ruled invalid at L6.)
- Setting a menu item's **`page_heading`** parameter, which requires Menu Manager rights — same
  "privileged user can only hack themselves" shape. (Ruled invalid at L9.)

**The pattern to recognise:** if the only way to plant a malicious value is through an account that
already holds category-scoped (or broader) create/edit/configuration rights, and the blast radius of
that value lands on a DIFFERENT, less-privileged party (an anonymous downloader, a different backend
user), that is usually still in scope (e.g. H1's path traversal, M8/M9's stored XSS were real bugs
despite requiring the same privilege level, because their blast radius crosses a trust boundary a site
owner did not sign up for when granting "publish releases in category X"). But if the blast radius
only ever lands on the **same privileged actor who set the value**, or on a downstream Joomla
update-checker that has no meaningful "access" concept at all (see Update Streams below), it is not a
vulnerability — just ask: *does exploiting this require convincing someone MORE trusted or LESS
trusted than the setter to act, or does the setter just hurt themselves/their peers?*

## Update-stream feeds must stay public

The update-stream/JSON:API feed endpoints (`UpdateController`, consumed by downstream Joomla sites'
own core updater) **must remain unauthenticated and publicly readable**. This is how Joomla's update
system works: an anonymous HTTP client with no ARS credentials has to be able to fetch the feed to
learn a new version exists. Access-restricting the feed would mean sites running access-restricted
software are never told updates exist — a correctness regression, not a hardening win.

The correct (and already-implemented) access control point is the **actual file download** —
`ItemController`'s `accessControlItem()`/`accessControlRelease()`/`accessControlCategory()` chain still
gates the file itself by the record's `access` level. Metadata disclosure in the feed (version,
changelog, checksums, security-flag) for access-restricted releases is an accepted, necessary
trade-off of the mechanism, not a gap. (Ruled invalid at M3.)

## Models must never perform authentication/authorisation

ARS models are medium-level abstractions over the persistence layer ONLY. Authentication/authorisation
is strictly the controller's job. This is a deliberate, explicit rejection of Joomla core's own design
— `AdminModel`/`ListModel` embedding ACL checks (`canDelete()`, `canEditState()`, batch-ACL checks in
`batchMove()`/`batchCopy()`) is considered a **design mistake in Joomla core**, not a pattern to extend.

- Do **not** propose "add a redundant ACL/access check inside a model method" as a fix. A model method
  relying entirely on its caller (the controller) having already run the access-control chain is
  correct architecture, not defense-in-depth debt — **unless** that method is actually reachable
  through a path that bypasses the controller's access-control chain (verify this before raising it).
  (Ruled invalid at L1 — `ItemModel`'s download methods rely entirely on `ItemController` having
  already run `accessControlItem()`/`accessControlRelease()`/`accessControlCategory()`.)
- The **narrow exception**: ARS's own models DO override ACL hooks Joomla's base classes themselves
  declare and force onto every subclass (`CategoryModel::canDelete()`/`canEditState()`,
  `ItemModel`/`ReleaseModel::assertSourceCategoryAccessForBatch()`, wired into `onBeforeBatch()`/
  `batch()`). That is a constrained response to a surface Joomla itself imposes on any `AdminModel`
  subclass — it is not a precedent for adding a NEW ACL check to a model method that doesn't already
  have Joomla-imposed ACL surface on it.

## Controls already confirmed in place (do not re-flag)

- **No SQL injection anywhere** — every filter value reaching a query is type-cast, bound via
  `QueryInterface::bind()`/`whereIn()`/`bindArray()` with a correct per-iteration variable (not a
  reference-aliased loop variable), or a hard-coded identifier. Confirmed by a dedicated audit pass.
- **No hardcoded secrets, no weak/custom cryptography.** Download ID tokens use CSPRNG
  (`hash('md5', random_bytes(64))` — legitimate preimage-resistance-only use of MD5 over unpredictable
  input, not a collision-resistance use) with a parameterised, bound equality lookup at verification
  time. No `eval()`, `unserialize()`, dynamic class instantiation from untrusted data, or object
  injection gadget anywhere.
- **Every PHP file carries the `_JEXEC` guard** (the one exception, `component/README.php`, is a
  plain-text README that happens to end in `.php`, self-protected by an unconditional `<?php die(); ?>`
  as its first line — a stronger guard than `_JEXEC`, not a weaker one).
- **No SQL-injection-shaped dynamic execution sinks** — `call_user_func*`/`new $var`/dynamic
  `include`/`require` calls all resolve against fixed, code-defined allow-lists, never attacker data.

## How to classify hardening vs. vulnerability

A finding belongs in the report as a real vulnerability only when BOTH of these hold:

1. **An attacker with a plausibly-granted role** (not necessarily Super User, but also not "the site
   owner maliciously attacking their own site") **can influence the input** that reaches the sink.
2. **The blast radius lands on a party who did not consent to that risk** by virtue of the privilege
   the site owner granted the attacker — a different backend role, an anonymous visitor, or (for the
   traversal/SSRF-shaped findings) the server/filesystem itself, rather than the attacker's own account
   or peers at the same or lower trust level.

When (1) holds but (2) doesn't (the setter can only ever hurt themselves, their peers, or a downstream
system that has no access concept to violate), disposition as **Invalid**, and say so explicitly with
the specific actor and blast-radius reasoning — don't just mark it invalid without the "why," since the
next audit needs the same reasoning to avoid re-raising it.

## Process notes

- **Every code fix in a remediation session must ship with a unit test**, using whichever seam fits:
  `RecordingDatabase`/`ScriptedRecordingDatabase` + Reflection for model/table logic, an anonymous class
  `use`-ing a trait for mixin logic, a `UnitTest/Structure/*Test.php` when the fix has no runtime seam
  (a build manifest URL, an `HTMLHelper` option flag), or a direct `include`+output-buffering render
  when the fix touches a plain procedural layout file with no `$this`/framework dependency. When a fix
  genuinely has no accessible seam (raw controller ACL wiring needing `ApiController`/dispatch
  machinery this project's unit stubs don't provide, or view-template escaping with no render harness),
  say so explicitly rather than force artificial coverage — that gap is intentional, E2E-suite
  territory per `tests/README.md`, not something to fake past.
