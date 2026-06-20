<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Module\Arsdownload\Site\Helper;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Site\Model\UpdateModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class ArsdownloadHelper
{
	/**
	 * Get the latest item for each of the configured update streams.
	 *
	 * @param   Registry         $params  The module parameters.
	 * @param   SiteApplication  $app     The site application.
	 *
	 * @return  object[]
	 *
	 * @since   7.4.4
	 */
	public function getItems(Registry $params, SiteApplication $app): array
	{
		if (!ComponentHelper::isEnabled('com_ars'))
		{
			return [];
		}

		$streams = $this->parseStreams($params->get('streams', ''));

		if (empty($streams))
		{
			return [];
		}

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

	/**
	 * Normalise the streams module parameter into an array of integer stream IDs.
	 *
	 * @param   mixed  $streams  The raw streams parameter (array, JSON, or comma-separated string).
	 *
	 * @return  int[]
	 *
	 * @since   7.4.4
	 */
	private function parseStreams($streams): array
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
