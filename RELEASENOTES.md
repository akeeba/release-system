## Joomla and PHP Compatibility

We are developing, testing and using Akeeba Release System using the latest version of Joomla! and a popular and actively maintained branch of PHP 7. At the time of this writing this is:
* Joomla! 3.6
* PHP 7.0

Akeeba Release System should be compatible with:
* Joomla! 3.4, 3.5, 3.6, 3.7
* PHP 5.4, 5.5, 5.6, 7.0, 7.1.

## Changelog

**Critical issues**

* Joomla! 3.7.0 has a broken System - Page Cache plugin leading to white pages and wrong redirections
* Joomla! 3.7.0 broke the JDate package, effectively ignoring timezones, causing grave errors in date / time calculations and display
* Joomla! 3.7 added a fixed width to specific button classes in the toolbar, breaking the page layout
