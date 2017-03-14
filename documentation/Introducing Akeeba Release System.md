Akeeba Release System (or ARS for short) is a download manager component quite different than its competition. It is designed for developers who want to disseminate their software products to their clients. Even though it can be abused as a generic downloads manager we do not recommend this approach and will not provide any kind of support for this use case. Its key features are:

-   **The software used by Joomla! for its own releases**.

-   Full support for Amazon S3, including the ability to manage (upload, delete, create directories) your files stored on S3 from within the component

-   Bootstrap styling.

-   Sensible three-level organization of your downloads (package, version, files).

-   Out-of-the box SEF URLs, without requiring a SEF component.

-   Excellent performance

-   Integration with Akeeba Subscriptions and Joomla! access control (user groups & viewing access levels) for limiting access to downloads only to specific subscribers.

-   Allows you to add files either stored on any directory under your site's root -even a different one per package!- or directly link to externally hosted files without revealing their URL to your visitors.

-   Automatic determination of th file size and MD5 and SHA1 hashes of all files you add to it.

-   You can create subdirectories, upload, replace and delete files from the component itself.

-   Automatic item descriptions. Tired of typing the same descriptions over and over? Stop typing! ARS can do it for you.

-   Update streams. Offer updates to your software in two formats: INI files easily usable by any and all programming languages or automatic creation of **Joomla! XML extension update streams** and **JED Remote XML files**. As soon as you publish a new version, all your clients using a modern version of Joomla! will be able to automatically upgrade to it. Moreover, the Joomla! Extensions Directory will be notified of the new version and update its listing. No extra file creation or manual action is necessary. Coupled with [Akeeba Release Maker](https://github.com/akeeba/releasemaker) your entire extension publish process can be shortened to a single command.

-   Allows for automatic updates of restricted downloads. You can authenticate automatic downloads of restricted access items by passing the username and password or a secure Download ID to the URL. This is how we allow you to do 1-click live updates of the Professional versions of our software.

-   Release notes

-   All texts are edited using Joomla!'s WYSIWYG editor and support content plugins.

-   Latest releases overview for presenting your newest downloads on a single page.

-   Download logging. Even the referrer, IP address and country (using the free MaxMind GeoLite2 Country database) is logged. Know who downloaded when and what.

-   BleedingEdge repositories support. Just upload files to your server by FTP and ARS will automatically create new releases, download items and publish them, while refreshing their update stream. In other words, 0-click releases.

-   GPL v3 software. We value your Freedom of choice and transparency in our code.

> **Important**
>
> The software is provided free of charge for everyone to use. However, we do not offer any kind of support, free or paid.

> **Note**
>
> This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/.