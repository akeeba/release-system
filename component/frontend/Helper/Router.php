<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

defined('_JEXEC') or die;

use JRoute;
use JUri;

class Router
{
	static function _($plainURL)
	{
		$container = \FOF30\Container\Container::getInstance('com_ars');
		$config    = $container->platform->getConfig();
		$addSuffix = $config->get('sef_suffix', 0) == 1;

		$url = JRoute::_($plainURL);

		if ($addSuffix)
		{
			$uri    = new JURI($plainURL);
			$format = $uri->getVar('format', 'html');
			$format = strtolower($format);

			if (!in_array($format, ['html', 'raw']))
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
				$basename  = basename($url);
				$exploded  = explode(".", $basename);
				$extension = end($exploded);
				$realbase  = basename($url, '.' . $extension);
				$url       = str_replace($basename, $realbase, $url) . $qparams;

				// Add a format parameter
				$uri = new JURI($url);
				$uri->setVar('format', $format);
				$url = $uri->toString();
			}
		}

		return $url;
	}
}
