## Joomla and PHP Compatibility

We are developing, testing and using Akeeba Release System using the latest version of Joomla! and a popular and actively maintained branch of PHP 7. At the time of this writing this is:

* Joomla! 3.9
* PHP 7.2

Akeeba Release System should be compatible with:
* Joomla! 3.8, 3.9
* PHP 7.2, 7.3.

## Changelog

**Other changes**

* Protection of all component and plugin folders against direct web access
* Update for new FOF 3 behavior regarding backend and frontend caching
* Slightly less wonky backend downloads graph

**Bug fixes**

* [HIGH] Unpublished items or items in unpublished releases or categories could be downloaded
* [MEDIUM] Releases: filter by Maturity was broken for the "Beta" option
* [LOW] Unused common tables folder 
