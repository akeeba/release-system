> **Note**
>
> This product includes GeoLite2 data created by MaxMind, available from [MaxMind](http://www.maxmind.com).

**What is the GeoIP database, installing and updating it**

Logging the country of origin of a download in Akeeba Release System requires it to be able to find out the country and / or continent associated with the IP address of a visitor of your site. Naturally, IPs do not carry geographic information so we need an external database which has this kind of information.

Akeeba Release System requires you to install an optional plugin called "System - Akeeba GeoIP provider plugin". You can download it for free [from our site](https://www.akeebabackup.com/download/akgeoip.html). Please remember to enable it after you install it.

This plugin is using the third party MaxMind GeoLite2 database to match IPs to countries and continents. This list is not static, i.e. it is updated about once per month. Admin Tools can attempt to download its newest version by clicking the Update the GeoLite2 Country database button in the Control Panel page. However, if this is not possible (for reasons ranging from your host restrictions to permissions issues) you can do so manually.

You can download the latest version of [MaxMing GeoLite2 database](http://dev.maxmind.com/geoip/geoip2/geolite2/) in binary format, from [http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz](???). Extract the downloaded compressed file using gunzip on Linux, 7-Zip on Windows or BetterZIP on Mac OS X. It will result in a file named `GeoLite2-Country.mmdb.gz`. Upload it to your site's `plugins/system/akgeoip/db` directory overwriting the existing file.

> **Important**
>
> Capitalization matters! You have to upload the file as `GeoLite2-Country.mmdb.gz`, not `geolite2-country.mmdb.gz` or any other combination of lowercase / capital letters, otherwise IT WILL NOT WORK, AT ALL.

> **Tip**
>
> If you are a subscriber to MaxMind's more accurate (99.8% advertised accuracy), for-a-fee GeoIP Country database you can use that database instead of the free GeoLite2 database included in the component, using the same procedure.

Do note that download log records prior to installing the new version of the database will not be affected. Only download attempts logged after uploading the new database version will be affected by the new database version.
