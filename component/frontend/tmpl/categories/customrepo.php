<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

/** @var \Akeeba\Component\ARS\Site\View\Categories\HtmlView $this */

// Load the custom repo file
$customHTML = @file_get_contents($this->customHtmlFile);

if (!empty($customHTML))
{
	echo HTMLHelper::_('content.prepare', $customHTML);
}
else
{
	echo $this->loadAnyTemplate('categories/generic', true, [
		'section' => 'normal', 'title' => 'COM_ARS_CATEGORY_TYPE_NORMAL',
	]);
	echo $this->loadAnyTemplate('categories/generic', true, [
		'section' => 'bleedingedge', 'title' => 'COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE',
	]);
}
