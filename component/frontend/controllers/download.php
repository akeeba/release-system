<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerDownload extends JController
{
	function  __construct($config = array()) {
		parent::__construct($config);
		$this->registerDefaultTask('download');
		$this->registerTask( 'display', 'download' );

		JRequest::setVar('layout',null);
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
		if ($cachable && $viewType != 'feed') {
			global $option;
			$cache =& JFactory::getCache($option, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}
	}

	function download($cachable=false)
	{
		$id = JRequest::getInt('id',null);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');

		// Get the model
		$model = $this->getThisModel();

		// Anti-leech protection
		$component =& JComponentHelper::getComponent( 'com_ars' );
		$params = new JParameter($component->params);
		$antileech = $params->get('antileech',1);
		if($antileech == 1)
		{
			$model->antiLeech();
		}

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