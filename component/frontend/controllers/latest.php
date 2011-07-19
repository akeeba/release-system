<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once(dirname(__FILE__).DS.'default.php');

class ArsControllerLatest extends ArsControllerDefault
{
	function  __construct($config = array()) {
		parent::__construct($config);
		
		$this->modelName = 'browse';
		
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
		$model->setState( 'orderby',	'order' );
		$model->setState( 'maturity',	$params->get('min_maturity',	'alpha') );

		// Push URL parameters to the model
		$model->setState( 'start',		JRequest::getInt('start', 0) );

		// Get the item lists
		$model->itemList = $model->getCategories();
		$model->processLatest();

		$this->display(true);
	}
}