## Joomla and PHP Compatibility

We are developing, testing and using Akeeba Release System using the latest version of Joomla! and a popular and actively maintained branch of PHP 7. At the time of this writing this is:
* Joomla! 3.8
* PHP 7.2

Akeeba Release System should be compatible with:
* Joomla! 3.4, 3.5, 3.6, 3.7, 3.8, 3.9
* PHP 5.4, 5.5, 5.6, 7.0, 7.1, 7.2, 7.3.

## Changelog

**Other changes**

* Removing checksums from the update stream by default to work around Joomla 3.9 bugs related to extension updates.

**Bug fixes**

* [HIGH] Automatic item descriptions cannot save multiple Environments
* [HIGH] Update streams lack a "type" attribute for files hosted externally 
* [MEDIUM] Fixed JavaScript error on manual creation of a new Item
