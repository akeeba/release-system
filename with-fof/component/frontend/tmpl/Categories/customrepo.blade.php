<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * This view template is loaded automatically when the file /templates/YOUR_TEMPLATE/html/com_ars/Categories/repo.html
 * exists.
 *
 * In this case the contents of that file will be parsed as an article i.e. all content plugins will be run against it
 * and the result is displayed INSTEAD OF the repository.
 */

// Load the custom repo file
$customHTML = @file_get_contents($this->customHtmlFile);

?>
@unless(empty($customHTML))
    {{ Joomla\CMS\HTML\HTMLHelper::_('content.prepare', $customHTML) }}
@else
    @include('site:com_ars/Categories/generic', ['section' => 'normal', 'title' => 'COM_ARS_CATEGORY_TYPE_NORMAL'])
    @include('site:com_ars/Categories/generic', ['section' => 'bleedingedge', 'title' => 'COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE'])
@endunless