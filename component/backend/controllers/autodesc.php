<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'controllers'.DS.'default.php';

class ArsControllerAutodesc extends ArsControllerDefault
{
	public function copy()
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$ids = $model->getIds();
		if(!empty($ids))
		{
			foreach($ids as $id)
			{
				$model->setId($id);
				$item = $model->getItem();
				$item->title = JText::_('Copy of').' '.$item->title;
				$item->id = 0;
				$status = $model->save($item);

				if(!$status) break;
			}
		}
		else
		{
			$status = true;
		}

		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
		$url = 'index.php?option='.$option.'&view='.$view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
	}
}