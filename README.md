# Akeeba Release System
![ARS Logo](build/logo/product-releasesystem.svg)

A download manager component for Joomla!, designed for the distribution of Akeeba software.

## Internal Project

This software is designed to primarily fit the needs of our business site, akeeba.com.

If you decide to use this software please keep the following in mind:

* We do not provide any support for this software whatsoever.
* We do not take feature requests for this software.
* There is absolutely no guarantee that any feature implemented today will be available in the future, including the
  next minor release.
* There is absolutely no guarantee that the component will continue to work the same or even exist.
* This project does NOT follow semantic versioning.
* We provide VERY irregular downloads.

## JSON:API

ARS exposes a Joomla JSON:API for its categories, releases, items, automatic item descriptions, Download ID labels,
environments and update streams. Enable the "Web Services - Akeeba Release System" plugin to register the routes under
`/api/index.php/v1/ars/…`.

Access is authenticated with a Joomla API token and authorised with that token's user's own permissions. Reading
requires `core.manage` on `com_ars`; creating, editing and deleting a record additionally require `core.create`,
`core.edit` or `core.delete` on the ARS category the record belongs to. Download ID labels are more restrictive still:
any authenticated user can see their own, seeing anybody else's requires `core.manage`, and writing to somebody else's
requires `core.admin`.

`assets/http/api.http` documents every endpoint — the available filters, the sortable columns, and a worked example of
each request. It is a PHPStorm / IntelliJ IDEA HTTP Client file, so you can run the requests straight from the IDE; copy
`assets/http/http-client.private.env.json-dist` to `http-client.private.env.json` and put your API token in it first.

## Prerequisites

Necessary folder structure for building packages

* **ars** This repository
* **buildfiles** [Akeeba Build Tools](https://github.com/akeeba/buildfiles)

From `ars/build` run `phing git -Dversion=5.999.999.b1` to build an installable package with the fake version number `5.999.999.b1`.