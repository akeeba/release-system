<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

defined('_JEXEC') or die();

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');

if (!JComponentHelper::isEnabled('com_ars'))
{
	return;
}

// This has the side-effect of initialising our auto-loader
\FOF30\Container\Container::getInstance('com_ars');

if (!class_exists('MydownloadsModel'))
{
	class MydownloadsModel
	{
		public function getItems($streams)
		{
			$arsContainer = \FOF30\Container\Container::getInstance('com_ars');

			$items = array();

			/** @var \Akeeba\ReleaseSystem\Site\Model\Update $model */
			$model = $arsContainer->factory->model('Update');
			/** @var \Akeeba\ReleaseSystem\Admin\Model\Items $dlModel */
			$dlModel = $arsContainer->factory->model('Items');

			foreach ($streams as $stream_id)
			{
				$streamItems = $model->getItems($stream_id);

				if (empty($streamItems))
				{
					continue;
				}

				$i = array_shift($streamItems);

				// Is the user authorized to download this item?
				$iFull = $dlModel->find($i->item_id);

				if (!\Akeeba\ReleaseSystem\Site\Helper\Filter::filterItem($iFull))
				{
					continue;
				}

				// Add this item
				$newItem = array(
					'id'         => $i->item_id,
					'release_id' => $i->release_id,
					'name'       => $i->name,
					'version'    => $i->version,
					'maturity'   => $i->maturity
				);

				$items[] = (object)$newItem;
			}

			return $items;
		}
	}
}

$streamsArray = array();
$streams = $params->get('streams', '');

if (empty($streams))
{
	return;
}

$temp = explode(',', $streams);

foreach ($temp as $item)
{
	$streamsArray[] = (int)$item;
}

$model = new MydownloadsModel;
$items = $model->getItems($streamsArray);

if (!empty($items))
{
	require JModuleHelper::getLayoutPath('mod_arsdownloads', $params->get('layout', 'default'));
}