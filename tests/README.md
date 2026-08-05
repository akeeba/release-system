# Akeeba Release System — test suites

ARS ships two suites, and the split between them is deliberate.

| Suite | Config | Location | Needs | Speed | Answers |
|---|---|---|---|---|---|
| **Unit** | `phpunit.xml` | `UnitTest/` | PHP + PHPUnit only | milliseconds | is this function correct in isolation? |
| **Integration (E2E)** | `phpunit-integration.xml` | `tests/integration/` | Docker | minutes to provision, seconds to run | can somebody download a file they have not paid for, and can a legitimate customer still get theirs? |

> The unit suite lives in `UnitTest/` at the **repository root**, not under `tests/`. `tests/` holds
> only the integration suite. This matches the layout of Akeeba Ticket System and Admin Tools.

**There is deliberately no middle tier.** A harness that boots Joomla's classes but not Joomla's
request lifecycle tests neither isolation nor interaction, while looking like it tests both.

Neither suite adds a PHPUnit dependency to `composer.json`. Both use whatever PHPUnit 11 is on your
`PATH`.

---

## 1. Unit suite (`UnitTest/`)

Fast, framework-independent tests. They boot **no** Joomla, touch **no** database, and — unlike the
equivalent suites in ATS and Admin Tools — need **no `composer install`**: ARS has no runtime
dependencies, so `UnitTest/bootstrap.php` registers its own PSR-4 autoloader and loads a small set
of Joomla symbol stubs. That is all.

```sh
phpunit                          # phpunit.xml is picked up automatically
phpunit --filter PlatformVersion
phpunit --testdox
phpunit UnitTest/Structure       # a directory
```

A successful run ends in `OK (…)` with **no `Deprecations:` line**.

### What it covers

- **Pure algorithms** — the update-stream platform-version compactor, the download-URL format
  mapping, `sizeFormat()`, the `{ars …}` tag parser in `plg_content_arslatest`, the module's stream
  parser, `reformatDownloadID()`.
- **Decision logic behind a seam** — the `fnmatch()` matching that picks an item's update stream and
  auto-description, the security-badge severity clamp, the `return`-URL guard, alias incrementing.
- **SQL construction** — list models are handed a recording database that captures the query they
  build, so the `ORDER BY` quoting and direction whitelist are assertable without a database.
- **Structure** — every PHP file carries its `_JEXEC` guard; every PostgreSQL schema-update file has
  a MySQL counterpart.

### The stubs

`UnitTest/Stubs/joomla-stubs.php` provides the minimum Joomla symbols needed to *load* ARS classes.
Read its header before adding to it: every declaration is guarded so a real Joomla always wins, no
stub carries behaviour a test could mistake for the real thing, and the two places where fidelity
matters (`StringHelper::increment()`, `ArrayHelper::toInteger()`) hold Joomla's own implementation
copied verbatim.

**A growing stub set is a signal.** If a class needs five new stubs to be testable, it usually wants
a seam — a small `protected` method a test subclass overrides — or it belongs in the E2E suite. ARS
has classes in exactly that position: `ItemModel::doDownload()` calls `header()`, `flush()` and
`$app->close()`, and nothing useful is learned by stubbing those.

`RecordingDatabase` and `ModernRecordingDatabase` are a pair. ARS branches on
`method_exists($db, 'createQuery')` because that method only exists from Joomla 5.1, and
`method_exists()` is answered by the class rather than the instance — so a query-building test that
runs against only one of them proves only the branch it happened to take. Use both, via a data
provider.

## 2. Integration suite (`tests/integration/`)

End-to-end against a real, disposable Joomla site over real HTTP: Apache in front of PHP-FPM, MySQL,
and Mailpit as a real SMTP sink.

```sh
tests/integration/docker/run.sh                    # provision, test, tear down
tests/integration/docker/run.sh --keep-containers  # leave it up to iterate
tests/integration/docker/run.sh --matrix           # every supported Joomla version
```

**Read [`tests/integration/README.md`](integration/README.md) before adding or changing tests.** It
is the authoritative reference for the harness, the fixture matrix, and the conventions that keep
these tests meaningful — in particular the difference between a test that asserts a request was
refused and one that merely cannot tell.

The suite must pass on **every version in the matrix**, not just one. ARS carries deliberate
Joomla-version branches (`createQuery()` on 5.1+, UCM handling on 5.4+; see the repository
`CLAUDE.md`), and a single-version run cannot see the branch it did not take.

---

## Conventions for new tests

- **Namespaces / layout**
  - Unit: `Akeeba\ARS\UnitTest\…` → `UnitTest/…`, mirroring the source tree, one `<Class>Test` per
    class.
  - Integration: `Akeeba\ARS\IntegrationTest\Tests\…` → `tests/integration/src/Tests/…`, extending
    `AbstractE2ETestCase`.
- `defined('_JEXEC') or die;` after the namespace declaration, like every other PHP file in this
  repository. No exceptions.
- PHPUnit 11 **attributes** (`#[CoversClass]`, `#[DataProvider]`, `#[Group]`), never annotations,
  and the standard ARS copyright header block.
- **Never call `ReflectionMethod::setAccessible()` unconditionally.** It has been a no-op since PHP
  8.1 and emits a deprecation on 8.5+. Guard it with the version check used throughout this
  repository.
- **Test names state the claim**, not the mechanics: `testSeverityZeroEmitsNoSecurityElement()`, not
  `testSecurityBadge3()`.
- **Confirm non-obvious expected output empirically** before hard-coding an assertion. Run the real
  code and look at what it returns. Reading the code and writing down what it ought to do is how
  wrong assertions get committed.
- **Assert documented, reasonable behaviour.** When current behaviour looks like a bug, surface it —
  a comment naming the suspicion, or `markTestSkipped()` with a diagnosis — rather than silently
  encoding the bug as "expected".
- **Check that a new test can fail.** For a regression test, the strongest form of this is to revert
  the fix and watch it go red.
