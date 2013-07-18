<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewLatests extends FOFViewHtml
{
	public function onAdd($tpl = null)
	{
		return $this->onDisplay();
	}

	public function onDisplay($tpl = null)
	{
		// Load helper classess
		$this->loadHelper('html');
		$this->loadHelper('router');
		$this->loadHelper('title');

		// Load the items
		$model = $this->getModel();
		$model->processLatest();
		$this->items		= $model->itemList;

		// Load visual group definitions
		$raw = FOFModel::getTmpInstance('Vgroups','ArsModel')
			->frontend(1)
			->getItemList(true);

		$vgroups = array();
		$groupedItems = 0;

		$defaultVgroup = (object)array(
			'title'			=> '',
			'description'	=> '',
			'numitems'		=> array()
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
							if (!empty($item->release))
							{
								if (!empty($item->release->files))
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
					}
				}

				$vgroups[$r->id] = (object)array(
					'title'			=> $r->title,
					'description'	=> $r->description,
					'numitems'		=> $noOfItems,
				);
			}
		}
		else
		{
			foreach ($this->items as $renderSection => $items)
			{
				$defaultVgroup->numitems[$renderSection] = count($items);
			}
		}

		$vgroups = array_merge(array($defaultVgroup), $vgroups);

		// Add RSS links
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		// Set page title and meta
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
			// add the links
			$document = JFactory::getDocument();
			$document->addHeadLink(AKRouter::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(AKRouter::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}

		// ...ordering
		$this->lists->set('order',		$model->getState('filter_order', 'id', 'cmd'));
		$this->lists->set('order_Dir',	$model->getState('filter_order_Dir', 'DESC', 'cmd'));

		// Assign data to the view
		$this->pagination	= $model->getPagination();
		$this->lists		= $this->lists;
		$this->vgroups		= $vgroups;
		$this->cparams		= $params;
		$this->Itemid		= $this->input->getInt('Itemid', 0);

		return true;
	}
}