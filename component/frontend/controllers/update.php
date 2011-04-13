<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once(dirname(__FILE__).DS.'default.php');

class ArsControllerUpdate extends ArsControllerDefault
{
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
		$this->viewLayout = $this->_task;
	}

	public function all()
	{
		$this->display(true);
	}

	public function category()
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
		$this->display(true);
	}

	public function stream()
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
		$this->display(true);
	}

	public function ini()
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
		
		$this->display(true);
	}
}