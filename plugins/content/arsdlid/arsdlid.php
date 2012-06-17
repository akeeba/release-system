<?php
/**
 * @package AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class plgContentArsdlid extends JPlugin
{

	public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
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
	
	private static function process($match)
	{
		$ret = '';
		
		$user = JFactory::getUser();
		if(!$user->guest) {
			$db = JFactory::getDBO();
			
			$query = $db->getQuery(true)
				->select('MD5(CONCAT('.$db->qn('id').','.$db->qn('username').','.$db->qn('password').')) AS '.$db->qn('dlid'))
				->from($db->qn('#__users'))
				->where($db->qn('id').' = '.$db->q($user->id));
			$db->setQuery($query, 0, 1);
			$ret = $db->loadResult();
		}
		
		return $ret;
	}
}
