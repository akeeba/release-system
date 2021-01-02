<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();
die(); ?>

The Common folder
================================================================================

This folder contains view templates and view template fragments which are used throughout the component. Generally
speaking, we have the following files here:

* browse.blade.php  A prototype for Browse views. Override its sections to customize when replacing default.form.xml.
* edit.blade.php    A prototype for Edit / Add views. Override its sections to customize when replacing form.form.xml.
* Entry*.php        Fields used in Edit / Add views, when something more complex than a simple INPUT is needed.
* Show*.php         Display fields for Browse views, when something more complex than an echo is needed.

If you want to do serious changes to the formatting of the component's backend you will need to override these files
using standard Joomla template overrides. The target folder for your overridden files is
administrator/templates/YOUR_TEMPLATE/html/com_ars/Common
