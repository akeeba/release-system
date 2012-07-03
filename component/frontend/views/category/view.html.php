<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewCategory extends FOFViewHtml
{
	public function onAdd($tpl = null) {
		return $this->onRead();
	}
	
	public function onEdit($tpl = null) {
		return $this->onRead();
	}
	
	public function onRead($tpl = null) {
		// Load helpers
		$this->loadHelper('breadcrumbs');
		$this->loadHelper('chameleon');
		$this->loadHelper('html');
		$this->loadHelper('router');
		
		// Load CSS
		FOFTemplateUtils::addCSS('media://com_ars/css/frontend.css');
		
		// Get some useful information
		$model = $this->getModel();
		$repoType = $model->item->type;
		
		// Add breadcrumbs
		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($model->item->id, $model->item->title);
		
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
				$title = JText::_('ARS_VIEW_CATEGORY_TITLE');
			}


			$feed = 'index.php?option=com_ars&view=category&id='.$model->item->id.'&format=feed';
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
			$document->addHeadLink(JRoute::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(JRoute::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}
		
		$this->assignRef('pparams', $params);
		$this->assignRef('pagination', $model->relPagination);
		$this->assign('items', $model->itemList);
		$this->assignRef('item', $model->item);
		$this->assign('category_id', $model->getState('category_id', 0));
		
		return true;
	}
}