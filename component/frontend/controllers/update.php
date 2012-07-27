<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerUpdate extends FOFController
{
	public function execute($task) {
		$document = JFactory::getDocument();
		$viewType	= $document->getType();
		$task = FOFInput::getCmd('task', '', $this->input);
		$layout = FOFInput::getCmd('layout', '', $this->input);
		$id = FOFInput::getInt('id', null, $this->input);

		// Check for menu items bearing layout instead of task
		if((empty($task) || ($task == 'read')) && !empty($layout)) {
			$task = $layout;
		}
		
		// Check for default task
		if(empty($task) || ($task == 'read')) {
			if($viewType == 'xml') {
				$task = 'all';
			} elseif( ($viewType == 'ini') && empty($id)) {
				return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
			} elseif($viewType == 'ini') {
				$task = 'ini';
			} else {
				$task = 'ini';
				$viewType = 'ini';
			}
		}
		
		switch($task) {
			case 'ini':
				$viewType = 'ini';
				break;
				
			default:
				$viewType = 'xml';
				break;
		}
		
		switch($viewType) {
			case 'xml':
				switch($task) {
					case 'all':
						$task = 'all';
						break;

					case 'category':
						$task = 'category';
						break;

					case 'stream':
						$task = 'stream';
						break;
				}
				break;

			case 'ini':
				$task = 'ini';
				break;
		}

		parent::execute($task);
	}

	public function all()
	{
		$this->display(true);
	}

	public function category()
	{
		$cat = FOFInput::getCmd('id', '', $this->input);
		if(empty($cat)) {
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$cat = $params->get('category', 'components');
		}
		if(empty($cat)) {
			return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
		}
		$model = $this->getThisModel();
		$x = $model->getCategoryItems($cat);
		$this->display(true);
	}

	public function stream()
	{
		$id = FOFInput::getInt('id', 0, $this->input);
		if($id == 0) {
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);			
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

		$this->display(true);
	}

	public function ini()
	{
		$id = FOFInput::getInt('id', 0, $this->input);
		if($id == 0) {
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);			
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

		$this->display(true);
	}
}