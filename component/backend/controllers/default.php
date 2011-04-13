<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerDefault extends JController
{
	var $isJoomla16 = false;
	
	public function __construct($config = array())
	{
		parent::__construct($config);
		if(version_compare(JVERSION,'1.6.0','ge')) {
			$this->isJoomla16 = true;
		}
	} 
	
	public function display($cachable = false)
	{
		$document =& JFactory::getDocument();
		$viewType	= $document->getType();
		$viewLayout	= JRequest::getCmd( 'layout', 'default' );

		$view = $this->getThisView();

		// Get/Create the model
		if ($model = $this->getThisModel()) {
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		// Set the layout
		$view->setLayout($viewLayout);

		// Display the view
		if ($cachable && $viewType != 'feed') {
			global $option;
			$cache =& JFactory::getCache($option, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}
	}

	public function add($cachable = false)
	{
		// Load and reset the model
		$model = $this->getThisModel();
		$model->reset();

		// Set the layout to form, if it's not set in the URL
		$layout = JRequest::getCmd( 'layout', null );
		if( empty($layout) )
		{
			JRequest::setVar('layout','form');
		}

		// Display
		$this->display($cachable);
	}

	public function edit($cachable = false)
	{
		// Load the model
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$status = $model->checkout();

		if(!$status) {
			// Redirect on error
			$option = JRequest::getCmd('option');
			$view = JRequest::getCmd('view');
			$url = 'index.php?option='.$option.'&view='.$view;
			$this->setRedirect($url, $model->getError(), 'error');
			$this->redirect();
			return;
		}


		// Set the layout to form, if it's not set in the URL
		$layout = JRequest::getCmd( 'layout', null );
		if( empty($layout) )
		{
			JRequest::setVar('layout','form');
		}

		// Display
		$this->display($cachable);
	}

	public function apply()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$this->applySave();

		// Redirect to the edit task
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$id = $model->getId();
		$textkey = 'LBL_'.strtoupper($view).'_SAVED';
		$url = 'index.php?option='.$option.'&view='.$view.'&task=edit&id='.$id;
		$this->setRedirect($url, JText::_($textkey));
		$this->redirect();
	}

	public function save()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->applySave();

		// Redirect to the display task
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$textkey = 'LBL_'.strtoupper($view).'_SAVED';
		$url = 'index.php?option='.$option.'&view='.$view;
		$this->setRedirect($url, JText::_($textkey));
		$this->redirect();
	}

	public function savenew()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->applySave();

		// Redirect to the display task
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$textkey = 'LBL_'.strtoupper($view).'_SAVED';
		$url = 'index.php?option='.$option.'&view='.$view.'&task=add';
		$this->setRedirect($url, JText::_($textkey));
		$this->redirect();
	}

	public function cancel()
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$model->checkin();

		// Redirect to the display task
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		$this->setRedirect($url);
		$this->redirect();
	}

	public function accesspublic()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setaccess(0);
	}

	public function accessregistered()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setaccess(1);
	}

	public function accessspecial()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setaccess(2);
	}

	public function publish()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setstate(1);
	}

	public function unpublish()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setstate(0);
	}

	public function saveorder()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$ids = $model->getIds();
		$post = JRequest::get('post');
		$orders = $post['order'];

		if($n = count($ids))
		{
			for($i = 0; $i < $n; $i++)
			{
				$model->setId( $ids[$i] );
				$neworder = (int)$orders[$i];

				$item = $model->getItem();
				$key = $item->getKeyName();
				if($item->$key == $ids[$i])
				{
					$item->ordering = $neworder;
					$model->save($item);
				}
			}
		}

		$model->reorder();

		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		$this->setRedirect($url);
		$this->redirect();
		return;
	}

	public function orderdown()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$status = $model->move(1);
		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
	}

	public function orderup()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$status = $model->move(-1);
		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
	}

	public function remove()
	{
		// CSRF prevention
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST')) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$status = $model->delete();

		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
		return;
	}

	protected final function setstate($state = 0)
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$status = $model->publish($state);

		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
		return;
	}

	protected final function setaccess($level = 0)
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$id = $model->getId();

		$item = $model->getItem();
		$key = $item->getKeyName();
		$loadedid = $item->$key;

		if($id == $loadedid)
		{
			$item->access = $level;
			$status = $model->save($item);
		}
		else
		{
			$status = false;
		}


		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
		return;
	}

	protected final function applySave()
	{
		// Load the model
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$id = $model->getId();

		$data = JRequest::get('POST',4);
		$status = $model->save($data);

		if($status && ($id == 0)) {
			// Update the internally used ID if we saved a new record
			$saved = $model->getSavedTable();
			$key = $saved->getKeyName();
			$id = $saved->$key;
			JRequest::setVar('id', $id);
			$model->setId($id);
		} elseif($status) {
			// Try to check-in the record if it's not a new one
			$status = $model->checkin();
		}

		if(!$status) {
			// Redirect on error
			// save the posted data
			$session = JFactory::getSession();
			$session->set($model->getHash().'savedata', serialize($data) );
			// redirect
			$option = JRequest::getCmd('option');
			$view = JRequest::getCmd('view');
			$id = $model->getId();
			$url = 'index.php?option='.$option.'&view='.$view.'&task=edit&id='.$id;
			$this->setRedirect($url, $model->getError(), 'error');
			$this->redirect();
			return;
		} else {
			$session = JFactory::getSession();
			$session->set($model->getHash().'savedata', null );
		}
	}

	/**
	 * Returns the default model associated with the current view
	 * @return ArsModelBase The global instance of the model (singleton)
	 */
	public final function getThisModel()
	{
		static $prefix = null;
		static $modelName = null;

		if(empty($modelName)) {
			$prefix = $this->getName().'Model';
			$view = JRequest::getCmd('view','cpanel');
			$modelName = ucfirst($view);
		}

		return $this->getModel($modelName, $prefix);
	}

	/**
	 * Returns current view object
	 * @return JView The global instance of the view object (singleton)
	 */
	public final function getThisView()
	{
		static $prefix = null;
		static $viewName = null;
		static $viewType = null;

		if(empty($modelName)) {
			$prefix = $this->getName().'View';
			$view = JRequest::getCmd('view','cpanel');
			$viewName = ucfirst($view);
			$document =& JFactory::getDocument();
			$viewType	= $document->getType();
		}

		$basePath = (!$this->isJoomla16) ? $this->_basePath : $this->basePath;
		return $this->getView($viewName, $viewType, $prefix, array( 'base_path'=>$basePath));
	}
}