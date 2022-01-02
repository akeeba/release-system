<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Service\Html;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') || die;

class AkeebaReleaseSystem
{
	private static $dateFormat = null;

	public static function formatDate($date, $local = true, $dateFormat = null)
	{
		$date = new Date($date, 'GMT');

		if ($local)
		{
			$app  = Factory::getApplication();
			$user = $app->getIdentity();
			$zone = $user->getParam('timezone', $app->get('offset', 'UTC'));
			$tz   = new \DateTimeZone($zone);
			$date->setTimezone($tz);
		}

		$dateFormat = $dateFormat ?: self::getDateFormat();

		return $date->format($dateFormat, $local);
	}

	/**
	 * Processes the message, replacing placeholders with their values and running any
	 * plug-ins
	 *
	 * @param   string  $message  The message to process
	 * @param   string  $context  The context of the message to process
	 *
	 * @return  string The processed message
	 */
	public static function preProcessMessage(string $message, string $context = 'com_ars.message'): string
	{
		// Parse [SITE]
		$site_url = Uri::base();
		$message  = str_replace('[SITE]', $site_url, $message);

		// Run content plug-ins
		$message = HTMLHelper::_('content.prepare', $message, null, $context);

		// Return the value
		return $message;
	}

	public static function sizeFormat(int $filesize): string
	{
		if ($filesize > 1073741824)
		{
			return number_format($filesize / 1073741824, 2) . " Gb";
		}
		elseif ($filesize >= 1048576)
		{
			return number_format($filesize / 1048576, 2) . " Mb";
		}
		elseif ($filesize >= 1024)
		{
			return number_format($filesize / 1024, 2) . " Kb";
		}
		else
		{
			return $filesize . " bytes";
		}
	}

	public static function downloadId($userId = null): string
	{
		if (is_null($userId))
		{
			$userId = Factory::getApplication()->getIdentity()->id;
		}

		if (empty($userId))
		{
			return '';
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('dlid'))
			->from($db->quoteName('#__ars_dlidlabels'))
			->where($db->quoteName('user_id') . ' = :user_id')
			->where($db->quoteName('primary') . ' = 1')
			->where($db->quoteName('published') . ' = 1')
			->bind(':user_id', $userId);

		try
		{
			return $db->setQuery($query)->loadResult() ?: '';
		}
		catch (\Exception $e)
		{
			return '';
		}
	}

	private static function getDateFormat(): string
	{
		if (!is_null(self::$dateFormat))
		{
			return self::$dateFormat;
		}

		$cParams = ComponentHelper::getParams('com_ars');

		self::$dateFormat = $cParams->get('dateformat', Text::_('DATE_FORMAT_LC5') . ' T');

		return self::$dateFormat;
	}
}