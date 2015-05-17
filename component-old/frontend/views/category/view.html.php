<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewCategory extends F0FViewHtml
{
	public function onAdd($tpl = null)
	{
		return $this->onRead();
	}

	public function onEdit($tpl = null)
	{
		return $this->onRead();
	}

	public function onRead($tpl = null)
	{
		// Load helpers
		$this->loadHelper('breadcrumbs');
		$this->loadHelper('html');
		$this->loadHelper('router');
		$this->loadHelper('title');

		// Get some useful information
		$model = $this->getModel();
		$item = $model->item;
		$repoType = $item->type;

		// Add breadcrumbs
		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($model->item->id, $model->item->title);

		// Add RSS links, title and meta
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		$title = ArsHelperTitle::setTitleAndMeta($params, $item->title);

		$show_feed = $params->get('show_feed_link');

		if ($show_feed)
		{
			$feed = 'index.php?option=com_ars&view=category&id=' . $model->item->id . '&format=feed';
			$rss = array(
				'type'  => 'application/rss+xml',
				'title' => $title . ' (RSS)'
			);
			$atom = array(
				'type'  => 'application/atom+xml',
				'title' => $title . ' (Atom)'
			);

			// Add the links
			$document = JFactory::getDocument();
			$document->addHeadLink(JRoute::_($feed . '&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(JRoute::_($feed . '&type=atom'), 'alternate',
				'rel', $atom);
		}

		$this->pparams = $params;
		$this->pagination = $model->relPagination;
		$this->items = $model->itemList;
		$this->item = $item;
		$this->category_id = $model->getState('category_id', 0);
		$this->Itemid = $this->input->getInt('Itemid', null);

		return true;
	}
}