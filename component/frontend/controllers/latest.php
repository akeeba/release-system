<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerLatest extends F0FController
{
	public function __construct($config = array())
	{
		$config['modelName'] = 'ArsModelBrowses';

		parent::__construct($config);

		$this->cacheableTasks = array();
	}

	public function execute($task)
	{
		if (!$this->layout)
		{
			$this->layout = 'latest';
		}
		$task = 'browse';

		parent::execute($task);
	}

	public function onBeforeBrowse()
	{
		$result = parent::onBeforeBrowse();

		if ($result)
		{
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');

			// Push the page params to the model
			$model = $this->getThisModel()
						  ->grouping($params->get('grouping', 'normal'))
						  ->orderby($params->get('orderby', 'order'))
						  ->limitstart(0)
						  ->limit(0);
		}

		return $result;
	}
}