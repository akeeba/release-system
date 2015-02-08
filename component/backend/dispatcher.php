<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsDispatcher extends F0FDispatcher
{

	public $defaultView = 'cpanels';

	public function onBeforeDispatch()
	{
		$result = parent::onBeforeDispatch();

		if (!$result)
		{
			return $result;
		}

		$view = F0FInflector::singularize($this->input->getCmd('view', $this->defaultView));

		if ($view == 'liveupdate')
		{
			$url = JUri::base() . 'index.php?option=com_ars';
			JFactory::getApplication()->redirect($url);

			return;
		}

		// Load Akeeba Strapper
		include_once JPATH_ROOT . '/media/akeeba_strapper/strapper.php';
		AkeebaStrapper::bootstrap();
		AkeebaStrapper::jQueryUI();
		AkeebaStrapper::addCSSfile('media://com_ars/css/backend.css');

		//AkeebaStrapper::addJSfile('media://com_ars/js/backend.js');

		return true;
	}
}