<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerRelease extends FOFController
{
	public function __construct($config = array()) {
		if(FOFInput::getCmd('format','html',$config['input']) == 'html') {
			$config['modelName'] = 'ArsModelBrowses';
		} elseif(!JFactory::getUser()->authorise('com.manage', 'com_ars')) {
			JError::raiseError(403, 'Forbidden');
		}
		parent::__construct($config);
	}

	public function execute($task) {
		if(FOFInput::getCmd('format','html',$this->input) == 'html') {
			if(!in_array($task, array('browse','read'))) {
				$task = 'read';
			}
		}

		parent::execute($task);
	}

	function onBeforeRead() {
		$limitstart = FOFInput::getInt('limitstart', -1, $this->input);
		if($limitstart >= 0) {
			FOFInput::setVar('start', $limitstart, $this->input);
		}

		if(FOFInput::getCmd('format','html',$this->input) != 'html') {
			return true;
		}

		$id = FOFInput::getInt('id', 0, $this->input);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		// Push the page params to the model
		$this->getThisModel()
			->grouping($params->get('grouping',	'normal'))
			->orderby($params->get('orderby',		'order'))
			->rel_orderby($params->get('rel_orderby',	'order'))
			->items_orderby($params->get('items_orderby',	'order'))
		;

		// Get the category ID
		if(empty($id)) {
			$id = $params->get('relid', 0);
		}

		if($id > 0) {
			$release = $this->getThisModel()->getRelease($id);
		} else {
			$release = null;
		}

		if($release instanceof ArsTableRelease) {
			$bemodel = FOFModel::getAnInstance('Bleedingedge','ArsModel');
			$bemodel->checkFiles($release);
			$items = $this->getThisModel()->getItems($id);
		} else {
			$noAccessURL = JComponentHelper::getParams('com_ars')->get('no_access_url', '');
			if(!empty($noAccessURL)) {
				$this->setRedirect($noAccessURL);
			}
			return false;
		}

		return true;
	}

	protected function _csrfProtection()
	{
		$format = $this->input->get('format', 'html');
		$loggedin = !JFactory::getUser()->guest;

		if(($format == 'json') && $loggedin)
		{
			return true;
		}

		return parent::_csrfProtection();
	}
}