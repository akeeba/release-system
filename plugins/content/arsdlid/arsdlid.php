<?php
/**
 * @package    AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright  Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class plgContentArsdlid extends JPlugin
{
	private static $cache = array();

	public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
	{
		// Check whether the plugin should process or not
		if (JString::strpos($article->text, 'downloadid') === false)
		{
			return true;
		}

		// Search for this tag in the content
		$regex = "#{[\s]*downloadid[\s]*}#s";

		$article->text = preg_replace_callback($regex, array('self', 'process'), $article->text);
	}

	private static function process($match)
	{
		$ret = '';
		$user = JFactory::getUser();

		if (!$user->guest)
		{
			if (!isset(self::$cache[$user->id]))
			{
				if (!class_exists('ArsHelperFilter'))
				{
					@include_once JPATH_SITE . '/components/com_ars/helpers/filter.php';
				}

				self::$cache[$user->id] = class_exists('ArsHelperFilter') ? ArsHelperFilter::myDownloadID() : '';
			}

			$ret = self::$cache[$user->id];
		}

		return $ret;
	}
}
