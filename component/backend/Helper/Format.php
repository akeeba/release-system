<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use JHtml;

defined('_JEXEC') or die;

abstract class Format
{
	/**
	 * Processes the message, replacing placeholders with their values and running any
	 * plug-ins
	 *
	 * @param string $message The message to process
	 * @param string $context The context of the message to process
	 *
	 * @return string The processed message
	 */
	public static function preProcessMessage($message, $context = 'com_ars.message')
	{
		// Parse [SITE]
		$site_url = \JUri::base();
		$message = str_replace('[SITE]', $site_url, $message);

		// Run content plug-ins
		$message = JHTML::_('content.prepare', $message, null, $context);

		// Return the value
		return $message;
	}

	public static function sizeFormat($filesize)
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
}
