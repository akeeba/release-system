# ARS end-to-end tests

These tests drive a **real, disposable Joomla site over real HTTP**. The whole site — Apache,
PHP-FPM, MySQL and a Mailpit mail sink — is stood up in Docker, provisioned from nothing, tested,
and thrown away. You never configure a site by hand, and a run that fails or crashes mid-way never
leaves anything behind in an unknown state.

Why this exists: ARS's most important behaviour is a refusal. A release file behind a subscriber-only
access level, an unpublished release, a Download ID that belongs to somebody else — the thing worth
asserting is that the bytes did **not** come back. That cannot be tested from inside the process
that would be serving them.

## Requirements

* **Docker** with the Compose plugin.
* **PHP CLI** and **PHPUnit 11** on your `PATH` — the runner is a host-side process that talks to the
  site over its published ports.
* **Phing** on your `PATH`, to build the package (`phing git`). Skip with `--skip-build` if
  `release/` already holds one.
* `unzip`, `curl`, `gunzip` and `jq`, used to resolve, fetch and extract Joomla.

## Quick start

```
tests/integration/docker/run.sh
```

That one command scrubs, brings the stack up, installs Joomla, points its mailer at Mailpit, builds
and installs ARS, creates the release repository directory, provisions the fixtures, runs the suite,
and tears the stack down.

To iterate, keep the stack up and re-run PHPUnit directly — provisioning is the slow part, the tests
themselves take seconds:

```
tests/integration/docker/run.sh --keep-containers
phpunit -c phpunit-integration.xml
phpunit -c phpunit-integration.xml --filter DownloadAuthorisationTest
php tests/integration/provision.php      # put the fixtures back
tests/integration/docker/run.sh --down   # tear it all down
```

While the stack is up the site is on <http://localhost:8100> and Mailpit's web UI on
<http://localhost:8135>. Those ports were chosen to clear the Akeeba Ticket System harness
(8090/8091/33307/8125) and the Admin Tools harness (8080/8081/33306), so all three can be up at once.

Note `--skip-build`: `phing git` recompiles the SCSS and minifies the JavaScript, which writes into
the tracked `component/media/` tree. When you are iterating on tests rather than on ARS, skipping the
build keeps your working copy clean.

### The version matrix

```
tests/integration/docker/run.sh --matrix
```

Runs the suite once per version in `JOOMLA_MATRIX` — by default 5.4, 6.0 and 6.1. **This is not
optional thoroughness.** ARS carries deliberate Joomla-version branches — `createQuery()` from 5.1,
UCM handling from 5.4 — and the repository `CLAUDE.md` is explicit that they must not be simplified
away. A green single-version run says nothing about the branch it did not take.

ARS's installer script declares a Joomla 4.3.0 floor, and `run.sh` reads that floor out of
`component/script.ars.php` and refuses anything lower, so the two cannot drift apart. The default
matrix starts at 5.4 because that is what is realistically supported; `-j 4.4` still works if you
want it, but Joomla 4.4 does not support PHP 8.4, so set `PHP_VERSION=8.3` for such a run.

## `run.sh` options

| Option | Effect |
|---|---|
| `-j`, `--joomla=V` | Override `JOOMLA_VERSION` for this run (`6`, `6.1`, `6.1.2`) |
| `--matrix` | Run once per version in `JOOMLA_MATRIX` |
| `-f`, `--filter=NAME` | Passed through to PHPUnit |
| `--skip-build` | Don't run `phing git`; install the newest package in `release/` |
| `--no-tests` | Provision the site but don't run the suite (leaves it up) |
| `--keep-containers` | Leave the stack running afterwards |
| `--down` | Tear everything down and exit |

## Configuration

Everything is in `docker/env.dist`, which **is committed**. `run.sh` creates `docker/.env` from it on
first run; edit that for local overrides.

`tests/integration/config.php` is written by `run.sh` to match what it actually provisioned. It is
git-ignored, and `config.dist.php` carries the same defaults, so a fresh clone can run PHPUnit
without configuring anything.

Drop a Joomla full package into `inbox/` to pin an exact build and provision offline; `run.sh`
prefers a matching package there over downloading one.

## How it fits together

| Piece | What it does |
|---|---|
| `docker/run.sh` | The one-shot orchestrator described above |
| `docker/docker-compose.yml` | `db`, `php`, `web` (Apache), `mailpit` |
| `assets/e2e-provision.php` | Runs **inside** the container: builds the ACL matrix, categories, releases, items, update streams, Download IDs and the release files, then writes a JSON manifest |
| `assets/e2e-probe.php` | Runs inside the site: reports who a session belongs to and what it may do |
| `src/SiteProvisioner.php` | Host-side façade over the provisioner; hands fixtures to tests by name |
| `src/Engine/Surfer.php` | cURL client with a cookie jar, token extraction, and redirects captured rather than followed |
| `src/Engine/Database.php` | PDO access to the site's database, for seeding and for observing |
| `src/Engine/Mailpit.php` | Reads the mail the site actually sent, over Mailpit's REST API |
| `src/AbstractE2ETestCase.php` | Base class: logged-in surfers by role, and the refusal assertions |

Three things are worth knowing before you change any of it.

