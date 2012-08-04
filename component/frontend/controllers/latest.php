<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerLatest extends FOFController
{
	public function __construct($config = array()) {
		$config['modelName'] = 'ArsModelBrowses';
		parent::__construct($config);
	}
	
	public function execute($task) {
		if(!$this->layout) {
			$this->layout = 'latest';
		}
		$task = 'browse';
		
		parent::execute($task);
	}
	
	public function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		
		if($result) {
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			
			// Push the page params to the model
			$model = $this->getThisModel()
				->grouping($params->get('grouping',	'normal'))
				->orderby($params->get('orderby',	'order'))
				->limitstart(0)
				->limit(0);
		}

		return $result;
	}
}