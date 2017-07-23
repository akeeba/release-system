<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Items;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Breadcrumbs;
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Title;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use Akeeba\ReleaseSystem\Site\Model\Items;
use FOF30\Model\DataModel\Collection;
use FOF30\View\View as BaseView;

class Html extends BaseView
{
	/** @var  Collection  The items to display */
	public $items;

	/** @var  Releases  The category of the releases */
	public $release;

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

	/** @var  \JMenuNode  The active menu item */
	public $menu;

	public function onBeforeBrowse($tpl = null)
	{
		// Prevent phpStorm's whining...
		if ($tpl) {}

		// Load the model
		/** @var Items $model */
		$model = $this->getModel();

		// Assign data to the view, part 1 (we need this later on)
		$this->items = $model->get(true)->filter(function ($item)
		{
			return Filter::filterItem($item, true);
		});

		/** @var \JApplicationSite $app */
		$app = \JFactory::getApplication();
		$user = $this->container->platform->getUser();
		$params = $app->getParams();

		// Add Breadcrumbs
		/** @var Releases $release */
		$release = $this->getModel('Releases');
		/** @var Categories $category */
		$category = $release->category;
		Breadcrumbs::addRepositoryRoot($category->type);
		Breadcrumbs::addCategory($category->id, $category->title);
		Breadcrumbs::addRelease($release->id, $release->version);

		// DirectLink setup
		$this->downloadId = Filter::myDownloadID();

		$directlink = $params->get('show_directlink', 1) && !$user->guest;
		$this->directlink = $directlink;

		// Pass on Direct Link-related stuff
		if ($directlink)
		{
			$directlink_extensions = explode(',', $params->get('directlink_extensions', 'zip,tar,tar.gz,tgz,tbz,tar.bz2'));

			if (empty($directlink_extensions))
			{
				$directlink_extensions = array();
			}
			else
			{
				$temp = array();

				foreach ($directlink_extensions as $ext)
				{
					$temp[] = '.' . trim($ext);
				}

				$directlink_extensions = $temp;
			}

			$this->directlink_extensions = $directlink_extensions;

			$this->directlink_description = $params->get('directlink_description', \JText::_('COM_ARS_CONFIG_DIRECTLINKDESCRIPTION_DEFAULT'));
		}

		// Get the ordering
		$this->order = $model->getState('filter_order', 'id', 'cmd');
		$this->order_Dir = $model->getState('filter_order_Dir', 'DESC', 'cmd');

		// Assign data to the view
		$this->pagination = new \JPagination($model->count(), $model->limitstart, $model->limit);
		$this->release = $this->getModel('Releases');

		// Pass page params
		$this->params = $params;
		$this->Itemid = $this->input->getInt('Itemid', 0);
		$this->menu = $app->getMenu()->getActive();

		return true;
	}
}