<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

defined('_JEXEC') or die();

if (!defined('F0F_INCLUDED'))
{
	include_once JPATH_LIBRARIES . '/f0f/include.php';
	if (!defined('F0F_INCLUDED') || !class_exists('F0FForm', true))
	{
		JError::raiseError('500', 'Your Akeeba Release System installation is broken; please re-install. Alternatively, extract the installation archive and copy the fof directory inside your site\'s libraries directory.');
	}
}

// PHP version check
if (defined('PHP_VERSION'))
{
	$version = PHP_VERSION;
}
elseif (function_exists('phpversion'))
{
	$version = phpversion();
}
else
{
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}
// Old PHP version detected. EJECT! EJECT! EJECT!
if (!version_compare($version, '5.3.0', '>='))
{
	return;
}

if (!class_exists('MydownloadsModel'))
{
	class MydownloadsModel
	{
		public function getItems($streams)
		{
			$items = array();

			$model = F0FModel::getTmpInstance('Updates', 'ArsModel');
			$dlmodel = F0FModel::getTmpInstance('Downloads', 'ArsModel');

			foreach ($streams as $stream_id)
			{
				$model->getItems($stream_id);
				$temp = $model->items;
				if (empty($temp))
				{
					continue;
				}

				$i = array_shift($temp);

				// Is the user authorized to download this item?
				$iFull = $dlmodel->getItem($i->item_id);
				if (is_null($iFull))
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