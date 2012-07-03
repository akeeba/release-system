<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerCategory extends FOFController
{
	public function __construct($config = array()) {
		$config['modelName'] = 'ArsModelBrowses';
		parent::__construct($config);
	}
	
	public function execute($task) {
		if(!in_array($task, array('browse','read'))) {
			$task = 'read';
		}
		
		parent::execute($task);
	}
	
	function onBeforeRead() {
		$id = FOFInput::getInt('id', 0, $this->input);

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
			return false;
		}
		
		return true;
	}
}