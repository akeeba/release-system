<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\Controller;

class Browse extends Controller
{
	public function execute($task)
	{
		// We only have one task
		$task = 'main';

		if (!in_array($this->layout, ['normal', 'bleedingedge', 'repository']))
		{
			$this->layout = 'repository';
		}

		return parent::execute($task);
	}

	protected function onBeforeMain()
	{
		/** @var \JApplicationSite $app */
		$app    = \JFactory::getApplication();
		$params = $app->getParams('com_ars');

		// Push the page params to the model
		/** @var \Akeeba\ReleaseSystem\Site\Model\Browse $model */
		$model = $this->getModel();
		$model->grouping($params->get('grouping', 'normal'))
			  ->orderby($params->get('orderby', 'order'))
			  ->limitstart(0)
			  ->limit(0);
	}
}