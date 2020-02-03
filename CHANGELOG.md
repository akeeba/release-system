# 5.0.0

* `!`️ Rolling internal release. This is no longer a mass distributed extension.
* `+` PHP 7.3+ only. 
* `+` Joomla 4 (alpha 10) compatible. 
* `+` Implement Joomla! 4 routing. ([gh-135](https://github.com/akeeba/release-system/issues/135))
* `+` Support for fully customised repository layout using an HTML override. 
* `+` Common PHP version warning scripts 
* `+` Show the release date in the Releases page 
* `-` Remove country column from Logs
* `-` Remove unused INI parser helper class.
* `-` Remove Visual Groups feature.
* `-` Remove support for the old versions of SiteGround SuperCacher.
* `-` Remove support for Joomla extensions updater.
* `-` Remove support for Amazon S3.
* `-` Remove support for JED Remote XML (since JED never made it work anyway).
* `-` Remove built-in `mime.types` files.
* `-` Remove some useless graphs and stats.
* `-` Remove access rules from Categories.
* `-` Remove release descriptions (not shown since a while ago), kept release notes.
* `~` Removed jQuery dependence [gh-168]
* `~` Replaced jqPlot with Chart.js.
* `~` Upgrade deprecated Joomla API calls to Joomla 3.9.
* `~` Work towards future PHP 7.4 support.
* `#` [HIGH] XML streams broken on hosts with short PHP tags enabled
* `#` [HIGH] INI update format always showed the wrong platforms
* `#` [HIGH] File picker in Item edit form was broken 
* `#` [LOW] Download stats sometimes appeared empty