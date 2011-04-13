<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once(dirname(__FILE__).DS.'default.php');

class ArsControllerBrowse extends ArsControllerDefault
{
	function  __construct($config = array()) {
		parent::__construct($config);
		
		if(!in_array( $this->viewLayout, array('normal','bleedingedge','repository') ))
		{
			$this->viewLayout = 'repository';
		}
		
		$this->registerDefaultTask('repository');
		$this->registerTask( 'display', 'repository' );
	}

	function repository()
	{
		// Get the page parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');

		// Push the page params to the model
		$model = $this->getThisModel();
		$model->setState( 'task',		$this->getTask() );
		$model->setState( 'grouping',	$params->get('grouping',	'normal') );
		$model->setState( 'orderby',	$params->get('orderby',		'order') );

		// Push URL parameters to the model
		$model->setState( 'start',		JRequest::getInt('start', 0) );

		// Get the item lists
		$model->itemList = $model->getCategories();

		$this->display(true);
	}
}