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

## Prerequisites

Necessary folder structure for building packages

* **ars** This repository
* **buildfiles** [Akeeba Build Tools](https://github.com/akeeba/buildfiles)

From `ars/build` run `phing git -Dversion=5.999.999.b1` to build an installable package with the fake version number `5.999.999.b1`.