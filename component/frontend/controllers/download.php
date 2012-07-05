<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerDownload extends FOFController
{
	public function execute($task) {
		$task = 'download';
		
		parent::execute($task);
	}
	
	public function download($cachable = false, $urlparams = false)
	{
		$id = FOFInput::getInt('id', null, $this->input);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		// Get the model
		$model = $this->getThisModel();

		// Get the log table
		$log = FOFModel::getTmpInstance('Logs','ArsModel')->getTable();

		// Get the item lists
		if($id > 0) {
			$item = $model->getItem($id);
		} else {
			$item = null;
		}

		if(is_null($item))
		{
			$log->save(array('authorized' => 0));
			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN') );
		}

		$item->hit();
		$log->save(array('authorized' => 1));

		$model->doDownload();
		
		// No need to return anything; doDownload() calls the exit() method of the application object
	}
}