<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.module.helper');

/**
 * Chameleon skinning for Joomla!
 */
class ArsHelperChameleon
{
	/**
	 * Returns a module object based on custom contents
	 * @param string $title The title to show
	 * @param string $contents The HTML inside the module
	 * @param array $params Extra parameters
	 */
	static public function getModule($title, $contents, $params = array())
	{
		if(version_compare(JVERSION,'1.6.0','ge')) {
			$jsonParams = json_encode($params);
		} else {
			$jsonParams = '';
			foreach($params as $k => $v)
			{
				$jsonParams .= "$k=$v\n";	
			}
		}
		
		$result = new StdClass;
		$result->id			= 0;
		$result->title		= $title;
		$result->module		= 'mod_custom';
		$result->position	= '';
		$result->content	= $contents;
		$result->showtitle	= 1;
		$result->control	= '';
		$result->params		= $jsonParams;
		$result->user		= 0;
		return $result;
	}
	
	/**
	 * Loads a layout file and renders it as a module
	 * @param string $title The title of the module
	 * @param string $basedir The base path holding the templates
	 * @param string $template The layout name (optional; do not include .php)
	 * @param array $params Any module parameters to pass (optional)
	 */
	static public function renderTemplate($title, $basedir, $template = 'default', $params = array())
	{
		// Get the template's contents
		@ob_start();
		@include $basedir.DS.$template.'.php';
		$contents = ob_get_clean();
		
		// Set up the rendering attributes
		$attribs = array();
		if(array_key_exists('style',$params)) {
			$attribs['style'] = $params['style'];
			unset($params['style']);
		} else {
			$attribs['style'] = 'rounded';
		}
		
		// Get the rendered module
		$module = self::getModule($title, $contents, $params);
		unset($contents);
		$rendered = JModuleHelper::renderModule($module, $attribs);
		unset($module);
		
		return $rendered;
	}
	
	/**
	 * Fetches the additional view parameters for a specific category of modules
	 * @param string $category The module category, i.e. 'category','release','item'
	 */
	static public function getParams($category = 'default', $bleeding_edge = false)
	{
		static $params = null;
		
		if(is_null($params))
		{
			jimport('joomla.application.component');
			$component = JComponentHelper::getComponent('com_ars');
			$params = ($component->params instanceof JRegistry) ? $component->params : new JParameter($component->params);
		}
		
		switch($category)
		{
			case 'category':
			default:
				$style = $params->getValue('categorystyle','rounded');
				$sfx = $params->getValue('categorysuffix','');
				break;
				
			case 'release':
				$style = $params->getValue('releasestyle','rounded');
				$sfx = $params->getValue('releasesuffix','');
				break;
				
			case 'item':
				$style = $params->getValue('itemstyle','rounded');
				$sfx = $params->getValue('itemsuffix','');
				break;
		}
		
		if($bleeding_edge) {
			$sfx2 = $params->getValue('besuffix','');
			if(!empty($sfx2)) {
				$sfx .= ' '.$sfx2;
			}
		}
		
		return array(
			'style'				=> $style,
			'moduleclass_sfx'	=> $sfx
		);
	}
	
	static public function getReadOn($title, $link)
	{
		static $params = null;
		
		if(is_null($params))
		{
			jimport('joomla.application.component');
			$component = JComponentHelper::getComponent('com_ars');
			$params = ($component->params instanceof JRegistry) ? $component->params : new JParameter($component->params);
		}
		
		$default_template = '<a class="readon" href="%s">%s</a>';
		$template = $params->getValue('readontemplate',$default_template);
		
		$template = str_replace('[[','\\<', $template);
		$template = str_replace(']]','\\>', $template);
		$template = str_replace('[','<', $template);
		$template = str_replace(']','>', $template);
		$template = str_replace('\\<','[', $template);
		$template = str_replace('\\>',']', $template);
		
		return sprintf($template, $link, $title);
	}
}