## Joomla and PHP Compatibility

We are developing, testing and using Akeeba Release System using the latest version of Joomla! and a popular and actively maintained branch of PHP 7. At the time of this writing this is:
* Joomla! 3.8
* PHP 7.1

Akeeba Release System should be compatible with:
* Joomla! 3.4, 3.5, 3.6, 3.7, 3.8
* PHP 5.4, 5.5, 5.6, 7.0, 7.1, 7.2.

## Language files

Akeeba Release System comes with English (Great Britain) language built-in. Installation packages for other languages are available [on our language download page](https://cdn.akeebabackup.com/language/ars/index.html).

## Changelog

**Bug fixes**

* State bleedover from Categories to Latest view (gh-121 - Thanks @mbabker)
* Typo prevents uploads in subdirectories (gh-117)
* HTTPS option is not taken into account when creating signed URLs (gh-125)
* ARS Link editor button plugin didn't display an icon (gh-126)
* ARS Link editor button plugin didn't work with the default editory due to JavaScript API change (gh-126)
