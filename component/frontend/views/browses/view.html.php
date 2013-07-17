<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class ArsViewBrowses extends FOFViewHtml
{
	public function onAdd($tpl = null)
	{
		return $this->onDisplay();
	}

	public function onDisplay($tpl = null)
	{
		// Load helpers
		$this->loadHelper('router');
		$this->loadHelper('html');

		// Load the model
		$model = $this->getModel();

		// Assign data to the view, part 1 (we need this later on)
		$this->items		= $model->getCategories();

		// Load visual group definitions
		$raw = FOFModel::getTmpInstance('Vgroups','ArsModel')
			->frontend(1)
			->getItemList(true);

		$vgroups = array();
		$groupedItems = 0;

		$defaultVgroup = (object)array(
			'title'			=> '',
			'description'	=> '',
			'numitems'		=> array(),
		);

		if (!empty($raw))
		{
			foreach($raw as $r)
			{
				// Get the number of items per visual group and render section
				$noOfItems = array();

				if (!empty($this->items))
				{
					foreach ($this->items as $renderSection => $items)
					{
						$noOfItems[$renderSection] = 0;

						if (!array_key_exists($renderSection, $defaultVgroup->numitems))
						{
							$defaultVgroup->numitems[$renderSection] = 0;
						}

						foreach ($items as $item)
						{
							if ($item->vgroup_id == $r->id)
							{
								$noOfItems[$renderSection]++;
								$groupedItems++;
							}
							elseif ($item->vgroup_id == 0)
							{
								$defaultVgroup->numitems[$renderSection]++;
							}
						}
					}
				}

				$vgroups[$r->id] = (object)array(
					'title'			=> $r->title,
					'description'	=> $r->description,
					'numitems'		=> $noOfItems,
				);
			}
		}

		$vgroups = array_merge(array($defaultVgroup), $vgroups);

		// Add RSS links
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		// Set page title and meta
		$this->loadHelper('title');
		$title = ArsHelperTitle::setTitleAndMeta($params, 'ARS_VIEW_BROWSE_TITLE');

		$show_feed = $params->get('show_feed_link');

		if ($show_feed)
		{
			$feed = 'index.php?option=com_ars&view=categories&format=feed';
			$rss = array(
				'type' => 'application/rss+xml',
				'title' => $title.' (RSS)'
			);
			$atom = array(
				'type' => 'application/atom+xml',
				'title' => $title.' (Atom)'
			);

			// Add the links
			$document = JFactory::getDocument();
			$document->addHeadLink(AKRouter::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(AKRouter::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}

		// Get the ordering
		$this->lists->set('order',		$model->getState('filter_order', 'id', 'cmd'));
		$this->lists->set('order_Dir',	$model->getState('filter_order_Dir', 'DESC', 'cmd'));

		// Assign data to the view
		$this->pagination	= $model->getPagination();
		$this->lists		= $this->lists;
		$this->vgroups		= $vgroups;

		// Pass page params
		$this->params = JFactory::getApplication()->getParams();
		$this->Itemid = $this->input->getInt('Itemid', 0);

		return true;
	}
}