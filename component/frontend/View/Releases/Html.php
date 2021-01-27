<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Releases;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Breadcrumbs;
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF40\Model\DataModel\Collection;
use FOF40\View\DataView\Html as BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;

class Html extends BaseView
{
	/** @var  Collection  The items to display */
	public $items;

	/** @var  Categories  The category of the releases */
	public $category;

	/** @var  \JRegistry  Page parameters */
	public $params;

	/** @var  string  The order column */
	public $order;

	/** @var  string  The order direction */
	public $order_Dir;

	/** @var  \JPagination  Pagination object */
	public $pagination;

	/** @var  int  Active menu item ID */
	public $Itemid;

	/** @var  object  The active menu item */
	public $menu;

	public function onBeforeBrowse($tpl = null): void
	{
		// Load the model
		/** @var Releases $model */
		$model = $this->getModel();

		$model->with(['category', 'items']);

		// Assign data to the view, part 1 (we need this later on)
		$this->items = $model->get()->filter(function ($item) {
			return Filter::filterItem($item, true);
		});

		// Add breadcrumbs
		/** @var Items $firstItem */
		$firstItem = $this->items->first();

		if (empty($firstItem))
		{
			$category_id = $model->getState('category', 0);
			/** @var Items $category */
			$category = $this->container->factory->model('Items');
			$category->load($category_id);
		}
		else
		{
			$category = $firstItem->category;
		}

		$repoType = $category->type;
		Breadcrumbs::addRepositoryRoot($repoType);
		Breadcrumbs::addCategory($category->id, $category->title);

		// Add RSS links
		/** @var \JApplicationSite $app */
		$app = Factory::getApplication();

		// Get the ordering
		$this->order     = $model->getState('filter_order', 'id', 'cmd');
		$this->order_Dir = $model->getState('filter_order_Dir', 'DESC', 'cmd');

		// Assign data to the view
		$this->pagination = new Pagination($model->count(), $model->limitstart, $model->limit);
		$this->category   = $this->getModel('Categories');

		// Pass page params
		$this->params = $app->getParams();
		$this->Itemid = $this->input->getInt('Itemid', 0);
		$this->menu   = $app->getMenu()->getActive();
	}

	public function getEnvironments(Releases $release): array
	{
		$ret = [];

		/** @var Collection $items */
		$items = $release->items;

		$items->each(function (Items $item) use (&$ret) {
			$environments = $item->environments;

			if (empty($environments) || !is_array($environments))
			{
				return;
			}

			$ret = array_merge($ret, $environments);
		});

		if (empty($ret))
		{
			return $ret;
		}

		$ret = array_unique($ret);

		$titles = array_map(function ($environment) {
			return \Akeeba\ReleaseSystem\Admin\Helper\Select::environmentTitle((int) $environment);
		}, $ret);

		uasort($titles, function ($a, $b) {
			$partsA = explode(' ', $a, 2);
			$partsB = explode(' ', $b, 2);

			if (($partsA[0] != $partsB[0]) || (count($partsA) < 2) || (count($partsB) < 2))
			{
				return $a <=> $b;
			}

			return version_compare($partsA[1], $partsB[1]);
		});

		return $titles;
	}
}
