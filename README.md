# Akeeba Release System
![ARS Logo](build/logo/product-releasesystem.svg)

A download manager component for Joomla!, designed for the distribution of Akeeba software.

> [!IMPORTANT]
> Developing and maintaining world-class software is neither easy nor free. The development of this software is subsidised by sales of our commercial offerings. If you like this software and would like to see it maintained in the future, please consider [purchasing a subscription](https://www.akeeba.com/subscribe.html) to one of our commercial offerings. _Thank you!_

## Internal Project

This software is designed to primarily fit the needs of our business site, akeeba.com.

If you decide to use this software please keep in mind that making it work with our specific use case takes priority over anyone else's. As such, feature requests may be rejected if we think implementing them would be detrimental to our own use case. Also keep in mind that there's a small but not insignifficant chance of some feature going away when we consider it to no longer fit our use case.

Kindly note that we do not provide any support or documentation for this software, be it free or paid. We're all developers here – you and us both. We can figure it out by looking at the source code.

## Build instructions

Check out this repository and Akeeba Build Tools — Public Packager using the following directory names:

- `ars` This repository.
- `buildfiles` [Akeeba Build Tools — Public Packager](https://github.com/akeeba/buildfiles-public)
- `build.properties` A file created as per the instructions in `buildfiles/README.md`

Then:

```bash
cd ars
composer install
phing git
```

The generated package is under `ars/release`.

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
