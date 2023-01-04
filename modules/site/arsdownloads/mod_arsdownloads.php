<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var Registry $params */

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\Arsdownload\Site\Helper\ArsdownloadHelper;
use Joomla\Registry\Registry;

$items = ArsdownloadHelper::getItems($params);

if (empty($items))
{
	return;
}

require ModuleHelper::getLayoutPath('mod_arsdownloads', $params->get('layout', 'default'));