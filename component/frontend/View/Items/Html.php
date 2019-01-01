<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Items;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Breadcrumbs;
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF30\Model\DataModel\Collection;
use FOF30\View\DataView\Html as BaseView;
use JText;

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

	public $downloadId;
	public $directlink;
	public $directlink_extensions;
	public $directlink_description;

	/** @var  array	Sorting order options */
	public $sortFields = [];

	public $filters = [];

	protected function onBeforeBrowseModal($tpl = null)
	{
		parent::onBeforeBrowse();

		$this->pagination->setAdditionalUrlParam('option', 'com_ars');
		$this->pagination->setAdditionalUrlParam('view', 'Items');
		$this->pagination->setAdditionalUrlParam('layout', 'modal');
		$this->pagination->setAdditionalUrlParam('tmpl', 'component');
		$this->pagination->setAdditionalUrlParam('Itemid', '');

		$hash = 'ars'.strtolower($this->getName());

		// ...ordering
		$platform        = $this->container->platform;
		$input           = $this->input;
		$this->order     = $platform->getUserStateFromRequest($hash . 'filter_order', 'filter_order', $input, 'id');
		$this->order_Dir = $platform->getUserStateFromRequest($hash . 'filter_order_Dir', 'filter_order_Dir', $input, 'DESC');

		// ...filter state
		$this->filters['title'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_title', 'title', $input);
		$this->filters['category'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_category', 'category', $input);
		$this->filters['release'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_release', 'release', $input);
		$this->filters['type'] 	 	  	  = $platform->getUserStateFromRequest($hash . 'filter_type', 'type', $input);
		$this->filters['published']	 	  = $platform->getUserStateFromRequest($hash . 'filter_published', 'published', $input);
		$this->filters['access']	 	  = $platform->getUserStateFromRequest($hash . 'filter_access', 'access', $input);
		$this->filters['language']	 	  = $platform->getUserStateFromRequest($hash . 'filter_language', 'language', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'ordering' 	 		=> JText::_('LBL_VGROUPS_TITLE'),
			'release' 	 		=> JText::_('LBL_ITEMS_RELEASE'),
			'title' 	 		=> JText::_('LBL_VGROUPS_TITLE'),
			'type'	 	 		=> JText::_('LBL_ITEMS_TYPE'),
			'access'	 	 	=> JText::_('JFIELD_ACCESS_LABEL'),
			'published' 	 	=> JText::_('JPUBLISHED'),
			'hits'		 	 	=> JText::_('JGLOBAL_HITS'),
			'language'	 	 	=> JText::_('JFIELD_LANGUAGE_LABEL')
		);
	}

	public function onBeforeBrowse($tpl = null)
	{
		$this->addJavascriptFile('media://fef/js/tabs.min.js');

		// If we're browsing a modal, we need some different things
		$layout = $this->input->getCmd('layout', 'default');
		$tmpl   = $this->input->getCmd('tmpl', '');

		if (($layout == 'modal') && ($tmpl == 'component'))
		{
			$this->onBeforeBrowseModal();

			return true;
		}

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

			$this->directlink_extensions  = $directlink_extensions;
			$this->directlink_description = $params->get('directlink_description', \JText::_('COM_ARS_CONFIG_DIRECTLINKDESCRIPTION_DEFAULT'));
		}

		// Get the ordering
		$this->order 	 = $model->getState('filter_order', 'id', 'cmd');
		$this->order_Dir = $model->getState('filter_order_Dir', 'DESC', 'cmd');

		// Assign data to the view
		$this->release 	  = $this->getModel('Releases');

		// Pass page params
		$this->params = $params;
		$this->Itemid = $this->input->getInt('Itemid', 0);
		$this->menu   = $app->getMenu()->getActive();

		return true;
	}
}
