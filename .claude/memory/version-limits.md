# PHP / Joomla version limits

## Verify the advertised Joomla range against real Joomla trees

To verify ARS really supports the Joomla range in `composer.json` (`extra.akcompat.limit`), do not
reason from memory about which Joomla APIs exist. The repo already carries real Joomla trees to check
against:

- `tests/integration/inbox/Joomla_*-Stable-Full_Package.zip` — full packages for the floor and ceiling
  of the matrix (e.g. 5.4.7, 6.0.4, 6.1.2).
- `tests/integration/docker/www/` — an extracted Joomla site (whatever the last e2e run used).

Method: collect every `use Joomla\...;` from `component/ plugins/ modules/`, map each namespace to its
file (`Joomla\CMS\X` → `libraries/src/X.php`; `Joomla\<Pkg>\Y` →
`libraries/vendor/joomla/<kebab-pkg>/src/Y.php`), and assert the file exists in BOTH the floor and
ceiling trees. Then extract the public method lists from those same files in each tree and `comm` them
to get methods removed/added between the two versions, and grep ARS for the removed ones. Per-class
extraction misses inherited methods, so verify each apparent "removed" hit against the parent class
before believing it.

**Why:** ARS advertises a wide Joomla range; guessing at J6 removals produces false alarms (`CMSObject`
and `Toolbar::getInstance()` are still present in 6.1.2) and misses real ones.

**How to apply:** run this before shipping any change to the advertised range, alongside
`tests/integration/docker/run.sh --matrix`.

## Version limits are enforced at runtime by `Helper\VersionLimits`

Implemented 2026-09-04, mirroring commit `d3044879` in `akeebabackup` (also rolled out in
`admintools`, `ats`, `onthos`, `docimport`, `paddle`, `social-magick`).
`Akeeba\Component\ARS\Administrator\Helper\VersionLimits`
(`component/backend/src/Helper/VersionLimits.php`) declares min/max PHP (`8.1.0`–`<8.7`) and min/max
Joomla (`5.4.0`–`<6.3`) and is wired in two places:

- `Dispatcher::dispatch()` calls `VersionLimits::throwIfVersionsIncompatible()` — covers the backend,
  the frontend (its Dispatcher extends the backend one), and therefore the JSON:API controllers too (no
  separate API dispatcher exists).
- Every plugin/module `services/provider.php` (`arsdlid`, `arslatest`, `arslink`, `webservices/ars`,
  `modules/admin/arsgraph`, `modules/site/arsdownloads`) guards `register()` with
  `if (!class_exists(VersionLimits::class) || !VersionLimits::isCompatible()) return;` so
  plugins/modules silently fail to load rather than fatal on an incompatible site.

ARS has no Core/Pro split (`$supportsProCore = false`, `$proCoreConstant = 'AK_NO_CONSTANT'`).

**Why:** part of Akeeba's EU Cyber Resilience Act compliance — security risk assessments only hold
within the tested PHP/Joomla range, so it is enforced, not just documented.

**How to apply:** `composer.json`'s `extra.akcompat.locations` markers for
`$minPHPVersion`/`$maxPHPVersion`/`$minJoomlaVersion`/`$maxJoomlaVersion` point at `VersionLimits.php`,
not `Dispatcher.php` — keep them in sync when bumping ranges (and verify the new range as above).
`component/script.ars.php`'s own `$minimumPhp`/`$maximumPhp`/`$minimumJoomla`/`$maximumJoomla`
properties are separate (install-time, runs pre-autoloader per `AGENTS.md`) and still need updating
too. Unlike `akeebabackup`, ARS ships no in-repo `documentation/` guide, so there is no docs section
explaining the CLI/console/JSON-API symptoms of enforcement. Recompute the e2e `JOOMLA_MATRIX` pairs
(see `AGENTS.md`) whenever a floor moves.
