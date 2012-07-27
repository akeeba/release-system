<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewUpdate extends FOFViewHtml
{
	function onDisplay($tpl = null) {
		$this->loadHelper('router');
		
		$task = $this->getModel()->getState('task', 'all');
		$document = JFactory::getDocument();
		$document->setMimeEncoding('text/xml');

		switch($task)
		{
			default:
			case 'all':
				$component = JComponentHelper::getComponent( 'com_ars' );
				$params = ($component->params instanceof JRegistry) ? $component->params : new JParameter($component->params);
				$this->assign('updates_name', $params->get('updates_name','') );
				$this->assign('updates_desc', $params->get('updates_desc','') );
				$this->setLayout('all');
				break;

			case 'category':
				$category = FOFInput::getCmd('id', '', $this->input);
				$model = $this->getModel();
				$items = $model->items;
				$this->assign('category',		$category);
				$this->assign('items',			$items);
				$this->setLayout('category');
				break;

			case 'stream':
				$model 		= $this->getModel();
				$items		= $model->items;
				$published	= $model->published;
				$this->assign('items',			$items);
				$this->assign('published', 		$published);
				$this->setLayout('stream');
				break;
		}

		return true;
	}
}