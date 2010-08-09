<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerBrowse extends JController
{
	function  __construct($config = array()) {
		parent::__construct($config);
		$this->registerDefaultTask('repository');
		$this->registerTask( 'display', 'repository' );
	}

	/**
	 * Required override because Joomla! keeps on fucking me
	 */
	function display($cachable=false)
	{
		// Set the layout
		$view = $this->getThisView();
		$model = $this->getThisModel();
		$view->setModel( $model, true );

		$viewLayout	= JRequest::getCmd( 'layout', 'default' );
		$view->setLayout($viewLayout);

		// Display the view
		$document =& JFactory::getDocument();
		$viewType	= $document->getType();

		if($viewType == 'feed')
		{
			// Extra data required for feeds
			$model->processFeedData();
			$view->setLayout('feed');
		}

		if ($cachable && $viewType != 'feed') {
			global $option;
			$cache =& JFactory::getCache($option, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}
	}

	function repository($cachable=false)
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

		$this->display($cachable);
	}

	private function getThisModel()
	{
		static $model;

		if(!is_object($model)) {
			$prefix = $this->getName().'Model';
			$view = JRequest::getCmd('view','browse');
			$modelName = ucfirst($view);
			$model = $this->getModel($modelName, $prefix);
		}

		return $model;
	}

	public final function getThisView()
	{
		static $view;

		if(!is_object($view)) {
			$prefix = $this->getName().'View';
			$view = JRequest::getCmd('view','cpanel');
			$viewName = ucfirst($view);
			$document =& JFactory::getDocument();
			$viewType	= $document->getType();

			$view = $this->getView($viewName, $viewType, $prefix, array( 'base_path'=>$this->_basePath));
		}

		return $view;
	}
}