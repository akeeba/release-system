<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewRelease extends FOFViewHtml
{
	public function onAdd($tpl = null) {
		return $this->onRead();
	}
	
	public function onEdit($tpl = null) {
		return $this->onRead();
	}
	
	function onRead($tpl = null)
	{
		// Load helpers
		$this->loadHelper('breadcrumbs');
		$this->loadHelper('chameleon');
		$this->loadHelper('html');
		$this->loadHelper('router');
		
		// Load CSS
		FOFTemplateUtils::addCSS('media://com_ars/css/frontend.css');
		
		// Add a breadcrumb if necessary
		$model = $this->getModel();

		$catModel = FOFModel::getTmpInstance('Categories','ArsModel');
		$category = $catModel->getItem($model->item->category_id);

		$repoType = $category->type;
		
		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($category->id, $category->title);
		ArsHelperBreadcrumbs::addRelease($model->item->id, $model->item->version);

		$this->assignRef( 'category',	$category );

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


			$feed = 'index.php?option=com_ars&view=category&id='.$category->id.'&format=feed';
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
		
		// Cleanup for display
		$items	= $model->itemList;
		
		foreach ( $items as $item ) {
			$item->environments = ArsHelperHtml::getEnvironments( $item->environments );
		}
		
		$model->itemList = $items;
		
		$this->assignRef('cparams', $params);
		$this->assignRef('item', $model->item);
		$this->assign('items', $model->itemList);
		$this->assignRef('pagination', $model->items_pagination);
		$this->assign('release_id', $model->item->id);
		
		if($this->getLayout() == 'item') {
			$this->setLayout('default');
		}
		
		return true;
	}
}