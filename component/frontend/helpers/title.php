<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsHelperTitle
{
	/**
	 * 
	 * @param JRegistry $params
	 * @param string $default
	 * 
	 * @return string The document title, for use in –let's say– RSS feeds.
	 */
	public static function setTitleAndMeta(&$params, $default = '')
	{
		$document	= JFactory::getDocument();
		$app		= JFactory::getApplication();
		$menus		= $app->getMenu();
		$menu		= $menus->getActive();
		$title 		= null;
		
		// Set the default value for page_heading
		if($menu) {
			$params->def('page_heading', $params->get('page_title', $menu->title));
		} else {
			$params->def('page_heading', JText::_($default));
		}
		
		// Set the document title
		$title = $params->get('page_title', '');
		$sitename = $app->getCfg('sitename');
		if($title == $sitename) {
			$title = JText::_($default);
		}
		
		if(empty($title)) {
			$title = $sitename;
		} elseif($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		} elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$document->setTitle($title);
		
		// Set meta
		if ($params->get('menu-meta_description')) {
			$document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('menu-meta_keywords')) {
			$document->setMetadata('keywords', $params->get('menu-meta_keywords'));
		}

		if ($params->get('robots')) {
			$document->setMetadata('robots', $params->get('robots'));
		}
		
		return $title;
	}
}