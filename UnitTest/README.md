# Unit tests

PHPUnit 11. Run from the repository root, so `phpunit.xml` and `UnitTest/bootstrap.php` are picked up
automatically:

```bash
phpunit                                          # the whole suite
phpunit --testdox                                # read it as a description of ARS's behaviour
phpunit UnitTest/Structure                       # a directory, recursively
phpunit UnitTest/Site/Model/ItemModelDownloadIdTest.php
phpunit --filter PlatformVersionCompactor        # by class name
phpunit --filter 'ItemTableTest::testAliasIsDerivedFromTheFileBasename'
```

A successful run ends in `OK (…)` and carries **no `Deprecations:` line**. Both are part of the bar.

**No `composer install` is needed.** ARS ships no runtime dependencies, so requiring one would be a
prerequisite that buys nothing. `bootstrap.php` defines `_JEXEC` and the `JPATH_*` constants,
registers its own PSR-4 autoloader for every ARS namespace, and loads the stubs. If a Composer
autoloader happens to be present it is loaded first, so a real class always beats a stub.

This is the fast suite: it boots no Joomla, opens no database connection and makes no HTTP request.
Anything that needs those belongs in [`tests/integration/`](../tests/integration/README.md).

## Layout

Mirrors the source tree, with the namespace roots ARS actually uses rather than its directory names:

| Directory | Namespace | Covers |
|---|---|---|
| `Administrator/` | `Akeeba\ARS\UnitTest\Administrator\` | `component/backend/src` |
| `Site/` | `…\Site\` | `component/frontend/src` |
| `Plugin/` | `…\Plugin\` | `plugins/` |
| `Module/` | `…\Module\` | `modules/` |
| `Structure/` | `…\Structure\` | repository-wide invariants |
| `Stubs/` | `…\Stubs\` | the test doubles below |

`Structure/` is worth knowing about: `JexecGuardTest` asserts every PHP file under `component/`,
`plugins/` and `modules/` carries its `defined('_JEXEC')` guard (the regression for commit
`25527ca1`, which fixed ten files that had lost it), and `SqlDialectParityTest` asserts every MySQL
schema-update file from 7.4.2 onwards has a PostgreSQL counterpart — the `CLAUDE.md` gotcha that
changing one dialect and not the other ships a broken install. Both are cheap and catch a class of
mistake no amount of behavioural testing will.

## The stubs

`Stubs/joomla-stubs.php` provides the minimum Joomla symbols needed to *load* ARS classes. ARS
classes extend `Table`, `ListModel` and `CMSPlugin` and `use` traits that in turn `use` Joomla
traits, all of which PHP resolves at class-definition time — so without these, a class cannot be
loaded at all, let alone tested.

Read the file's header before adding to it. Three rules keep it honest: every declaration is guarded
so a real Joomla wins; no stub carries behaviour a test could mistake for the real thing; and the two
places where fidelity genuinely matters (`StringHelper::increment()`, `ArrayHelper::toInteger()`)
hold Joomla's own implementation copied verbatim, with its provenance stated.

**A growing stub set is a signal.** If a class needs five new stubs to become testable, it usually
wants a seam — a small `protected` method a test subclass overrides — or it belongs in the E2E suite.
`ItemModel::doDownload()` is the clearest example of the latter: it calls `header()`, `flush()` and
`$app->close()`, and nothing true is learned by stubbing those.

**`RecordingDatabase` and `ModernRecordingDatabase` are a pair, and tests should use both.** They
capture the query a model builds instead of running it, so the `ORDER BY` quoting and the direction
whitelist are assertable with no database anywhere. The pair exists because ARS branches on
`method_exists($db, 'createQuery')` — that method only arrived in Joomla 5.1, and `method_exists()`
is answered by the class, not the instance. A query-building test that runs against only one of them
proves only the branch it happened to take. `ScriptedRecordingDatabase` adds per-table and per-call
scripted results, for the collision-retry loops in `DlidlabelTable` and `ModelCopyTrait`.

## Conventions

Beyond the repository-wide ones in [`tests/README.md`](../tests/README.md):

- **Never call `ReflectionMethod::setAccessible()` unconditionally.** It has been a no-op since PHP
  8.1 and emits a deprecation on 8.5+, which the host PHP here is. Guard it with the version check
  used throughout this repository.
- `phpunit.xml` sets `beStrictAboutChangesToGlobalState`, `beStrictAboutOutputDuringTests` and
  `beStrictAboutTestsThatDoNotTestAnything`. **Reset every static you touch in `tearDown()`** —
  `Factory::reset()`, `LayoutHelper::reset()`, `Text::$strings`, `ComponentHelper::$params`,
  `Uri::$rootUri` / `$baseUri`, and any static memo inside the class under test (reach those with
  Reflection). Produce no output from a test.
- Prefer `Text::_()` echoing the key back: assert the language **key**, which is stable, not the
  English wording, which is not.
- **Confirm non-obvious expected output empirically before hard-coding it.** Run the real method and
  look at what it returns. Several of this suite's assertions contradict what the code appears to do
  on a careful read — that is the point of checking.
- **Where behaviour looks like a bug, pin it with a comment naming the suspicion, or
  `markTestSkipped()` with a diagnosis.** Do not quietly assert today's behaviour as correct. Several
  tests here carry such comments, and they are how those bugs got found.
