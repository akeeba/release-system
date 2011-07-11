<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once(dirname(__FILE__).DS.'default.php');

class ArsControllerDownload extends ArsControllerDefault
{
	function  __construct($config = array()) {
		parent::__construct($config);
		$this->registerDefaultTask('download');
		$this->registerTask( 'display', 'download' );

		JRequest::setVar('layout',null);
	}

	function download()
	{
		$id = JRequest::getInt('id',null);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');

		// Get the model
		$model = $this->getThisModel();

		// Anti-leech protection (removed feature)
		/*
		$component =& JComponentHelper::getComponent( 'com_ars' );
		$params = ($component->params instanceof JRegistry) ? $component->params : new JParameter($component->params);
		$antileech = $params->get('antileech',1);
		if($antileech == 1)
		{
			$model->antiLeech();
		}
		*/

		// Get the log table
		$log = JTable::getInstance('Logs','Table');

		// Get the item lists
		if($id > 0)
		{
			$item = $model->getItem($id);
		}
		else
		{
			$item = null;
		}

		if(is_null($item))
		{
			$log->save(array('authorized' => 0));
			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN') );
		}

		$item->hit();
		$log->save(array('authorized' => 1));

		$model->doDownload();
		die();
	}
}