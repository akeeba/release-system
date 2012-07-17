<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerBrowse extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);

		// Do not remove these two lines. Required to handle fallback to repo view on invalid URLs.
		$config['view'] = 'browses';
		$dummy = $this->getThisView($config);
	}
	
	public function execute($task) {
		if(!in_array( $this->layout, array('normal','bleedingedge','repository') ))
		{
			$this->layout = 'repository';
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