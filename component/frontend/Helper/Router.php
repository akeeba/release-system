<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

defined('_JEXEC') or die;

use FOF40\Container\Container;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Uri\Uri as JUri;

class Router
{
	static function _(string $plainURL, $xhtml = true, $tls = JRoute::TLS_IGNORE, $absolute = false): string
	{
		$container = Container::getInstance('com_ars');
		$config    = $container->platform->getConfig();
		$addSuffix = $config->get('sef_suffix', 0) == 1;

		$url = JRoute::_($plainURL, $xhtml, $tls, $absolute);

		if ($addSuffix)
		{
			$uri    = new JURI($plainURL);
			$format = $uri->getVar('format', 'html');
			$format = strtolower($format);

			if (!in_array($format, ['html', 'raw', 'xml']))
			{
				// Save any query parameters
				if (strstr($url, '?'))
				{
					[$url, $qparams] = explode('?', $url, 2);

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
