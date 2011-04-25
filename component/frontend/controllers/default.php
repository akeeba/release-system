<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerDefault extends JController
{
	protected $viewLayout = null;
	protected $viewType = null;
	protected $modelName = null;

	public function __construct($config = null)
	{
		parent::__construct($config);
		$document =& JFactory::getDocument();
		$this->viewType	= $document->getType();
		$this->viewLayout = JRequest::getCmd( 'layout', 'default' );
	}
	
	/**
	 * Cross-version compatibility
	 * @param $cachable bool Set to true to let the contents be cacheable
	 * @param $urlparams array An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 */
	function display($cachable = false, $urlparams = false)
	{
		// Set the layout
		$view = $this->getThisView();
		$model = $this->getThisModel();
		$view->setModel( $model, true );

		$viewLayout = $this->viewLayout;
		$view->setLayout($viewLayout);

		// Display the view
		$document =& JFactory::getDocument();
		$viewType	= $document->getType();

		if($viewType == 'feed')
		{
			// Extra data required for feeds
			if(method_exists($model, 'processFeedData')) {
				$model->processFeedData();
				$view->setLayout('feed');
			} else {
				JError::raiseError(500, 'Invalid format in request');
				return;
			}
		}
		
		// Turn off caching for registered users
		$user = JFactory::getUser();
		$guest = $user->guest;

		if ($guest && $cachable && $viewType != 'feed') {
			$option	= JRequest::getCmd('option');
			$cache =& JFactory::getCache($option, 'view');
			
			if (is_array($urlparams) && version_compare(JVERSION,'1.6.0','ge')) {
				$app = JFactory::getApplication();

				$registeredurlparams = $app->get('registeredurlparams');

				if (empty($registeredurlparams)) {
					$registeredurlparams = new stdClass();
				}

				foreach ($urlparams AS $key => $value)
				{
					// add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
					$registeredurlparams->$key = $value;
				}

				$app->set('registeredurlparams', $registeredurlparams);
			}
			
			$cache->get($view, 'display');
		} else {
			// Feed display
			$view->display();
		}
	}

	/**
	 * Fetches the default model for this MVC triad (Singleton)
	 */
	protected final function getThisModel()
	{
		static $model;

		if(!is_object($model)) {
			$prefix = $this->getName().'Model';
			$view = JRequest::getCmd('view','cpanel');
			if(empty($this->modelName)) {
				$modelName = ucfirst($view);
			} else {
				$modelName = ucfirst($this->modelName);
			}
			$model = $this->getModel($modelName, $prefix);
		}

		return $model;
	}

	/**
	 * Fetches the default view for this MVC triad (Singleton)
	 */
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
