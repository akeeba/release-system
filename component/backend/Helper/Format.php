<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use FOF40\Container\Container;
use FOF40\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

abstract class Format
{
	private static $dateFormat = null;

	/**
	 * Processes the message, replacing placeholders with their values and running any
	 * plug-ins
	 *
	 * @param   string  $message  The message to process
	 * @param   string  $context  The context of the message to process
	 *
	 * @return string The processed message
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

	public static function formatDate($date, $local = true)
	{
		$container = Container::getInstance('com_ars');
		$date      = new Date($date, 'GMT');

		if ($local)
		{
			$zone = $container->platform->getUser()->getParam(
				'timezone',
				$container->platform->getConfig()->get('offset', 'UTC')
			);
			$tz   = new \DateTimeZone($zone);
			$date->setTimezone($tz);
		}

		return $date->format(self::getDateFormat(), $local);
	}

	private static function getDateFormat()
	{
		if (!is_null(self::$dateFormat))
		{
			return self::$dateFormat;
		}

		$container = Container::getInstance('com_ars');

		self::$dateFormat = $container->params->get('dateformat', Text::_('DATE_FORMAT_LC6') . ' T');

		return self::$dateFormat;
	}
}
