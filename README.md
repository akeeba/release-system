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

## Build with docker compose

The included docker compose file allows you to build ARS without the hassle of installing the dependencies locally on your host. A zip file will be created in the _release_ folder which can be installed on your Joomla web site. To make a release, run the following command in the root folder of this project. 

`docker-compose run ars`

If you want to define the version of the package, run the following command:

`docker-compose run -e VERSION=7.0.5 ars`
