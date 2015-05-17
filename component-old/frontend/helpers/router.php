<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AKRouter
{
	static function _($plainURL)
	{
		$config = JFactory::getConfig();
		$addSuffix = $config->get('sef_suffix', 0) == 1;

		$url = JRoute::_($plainURL);

		if ($addSuffix)
		{
			$uri = new JURI($plainURL);
			$params = $uri->getQuery(true);
			$format = $uri->getVar('format', 'html');
			$format = strtolower($format);

			if (!in_array($format, array('html', 'raw')))
			{
				// Save any query parameters
				if (strstr($url, '?'))
				{
					list($url, $qparams) = explode('?', $url, 2);
					$qparams = '?' . $qparams;
				}
				else
				{
					$qparams = '';
				}
				// Remove the suffix
				$basename = basename($url);
				$exploded = explode(".", $basename);
				$extension = end($exploded);
				$realbase = basename($url, '.' . $extension);
				$url = str_replace($basename, $realbase, $url) . $qparams;
				// Add a format parameter
				$uri = new JURI($url);
				$uri->setVar('format', $format);
				$url = $uri->toString();
			}
		}

		return $url;
	}
}