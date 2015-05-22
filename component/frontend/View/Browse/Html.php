<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Browse;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Title;
use Akeeba\ReleaseSystem\Site\Model\Browse;
use Akeeba\ReleaseSystem\Site\Model\VisualGroups;
use FOF30\View\View as BaseView;

class Html extends BaseView
{
	/** @var  array  The items to display */
	public $items;

	/** @var  \JRegistry  Page parameters */
	public $params;

	/** @var  string  The order column */
	public $order;

	/** @var  string  The order direction */
	public $order_Dir;

	/** @var  \JPagination  Pagination object */
	public $pagination;

	/** @var  array Visual groups */
	public $vgroups;

	/** @var  int  Active menu item ID */
	public $Itemid;

	/** @var  object  The active menu item */
	public $menu;

	public function onBeforeMain($tpl = null)
	{
		// Load the model
		/** @var Browse $model */
		$model = $this->getModel();

		// Assign data to the view, part 1 (we need this later on)
		$this->items = $model->getCategories();

		// Load visual group definitions
		/** @var VisualGroups $vGroupModel */
		$vGroupModel = $this->container->factory->model('VisualGroups')->tmpInstance();

		$raw = $vGroupModel->get(true);

		$visualGroups = array();
		$groupedItems = 0;

		$defaultVisualGroup = (object)[
			'id'          => 0,
			'title'       => '',
			'description' => '',
			'numitems'    => [],
		];

		if (!empty($raw))
		{
			foreach ($raw as $r)
			{
				// Get the number of items per visual group and render section
				$noOfItems = array();

				if (!empty($this->items))
				{
					foreach ($this->items as $renderSection => $items)
					{
						$noOfItems[$renderSection] = 0;

						if (!array_key_exists($renderSection, $defaultVisualGroup->numitems))
						{
							$defaultVisualGroup->numitems[$renderSection] = 0;
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
								$defaultVisualGroup->numitems[$renderSection]++;
							}
						}
					}
				}

				$visualGroups[$r->id] = (object)[
					'id'          => $r->id,
					'title'       => $r->title,
					'description' => $r->description,
					'numitems'    => $noOfItems,
				];
			}
		}
		else
		{
			foreach ($this->items as $renderSection => $items)
			{
				$defaultVisualGroup->numitems[$renderSection] = count($items);
			}
		}

		$visualGroups = array_merge(array($defaultVisualGroup), $visualGroups);

		// Add RSS links
		/** @var \JApplicationSite $app */
		$app = \JFactory::getApplication();
		$params = $app->getParams('com_ars');

		// Set page title and meta
		$title = Title::setTitleAndMeta($params, 'ARS_VIEW_BROWSE_TITLE');

		$show_feed = $params->get('show_feed_link');

		if ($show_feed)
		{
			$feed = 'index.php?option=com_ars&view=categories&format=feed';

			$rss = array(
				'type'  => 'application/rss+xml',
				'title' => $title . ' (RSS)'
			);

			$atom = array(
				'type'  => 'application/atom+xml',
				'title' => $title . ' (Atom)'
			);

			// Add the links
			/** @var \JDocumentHTML $document */
			$document = \JFactory::getDocument();
			$document->addHeadLink(\AKRouter::_($feed . '&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(\AKRouter::_($feed . '&type=atom'), 'alternate',
				'rel', $atom);
		}

		// Get the ordering
		$this->order = $model->getState('filter_order', 'id', 'cmd');
		$this->order_Dir = $model->getState('filter_order_Dir', 'DESC', 'cmd');

		// Assign data to the view
		$this->pagination = $model->pagination;
		$this->vgroups = $visualGroups;

		// Pass page params
		$this->params = $app->getParams();
		$this->Itemid = $this->input->getInt('Itemid', 0);
		$this->menu = $app->getMenu()->getActive();

		return true;
	}
}