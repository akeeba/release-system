<?php
/**
 * @package AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

class plgContentArsdlid extends JPlugin
{
	public function onPrepareContent( &$article, &$params, $limitstart = 0 )
	{
		// Check whether the plugin should process or not
		if ( JString::strpos( $article->text, 'downloadid' ) === false )
		{
			return true;
		}
		
		// Search for this tag in the content
		$regex = "#{[\s]*downloadid[\s]*}#s";
		
		$article->text = preg_replace_callback( $regex, array('self', 'process'), $article->text );
	}
	
	public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
	{
		return $this->onPrepareContent($article, $params, $limitstart);
	}
	
	private static function process($match)
	{
		$ret = '';
		
		$user = JFactory::getUser();
		if(!$user->guest) {
			$db = JFactory::getDBO();
			$query = 'SELECT md5(concat(`id`,`username`,`password`)) FROM `#__users` WHERE `id` = '.
				$db->Quote($user->id);
			$db->setQuery($query);
			$ret = $db->loadResult();
		}
		
		return $ret;
	}
}
