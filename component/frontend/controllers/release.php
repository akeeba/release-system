<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once(dirname(__FILE__).DS.'default.php');

class ArsControllerRelease extends ArsControllerDefault
{
	function  __construct($config = array()) {
		parent::__construct($config);
		$this->registerDefaultTask('release');
		$this->registerTask( 'display', 'release' );

		JRequest::setVar('layout',null);
	}

	function release()
	{
		$id = JRequest::getInt('id',null);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');

		// Push the page params to the model
		$model = $this->getThisModel();
		$model->setState( 'task',		$this->getTask() );
		$model->setState( 'grouping',	$params->get('grouping',		'normal') );
		$model->setState( 'orderby',	$params->get('orderby',			'order') );
		$model->setState( 'rel_orderby',$params->get('rel_orderby',		'order') );
		$model->setState( 'items_orderby',$params->get('items_orderby',	'order') );

		// Push URL parameters to the model
		$model->setState( 'start',		JRequest::getInt('start', 0) );

		// Get the item lists
		if(empty($id))
		{
			$id = $params->get('relid');
		}
		if($id > 0)
		{
			$release = $model->getRelease($id);
		}
		else
		{
			$release = null;
		}

		if(!is_null($release))
		{
			$bemodel = JModel::getInstance('Bleedingedge','ArsModel');
			$bemodel->checkFiles($release);

			$items = $model->getItems($id);
		}
		else
		{
			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN') );
		}

		$this->display(true);
	}
}