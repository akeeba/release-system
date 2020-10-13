<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Categories;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use FOF30\Model\DataModel\Collection;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Pagination\Pagination;

class Html extends BaseView
{
	/** @var  Collection  The items to display */
	public $items;

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

	public $customHtmlFile;

	public function onBeforeBrowse($tpl = null): void
	{
		// Load the model
		/** @var Categories $model */
		$model = $this->getModel();

		/** @var SiteApplication $app */
		$app    = Factory::getApplication();
		$params = $app->getParams('com_ars');

		// Assign data to the view, part 1 (we need this later on)
		$this->items = $model->get(true)->filter(function ($item) {
			return Filter::filterItem($item, true);
		});

		// Do I have a custom HTML file?
		$useCustomHtml      = $params->get('useCustomRepoFile', 1) == 1;
		$customRepoFilename = $params->get('customRepoFilename', 'repo.html');

		$this->customHtmlFile = JPATH_THEMES . '/' . $app->getTemplate() . '/html/com_ars/Categories/' . $customRepoFilename;

		if (!$useCustomHtml || !File::exists($this->customHtmlFile))
		{
			$this->customHtmlFile = null;
		}

		// Get the ordering
		$this->order     = $model->getState('filter_order', 'id', 'cmd');
		$this->order_Dir = $model->getState('filter_order_Dir', 'DESC', 'cmd');

		// Assign data to the view
		$this->pagination = new Pagination($model->count(), $model->limitstart, $model->limit);

		// Pass page params
		$this->params = $app->getParams();
		$this->Itemid = $this->input->getInt('Itemid', 0);
		$this->menu   = $app->getMenu()->getActive();
	}
}
