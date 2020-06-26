# 5.1.0

* `~` Rewritten frontend SEF router
* `#` [LOW] Frontend add-on download ID, redirect to non-SEF URL after editing an item

# 5.0.0

* `~` Restore the categories search filter
* `~` Normalize language strings
* `#` [MEDIUM] Cannot create or filter auto descriptions by BleedingEdge release 
* `#` [HIGH] Router would make the wrong document format decision if the URL contained a query parameter 
* `#` [HIGH] Regression: categories pagination broken 

# 5.0.0.b1

* `!`️ Rolling internal release. This is no longer a mass distributed extension.
* `+` PHP 7.3+ only. 
* `+` Joomla 4 compatible. 
* `+` Implement Joomla! 4 routing. ([gh-135](https://github.com/akeeba/release-system/issues/135))
* `+` Support for fully customised repository layout using an HTML override. 
* `+` Common PHP version warning scripts 
* `+` Show the release date in the Releases page 
* `+` Minify the XML update stream 
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
* `-` Remove subscription level support; use Joomla view access levels (as you should be doing since 2013 at the latest).
* `~` Removed jQuery dependence [gh-168]
* `~` Replaced jqPlot with Chart.js.
* `~` Upgrade deprecated Joomla API calls to Joomla 3.9.
* `~` Work towards future PHP 7.4 support.
* `#` [HIGH] Input filter initialization incompatible with Joomla 4
* `#` [HIGH] MySQL script error could lead to unusable backend interface
* `#` [HIGH] XML streams broken on hosts with short PHP tags enabled
* `#` [HIGH] INI update format always showed the wrong platforms
* `#` [HIGH] File picker in Item edit form was broken 
* `#` [HIGH] Fatal error from ARS Latest content plugin when unpublishing article category in the backend 
* `#` [MEDIUM] Missing default column values would cause MySQL errors creating Releases or Items programmatically
* `#` [LOW] Download stats sometimes appeared empty
* `#` [LOW] Unhandled exception page was incompatible with Joomla 4