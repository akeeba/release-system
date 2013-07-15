<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerUpdate extends FOFController
{
	public function execute($task) {
		$document = JFactory::getDocument();
		$viewType	= $document->getType();
		$task = $this->input->getCmd('task', '');
		$layout = $this->input->getCmd('layout', '');
		$id = $this->input->getInt('id', null);

		// Check for menu items bearing layout instead of task
		if((empty($task) || ($task == 'read') || ($task == 'add')) && !empty($layout)) {
			$task = $layout;
		}

		// Check for default task
		if(empty($task) || ($task == 'read') || ($task == 'add')) {
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
		} elseif ($task == 'ini') {
			$viewType = 'ini';
		} else {
			$viewType = 'xml';
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
		$registeredURLParams = array(
			'option'		=> 'CMD',
			'view'			=> 'CMD',
			'task'			=> 'CMD',
			'format'		=> 'CMD',
			'layout'		=> 'CMD',
			'id'			=> 'INT',
			'dlid'			=> 'STRING',
		);
		$this->display(true, $registeredURLParams);
	}

	public function category()
	{
		$cat = $this->input->getCmd('id', '');
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

		$registeredURLParams = array(
			'option'		=> 'CMD',
			'view'			=> 'CMD',
			'task'			=> 'CMD',
			'format'		=> 'CMD',
			'layout'		=> 'CMD',
			'id'			=> 'INT',
			'dlid'			=> 'STRING',
		);
		$this->display(true, $registeredURLParams);
	}

	public function stream()
	{
		$id = $this->input->getInt('id', 0);
		if($id == 0) {
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

		$registeredURLParams = array(
			'option'		=> 'CMD',
			'view'			=> 'CMD',
			'task'			=> 'CMD',
			'format'		=> 'CMD',
			'layout'		=> 'CMD',
			'id'			=> 'INT',
			'dlid'			=> 'STRING',
		);
		$this->display(true, $registeredURLParams);
	}

	public function ini()
	{
		$id = $this->input->getInt('id', 0);
		if($id == 0) {
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

		$registeredURLParams = array(
			'option'		=> 'CMD',
			'view'			=> 'CMD',
			'task'			=> 'CMD',
			'format'		=> 'CMD',
			'layout'		=> 'CMD',
			'id'			=> 'INT',
			'dlid'			=> 'STRING',
		);
		$this->display(true, $registeredURLParams);
	}
}