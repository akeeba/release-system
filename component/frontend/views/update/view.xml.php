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
		$document->setMimeEncoding('text/xml');

		switch($task)
		{
			case 'all':
				$component =& JComponentHelper::getComponent( 'com_ars' );
				$params = ($component->params instanceof JRegistry) ? $component->params : new JParameter($component->params);
				$this->assign('updates_name', $params->get('updates_name','') );
				$this->assign('updates_desc', $params->get('updates_desc','') );
				break;

			case 'category':
				$category = JRequest::getCmd('id','');
				$model = $this->getModel();
				$items = $model->items;
				$this->assign('category',		$category);
				$this->assign('items',			$items);
				break;

			case 'stream':
				$model = $this->getModel();
				$items = $model->items;
				$this->assign('items',			$items);
				break;
		}

		parent::display();
	}
}