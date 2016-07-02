<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

defined('_JEXEC') or die;

use JFactory;
use JRoute;
use JUri;

class Router
{
	static function _($plainURL)
	{
		$config = JFactory::getConfig();
		$addSuffix = $config->get('sef_suffix', 0) == 1;

		$url = JRoute::_($plainURL);

		if ($addSuffix)
		{
			$uri = new JURI($plainURL);
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