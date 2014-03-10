<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsDispatcher extends FOFDispatcher
{
	public $defaultView = 'cpanels';

	public function onBeforeDispatch()
	{
		// You can't fix stupid… but you can try working around it
		if ((!function_exists('json_encode')) || (!function_exists('json_decode')))
		{
			require_once JPATH_ADMINISTRATOR . '/components/' . $this->component . '/helpers/jsonlib.php';
		}

		$result = parent::onBeforeDispatch();

		if (!$result)
		{
			return $result;
		}

		$view = FOFInflector::singularize($this->input->getCmd('view',$this->defaultView));

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