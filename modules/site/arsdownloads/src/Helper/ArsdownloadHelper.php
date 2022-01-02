<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Module\Arsdownload\Site\Helper;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Site\Model\UpdateModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class ArsdownloadHelper
{
	public static function getItems(Registry $params): array
	{
		if (!ComponentHelper::isEnabled('com_ars'))
		{
			return [];
		}

		$streams = self::parseStreams($params->get('streams', ''));

		if (empty($streams))
		{
			return [];
		}

		$app = Factory::getApplication();
		$app->bootComponent('com_ars');

		$app->getLanguage()->load('com_ars', JPATH_ADMINISTRATOR);

		$items = [];
		$model = new UpdateModel();

		foreach ($streams as $stream)
		{
			$thisItems = $model->getItems($stream);

			if (empty($thisItems))
			{
				continue;
			}

			$items[] = array_shift($thisItems);
		}

		return $items;
	}

	private static function parseStreams($streams): array
	{
		$test = $streams;

		if (!is_array($test))
		{
			$test = @json_decode($streams, true);
		}

		if (!is_array($test))
		{
			$test = explode(',', $streams);
		}

		if (is_array($test))
		{
			return ArrayHelper::toInteger($test);
		}

		return [];
	}
}