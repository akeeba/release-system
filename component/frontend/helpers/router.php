<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

class AKRouter
{
	static $addsSuffix = null;
	
	static function _($plainURL)
	{
		if(is_null(self::$addsSuffix)) {
			self::doesItAddSuffix();
		}
		
		$url = JRoute::_($plainURL);
		
		if(self::$addsSuffix) {
			$uri = new JURI($plainURL);
			$params = $uri->getQuery(true);
			$format = $uri->getVar('format','html');
			$format = strtolower($format);
			
			if(!in_array($format,array('html','raw'))) {
				// Save any query parameters
				if(strstr($url,'?')) {
					list($url, $qparams) = explode('?', $url, 2);
					$qparams = '?'.$qparams;
				} else {
					$qparams = '';
				}
				// Remove the suffix
				$basename = basename($url);
				$extension = end(explode(".", $basename));
				$realbase = basename($url,'.'.$extension);
				$url = str_replace($basename, $realbase, $url).$qparams;
				// Add a format parameter
				$uri = new JURI($url);
				$uri->setVar('format',$format);
				$url = $uri->toString();
			}
		}
		
		return $url;
	}
	
	static function doesItAddSuffix()
	{
		$config = JFactory::getConfig();
		self::$addsSuffix = $config->getValue('config.sef_suffix',0) == 1;
	}
}