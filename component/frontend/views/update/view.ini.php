<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewUpdate extends JView
{
	function display($tpl = null) {
		$task = JRequest::getCmd('task', '');
		$document =& JFactory::getDocument();
		$document->setMimeEncoding('text/plain');

		$model = $this->getModel();
		$items = $model->items;
		$this->assign('items',			$items);

		parent::display();
	}
}