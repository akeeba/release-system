<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Model\Items;
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\Update;
use FOF40\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die();

if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
if (!ComponentHelper::isEnabled('com_ars'))
{
	return;
}

$items = call_user_func(function (array $streamsArray): array {
	if (empty($streamsArray))
	{
		return [];
	}

	/** @var Update $model */
	/** @var Items $dlModel */
	$items        = [];
	$arsContainer = Container::getInstance('com_ars');
	$model        = $arsContainer->factory->model('Update');
	$dlModel      = $arsContainer->factory->model('Items');

	foreach ($streamsArray as $stream_id)
	{
		if (empty($stream_id))
		{
			continue;
		}

		$streamItems = $model->getItems($stream_id);

		if (empty($streamItems))
		{
			continue;
		}

		$i = array_shift($streamItems);

		// Is the user authorized to download this item?
		$iFull = $dlModel->find($i->item_id);

		if (!Filter::filterItem($iFull, false))
		{
			continue;
		}

		// Add this item
		$items[] = (object) [
			'id'         => $i->item_id,
			'release_id' => $i->release_id,
			'name'       => $i->name,
			'version'    => $i->version,
			'maturity'   => $i->maturity,
		];
	}

	return $items;
}, array_map(function ($x) {
	return (int) $x;
}, explode(',', $params->get('streams', ''))));

if (empty($items))
{
	return;
}

require ModuleHelper::getLayoutPath('mod_arsdownloads', $params->get('layout', 'default'));
