# 3.2.5

**Bug fixes**

* State bleedover from Categories to Latest view (gh-121 - Thanks @mbabker)

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
