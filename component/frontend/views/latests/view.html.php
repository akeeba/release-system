<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewLatests extends FOFViewHtml
{
	public function onAdd($tpl = null) {
		return $this->onDisplay();
	}
	
	public function onDisplay($tpl = null)
	{
		$this->loadHelper('router');
		
		// Load CSS
		FOFTemplateUtils::addCSS('media://com_ars/css/frontend.css');
		
		// Load visual group definitions
		$raw = FOFModel::getTmpInstance('Vgroups','ArsModel')
			->frontend(1)
			->getItemList(true);
		$vgroups = array('0' => '');
		if(!empty($raw)) foreach($raw as $r) {
			$vgroups[$r->id] = $r->title;
		}
		$this->assign('vgroups', $vgroups);
		
		// Add RSS links
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');
		$show_feed = $params->get('show_feed_link');
		if($show_feed)
		{
			if ($params->get('show_page_title', 1))
			{
				$title = $params->get('page_title');
			}
			else
			{
				$title = JText::_('ARS_VIEW_BROWSE_TITLE');
			}


			$feed = 'index.php?option=com_ars&view=categories&format=feed';
			$rss = array(
				'type' => 'application/rss+xml',
				'title' => $title.' (RSS)'
			);
			$atom = array(
				'type' => 'application/atom+xml',
				'title' => $title.' (Atom)'
			);
			// add the links
			$document = JFactory::getDocument();
			$document->addHeadLink(AKRouter::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(AKRouter::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}
		
		// Load the model
		$model = $this->getModel();
		
		// ...ordering
		$this->lists->set('order',		$model->getState('filter_order', 'id', 'cmd'));
		$this->lists->set('order_Dir',	$model->getState('filter_order_Dir', 'DESC', 'cmd'));
		
		// Assign data to the view
		$model->processLatest();
		$this->assign   ( 'items', $model->itemList);
		$this->assign   ( 'pagination',	$model->getPagination());
		$this->assignRef( 'lists',		$this->lists);
		
		//pass page params
		$params = JFactory::getApplication()->getParams();
		$this->assignRef('cparams', $params);
		return true;
	}
}