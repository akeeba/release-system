<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once(dirname(__FILE__).DS.'default.php');

class ArsControllerCategory extends ArsControllerDefault
{
	function  __construct($config = array()) {
		parent::__construct($config);
		$this->registerDefaultTask('category');
		$this->registerTask( 'display', 'category' );

		JRequest::setVar('layout',null);
	}

	function category()
	{
		$id = JRequest::getInt('id',null);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');

		// Push the page params to the model
		$model = $this->getThisModel();
		$model->setState( 'task',		$this->getTask() );
		$model->setState( 'grouping',	$params->get('grouping',	'normal') );
		$model->setState( 'orderby',	$params->get('orderby',		'order') );
		$model->setState( 'rel_orderby',$params->get('rel_orderby',	'order') );

		// Push URL parameters to the model
		$model->setState( 'start',		JRequest::getInt('start', 0) );

		// Get the item lists
		if(empty($id))
		{
			$id = $params->get('catid');
		}
		if($id > 0)
		{
			$category = $model->getCategory($id);
		}
		else
		{
			$category = null;
		}

		if(!is_null($category))
		{
			$bemodel = JModel::getInstance('Bleedingedge','ArsModel');
			$bemodel->scanCategory($category);
			$releases = $model->getReleases($id);
		}
		else
		{
			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN') );
		}

		$this->display(true);
	}
}