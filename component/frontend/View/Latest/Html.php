<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Latest;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF40\Model\DataModel\Collection;
use FOF40\View\DataView\Html as BaseView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

class Html extends BaseView
{
	/** @var  Collection  The items to display */
	public $categories;

	/** @var  Releases[]  An array of releases, indexed by category ID */
	public $releases;

	/** @var  int  Active menu item ID */
	public $Itemid;

	/** @var  object  The active menu item */
	public $menu;

	/** @var  \JRegistry */
	public $params;

	/** @var  \JRegistry */
	public $cparams;

	public function onBeforeMain($tpl = null): void
	{
		// Prevent phpStorm's whining...
		if ($tpl) {}

		// Load the model
		/** @var Categories $model */
		$model = $this->getModel('Categories');

		// Assign data to the view, part 1 (we need this later on)
		$this->categories = $model->get(true)->filter(function ($item)
		{
			return Filter::filterItem($item, true);
		});

		/** @var Releases $releasesModel */
		$releasesModel = $this->getModel();
		$releases = $releasesModel->get(true);

		$this->releases = [];

		if ($releases->count())
		{
			/** @var Releases $release */
			foreach($releases as $release)
			{
				$this->releases[$release->category_id] = $release;
			}
		}

		// Add RSS links
		/** @var \JApplicationSite $app */
		$app = Factory::getApplication();

		// Pass page params
		$this->params  = $app->getParams();
		$this->cparams = ComponentHelper::getParams('com_ars');
		$this->Itemid  = $this->input->getInt('Itemid', 0);
		$this->menu    = $app->getMenu()->getActive();
	}
}