**The provisioner runs inside the container on purpose.** ARS categories carry an `asset_id` and hang
per-category `core.create` / `core.edit` rules off the Joomla asset tree; releases, items and Download
ID labels run non-trivial `onBeforeCheck()` logic that computes hashes, file sizes, aliases,
auto-descriptions and update-stream matches. Building any of that with hand-written SQL would mean
reimplementing ARS, and then debugging results that are wrong for reasons unrelated to ARS. ARS's own
Table classes already do it correctly, so the fixtures are what ARS actually stores.

**Both container-side scripts call `$app->createExtensionNamespaceMap()` explicitly.** Nothing in
`libraries/bootstrap.php` or `includes/framework.php` registers the extension namespaces — that
happens in `ExtensionNamespaceMapper`, which the application only calls from inside `execute()`, and
neither script executes the application. Without that line no extension class is loadable, and the
failure is silent and misleading: every extension file guards itself with `defined('_JEXEC') or die`,
so the file gets included, dies quietly, and PHP reports the class as simply not found.

**Category directories must exist before the category does.** `CategoryTable::onBeforeCheck()`
asserts `is_dir()` on the category's `directory`, so `run.sh` creates `arsrepo/` before provisioning
and the provisioner writes the release files before it stores the items that describe them.

## The fixtures

`SiteProvisioner` hands these out by name — `categoryId('restricted')`, `itemId('restrictedFile')`,
`dlid('subscriberRevoked')`. Every one exists because some assertion turns on it.

**Accounts** (all with the shared test password; none of them is a Super User):

| Role | Holds | Exists because |
|---|---|---|
| `manager` | `core.manage` + full CRUD on `com_ars`, back-end and API login | the privileged actor |
| `catManager` | `core.manage`; create+edit on the **public** category only; edit **but not create** on `restricted` | per-category authorisation, and the `save2copy` regression — an account that may edit but may not create |
| `subscriber` | the ARS Subscribers view level, no back-end privilege | the legitimate customer |
| `client` | nothing; explicitly **not** in the Subscribers view level | the negative control |
| `other` | as `client` | a second ordinary account, for Download ID ownership |

**View levels**: `ARS Subscribers` (managers, catManagers, subscribers — *not* clients) and
`ARS Secret` (Super Users only). The second one looks pointless until you notice that a Super User is
granted every view level, so any "this must be filtered out by access level" assertion made with one
proves nothing. `ARS Secret` is the level that no test account holds.

**Categories** span the access matrix: `public`, `restricted`, `restrictedLinked` (same access but
`show_unauth_links=1` with a `redirect_unauth` target), `secret`, `unpublished`, and `bleedingedge`.
**Releases** cover the maturities, an unpublished one, a subscriber-only one inside a *public*
category, and exactly one carrying a security severity. **Items** cover file and link types,
item-level access inside a public release, and an unpublished item.

**Download IDs**: `subscriber` (primary), `subscriberSecondary` (the `userid:dlid` form),
`subscriberRevoked` (unpublished — must not authenticate), `client` (valid, but its owner holds no
access level: authentication and authorisation are different things), and `other`.

`HarnessTest` asserts every one of those asymmetries against the site's own `authorise()` and
`getAuthorisedViewLevels()`, because a fixture that accidentally granted the wrong thing would turn
the tests depending on it green while proving nothing.

## Writing tests

Test classes go in `src/Tests/`, namespace `Akeeba\ARS\IntegrationTest\Tests`, extending
`AbstractE2ETestCase`.

```php
$client   = $this->loggedIn('client');
$response = $client->get($this->siteUrl([
	'view' => 'item', 'task' => 'download', 'format' => 'raw',
	'id'   => static::$fixtures->itemId('restrictedFile'),
]));

$this->assertRefused($client, $response, 'A non-subscriber downloaded a subscriber-only file.');
$this->assertBodyNotContains(
	static::$fixtures->file('restrictedFile')['sentinel'],
	$response,
	'The file contents came back anyway.'
);
```

Conventions, beyond the repo-wide ones in [`tests/README.md`](../README.md):

- **Assert the refusal AND the absence of its effect.** `assertRefused()` says the response was not a
  success. For a download, also assert the bytes are absent — every fixture file carries a sentinel
  string for exactly this. For anything that changes state, assert the change did not happen. A
  message can be reworded; a row, or a leaked file, cannot.
- **Pass the surfer to `assertRefused()`.** A bad anti-CSRF token does not throw — Joomla enqueues a
  message and redirects — and that message lives in *that* session. A fresh surfer would find an
  empty queue and read the refusal as success.
- **Make sure the test could fail.** Before trusting a new refusal test, check that the thing you are
  testing could have happened at all: assert that the *allowed* actor gets the file, in the same
  class, right next to the assertion that the disallowed one does not.
- **Prefer targeted cleanup to `resetFixtures()`.** A reset re-runs the whole provisioner and
  reallocates release, item and Download ID ids, so any manifest a concurrently-running process is
  holding goes stale. A test that creates rows should delete its own rows in `tearDown()`, matched by
  a distinctive title prefix.
- **Surface suspected bugs, don't encode them.** When current behaviour looks wrong, `markTestSkipped`
  with a diagnosis rather than asserting today's behaviour as correct, and raise it.

## A note on Mailpit

Joomla really delivers over SMTP to `smtp://mailpit:1025`, so everything from the mailer
configuration through PHPMailer and the SMTP dialogue is exercised for real, and the tests read the
result back over Mailpit's REST API. ARS itself sends very little mail; the sink is mostly there so
that user registration and password handling during provisioning do not attempt real delivery, and
so that a test *can* check mail if one ever needs to.
