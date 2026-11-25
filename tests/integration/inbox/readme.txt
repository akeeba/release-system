Cache of Joomla full packages, so they are not downloaded again, e.g.

    Joomla_5.4.10-Stable-Full_Package.zip
    Joomla_6.0.4-Stable-Full_Package.zip
    Joomla_6.1.2-Stable-Full_Package.zip

It does NOT decide which version gets tested. For a partial JOOMLA_VERSION (e.g. "6" or "5.4") run.sh first looks up
the latest stable release on that branch, then uses the package here if it is already present, and downloads it if it
is not. If the lookup fails, the run fails.

The lookup is buildfiles' (docker/e2e/joomla/acquire.sh, using getcms). getcms keeps the release list for an hour, for
all projects; to test a release made in the last hour, ask for its exact version.

An exact version (run.sh --joomla=6.1.3) needs no lookup, so with its package here it also works offline.

Only this readme is committed to git.
