# 4.1.1

**Bug fixes**

* Automatic Descriptions does not allow selection of multiple environments

**Other changes**

* Joomla! 3.9 backend Components menu item compatibility

# 4.1.0

**New**

* gh-141 Additional file hashes

**Other changes**

* gh-139 Sort selection lists
* Filter ARS logs by user_id (required for data export on our site)

**Bug fixes**

* Creating or editing a category would apply an unexpected filter in the Browse view

# 4.0.0

**New**

* Rewritten interface using our Akeeba Frontend Framework (FEF).
* Warn the user if either FOF or FEF is not installed.
* Warn the user about incompatible versions of PHP, use of eAccelerator on PHP 5.4 and use of HHVM.

**Bug fixes**

* List of files not rendered for Amazon S3 categories 
* Link Created by ARS Item Button is wrong (gh-129)
* Unpublishing a Download ID in the front-end redirects you to some other page, not the one you were in.
* Accessing the Update view without specifying a valid format (xml or ini) should result in the XML feed being returned, not an error page 

# 3.2.5

**Bug fixes**

* State bleedover from Categories to Latest view (gh-121 - Thanks @mbabker)
* Typo prevents uploads in subdirectories (gh-117)
* HTTPS option is not taken into account when creating signed URLs (gh-125)
* ARS Link editor button plugin didn't display an icon (gh-126)
* ARS Link editor button plugin didn't work with the default editory due to JavaScript API change (gh-126)

# 3.2.4

**Bug fixes**

* Workaround for Joomla! Bug 16147 (https://github.com/joomla/joomla-cms/issues/16147) - Cannot access component after installation when cache is enabled
* Workaround for Joomla! bug "Sometimes files are not copied on update"
* Plugins would throw an error if FOF 3 cannot be loaded.
* Visual groups were ignored in caching, see gh-114
* Error COM_ARS_UPDATESTREAM_ERR_JEDID_EMPTY, see gh-111 

# 3.2.3

**Critical issues**

* Joomla! 3.7.0 has a broken System - Page Cache plugin leading to white pages and wrong redirections
* Joomla! 3.7.0 broke the JDate package, effectively ignoring timezones, causing grave errors in date / time calculations and display
* Joomla! 3.7 added a fixed width to specific button classes in the toolbar, breaking the page layout

# 3.2.2

**Bug fixes**

* Latest releases returning wrong results. A better SQL query is now used.

# 3.2.1

**Removed features**

* Removed translations

**Other changes**

* Detect a failed upload and abort early

# 3.2.0

**Critical**

* User would remain logged in when using a Download ID under some circumstances

**Removed features**

* Remove Akeeba Strapper
* Remove update notifications and statistics collection

**Other changes**

* Changing the styling of categories, releases and items views

**Bug fixes**

* Latest release plugin yielded wrong results because the model was reading the ID from the request
* Notice in the Normal and BleedingEdge sub-views``
