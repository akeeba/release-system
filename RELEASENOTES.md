## Joomla and PHP Compatibility

We are developing, testing and using Akeeba Release System using the latest version of Joomla! and a popular and actively maintained branch of PHP 7. At the time of this writing this is:
* Joomla! 3.8
* PHP 7.1

Akeeba Release System should be compatible with:
* Joomla! 3.4, 3.5, 3.6, 3.7, 3.8
* PHP 5.4, 5.5, 5.6, 7.0, 7.1, 7.2.

## Changelog

**New**

* Rewritten interface using our Akeeba Frontend Framework (FEF).
* Warn the user if either FOF or FEF is not installed.
* Warn the user about incompatible versions of PHP, use of eAccelerator on PHP 5.4 and use of HHVM.

**Bug fixes**

* List of files not rendered for Amazon S3 categories 
* Link Created by ARS Item Button is wrong (gh-129)
* Unpublishing a Download ID in the front-end redirects you to some other page, not the one you were in.
* Accessing the Update view without specifying a valid format (xml or ini) should result in the XML feed being returned, not an error page 
