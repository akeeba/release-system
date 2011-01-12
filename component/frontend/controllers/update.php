<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerUpdate extends JController
{
	var $viewType = null;
	
	function  __construct($config = array()) {
		parent::__construct($config);

		$document =& JFactory::getDocument();
		$viewType	= $document->getType();
		$task = JRequest::getCmd('task','');
		$layout = JRequest::getCmd('layout','');
		$id = JRequest::getInt('id',null);

		// Check for menu items bearing layout instead of task
		if(empty($task) && !empty($layout))
		{
			$task = $layout;
		}
		
		// Check for default task
		if(empty($task)) {
			if($viewType == 'xml') {
				$task = 'all';
			} elseif( ($viewType == 'ini') && empty($id)) {
				return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
			} elseif($viewType == 'ini') {
				$task = 'ini';
			} else {
				$task = 'ini';
				$viewType = 'ini';
				//return JError::raiseError(500, JText::_('ARS_ERR_INVALIDOP'));
			}
		}
		
		switch($task)
		{
			case 'ini':
				$viewType = 'ini';
				break;
				
			default:
				$viewType = 'xml';
				break;
		}
		
		$this->viewType = $viewType;

		switch($viewType)
		{
			case 'xml':
				switch($task)
				{
					case 'all':
						$this->_task = 'all';
						break;

					case 'category':
						$this->_task = 'category';
						break;

					case 'stream':
						$this->_task = 'stream';
						break;
				}
				break;

			case 'ini':
				$this->_task = 'ini';
				break;
		}

		JRequest::setVar('task', $this->_task);
		JRequest::setVar('layout', $this->_task);
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

		$view->setLayout( JRequest::getCmd('layout','default') );

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

	public function all($cacheable=false)
	{
		$this->display();
	}

	public function category($cacheable=false)
	{
		$cat = JRequest::getCmd('id','');
		if(empty($cat))
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params =& $app->getPageParameters('com_ars');
			$cat = $params->get('category', 'components');
		}
		if(empty($cat)) {
			return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
		}
		$model = $this->getThisModel();
		$model->getCategoryItems($cat);
		$this->display();
	}

	public function stream($cacheable=false)
	{
		$id = JRequest::getInt('id',0);
		if($id == 0)
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params =& $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);			
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$this->display();
	}

	public function ini($cacheable=false)
	{
		$id = JRequest::getInt('id',0);
		if($id == 0)
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params =& $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);			
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$this->display();
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
			$viewType	= $this->viewType;

			$basePath = version_compare(JVERSION,'1.6.0','ge') ? $this->basePath : $this->_basePath;
			$view = $this->getView($viewName, $viewType, $prefix, array( 'base_path'=>$basePath));
		}

		return $view;
	}
}