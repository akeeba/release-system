<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

if(!class_exists('ARSElementBase')) {
	if(version_compare(JVERSION,'1.6.0','ge')) {
		class ARSElementBase extends JFormField {
			public function getInput() {}
		}		
	} else {
		class ARSElementBase extends JElement {}
	}
}

/**
 * Returns a list of defined module rendering styles
 */
class ARSElementStyles extends ARSElementBase
{
	var $_name = 'Styles';
	protected $type = 'Styles';
	
	public function getInput()
	{
		if(!class_exists('JHTMLSelect')) {
			$dummy = JHtml::_('select.genericlist',array( JHtml::_('select.option','foobar') ),'foobar');
		}
		
		$styles = $this->getStyles();
		$class = $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="inputbox"';
		
		// prepare an array for the options
		$groups = array();
		foreach ($styles as $template => $chromes)
		{
			$groups[] = JHTMLSelect::option('<OPTGROUP>', $template);
			foreach($chromes as $chrome) {
				$groups[] = JHTMLSelect::option($chrome, $chrome);
			}
			$groups[] = JHTMLSelect::option('</OPTGROUP>');
		}
		return JHTMLSelect::genericList($groups, $this->name, $class, 'value', 'text', $this->value, $this->id);		
	}
	
	public function fetchElement($name, $value, &$node, $control_name)
	{
		if(!class_exists('JHTMLSelect')) {
			$dummy = JHtml::_('select.genericlist',array( JHtml::_('select.option','foobar') ),'foobar');
		}
		
		$styles = $this->getStyles();
		$class = $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="inputbox"';
		
		// prepare an array for the options
		$groups = array();
		foreach ($styles as $template => $chromes)
		{
			$groups[] = JHTMLSelect::option('<OPTGROUP>', $template);
			foreach($chromes as $chrome) {
				$groups[] = JHTMLSelect::option($chrome, $chrome);
			}
			$groups[] = JHTMLSelect::option('</OPTGROUP>');
		}
		return JHTMLSelect::genericList($groups,$control_name.'['.$name.']', $class, 'value', 'text', $value, $control_name.$name);		
	}
		
	public function getStyles()
	{
		static $styles = null;
		
		if(empty($styles))
		{
			$styles = $this->getChromeList();
		}

		return $styles;
	}
	
	private function getChromeList()
	{
		$ret = array();
		
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		
		$path = JPATH_SITE.'/templates/';
		foreach(JFolder::folders($path) as $template)
		{
			$list = array();
			$chromes = $this->searchTemplate($template, $path);
			if($chromes) foreach($chromes as $chrome)
			{
				$list[] = $chrome;
			}
			if(!empty($list)) $ret[$template] = $list;
		}
		
		return $ret;
	}
	
	private function searchTemplate($template, $path)
	{
		if(!file_exists($path.$template.DS.'html'.DS.'modules.php')) return array();
		$fileData = JFile::read($path.$template.DS.'html'.DS.'modules.php', false, 0, filesize($path.$template.DS.'html'.DS.'modules.php'));
		
		preg_match_all("/function(.)modChrome_(.*?)\(/", $fileData, $matches);
	
		return $matches['2'];
	}
}

if(version_compare(JVERSION,'1.6.0','ge')) {
	class JFormFieldStyles extends ARSElementStyles {}
} else {
	class JElementStyles extends ARSElementStyles {}		
}