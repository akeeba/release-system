<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Service\Html;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') || die;

class AkeebaReleaseSystem
{
	private static $dateFormat = null;

	public static function formatDate($date, $local = true, $dateFormat = null)
	{
		$date = clone Factory::getDate($date, 'GMT');

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

	/**
	 * Renders a badge for the security severity of a release.
	 *
	 * Severity levels follow the Joomla 6.2 update XML `<security>` element: 0 none, 1 low, 2 medium, 3 high,
	 * 4 critical. Level 0 renders nothing at all — an ordinary release must not be decorated with a
	 * "Security: None" badge.
	 *
	 * The markup comes from the akeeba.ars.common.securitybadge layout, so it can be overridden in the site or
	 * administrator template as html/layouts/com_ars/akeeba/ars/common/securitybadge.php.
	 *
	 * @param   int|null  $level       The security severity level, 0 to 4.
	 * @param   string    $extraClass  Extra CSS classes to add to the badge element.
	 *
	 * @return  string  The badge HTML, or an empty string for severity 0.
	 * @since   7.5.0
	 */
	public static function securityBadge(?int $level, string $extraClass = ''): string
	{
		$level = max(0, min((int) ($level ?? 0), 4));

		if ($level === 0)
		{
			return '';
		}

		/**
		 * The layout ships in the site part of the component and is deliberately rendered with client 0 from
		 * both applications. This keeps a single canonical copy, while the template override path is resolved
		 * against whichever application is rendering — so the badge can be restyled from the site template and
		 * from the administrator template alike.
		 */
		return LayoutHelper::render(
			'akeeba.ars.common.securitybadge',
			[
				'level'      => $level,
				'extraClass' => $extraClass,
			],
			'',
			[
				'component' => 'com_ars',
				'client'    => 0,
			]
		);
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
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
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