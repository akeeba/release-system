<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use JText;

abstract class Title
{
	/**
	 * Sets up the page title
	 *
	 * @param   \JRegistry  $params
	 * @param   string      $default
	 *
	 * @return  string  The document title, for use e.g. in RSS feeds.
	 */
	public static function setTitleAndMeta(&$params, $default = '')
	{
		$container = Container::getInstance('com_ars');
		$document  = $container->platform->getDocument();
		/** @var \JApplicationSite $app */
		$app   = \JFactory::getApplication();
		$menus = $app->getMenu();
		$menu  = $menus->getActive();
		$title = null;

		// Set the default value for page_heading
		if ($menu)
		{
			$params->def('page_heading', $params->get('page_title', $menu->title));
		}
		else
		{
			$params->def('page_heading', JText::_($default));
		}

		// Set the document title
		$title    = $params->get('page_title', '');
		$sitename = $app->get('sitename');

		if ($title == $sitename)
		{
			$title = JText::_($default);
		}

		if (empty($title))
		{
			$title = $sitename;
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		$document->setTitle($title);

		// Set meta
		if ($params->get('menu-meta_description'))
		{
			$document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $params->get('menu-meta_keywords'));
		}

		if ($params->get('robots'))
		{
			$document->setMetadata('robots', $params->get('robots'));
		}

		return $title;
	}
}
