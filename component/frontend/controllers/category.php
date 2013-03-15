<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerCategory extends FOFController
{
	public function __construct($config = array()) {
		if (array_key_exists('input', $config))
		{
			$input = $config['input'];
		}
		else
		{
			$input = null;
		}

		if (array_key_exists('input_options', $config))
		{
			$input_options = $config['input_options'];
		}
		else
		{
			$input_options = array();
		}

		if ($input instanceof FOFInput)
		{
			$this->input = $input;
		}
		else
		{
			$this->input = new FOFInput($input, $input_options);
		}

		if(in_array($this->input->getCmd('format','html'), array('html','feed'))) {
			$config['modelName'] = 'ArsModelBrowses';
		} elseif(!JFactory::getUser()->authorise('com.manage', 'com_ars')) {
			JError::raiseError(403, 'Forbidden');
		}
		
		parent::__construct($config);
	}

	public function execute($task) {
		if($this->input->getCmd('format','html') == 'html') {
			if(!in_array($task, array('browse','read'))) {
				$task = 'read';
			}
		}

		parent::execute($task);
	}

	function onBeforeRead()
	{
		$limitstart = $this->input->getInt('limitstart', -1);
		if($limitstart >= 0) {
			$this->input->set('start', $limitstart);
		}

		if(!in_array($this->input->getCmd('format','html'), array('html','feed'))) {
			return true;
		}

		$id = $this->input->getInt('id', 0);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		// Push the page params to the model
		$this->getThisModel()
			->grouping($params->get('grouping',	'normal'))
			->orderby($params->get('orderby',		'order'))
			->rel_orderby($params->get('rel_orderby',	'order'))
		;

		// Get the category ID
		if(empty($id)) {
			$id = $params->get('catid', 0);
		}

		if($id > 0) {
			$category = $this->getThisModel()->getCategory($id);
		} else {
			$category = null;
		}

		if($category instanceof ArsTableCategory) {
			$bemodel = FOFModel::getAnInstance('Bleedingedge','ArsModel');
			$bemodel->scanCategory($category);
			$releases = $this->getThisModel()->getReleases($id);
		} else {
			$noAccessURL = JComponentHelper::getParams('com_ars')->get('no_access_url', '');
			if(!empty($noAccessURL)) {
				$this->setRedirect($noAccessURL);
			}
			return false;
		}

		return true;
	}
}