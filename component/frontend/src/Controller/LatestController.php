<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\RegisterControllerTasks;
use Akeeba\Component\ARS\Site\Controller\Mixin\ARSViewParamsAware;
use Akeeba\Component\ARS\Site\Controller\Mixin\CRIAccessAware;
use Akeeba\Component\ARS\Site\Model\CategoriesModel;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Controller\BaseController;

class LatestController extends BaseController
{
	use RegisterControllerTasks;
	use ControllerEvents;
	use ARSViewParamsAware;
	use CRIAccessAware;

	public function main($cachable = false, $urlparams = [])
	{
		/** @var CategoriesModel $catModel */
		$catModel = $this->getModel('categories', '', ['ignore_request' => true]);
		/** @var ReleasesModel $relModel */
		$relModel = $this->getModel('releases', '', ['ignore_request' => true]);
		/** @var ItemsModel $relModel */
		$itemsModel = $this->getModel('items', '', ['ignore_request' => true]);
		$params     = $this->app->getParams('com_ars');
		$user       = $this->app->getIdentity();

		// Let's find the latest releases. They must be published and accessible to our user.
		$relModel->setState('filter.published', 1);
		$relModel->setState('filter.access', $user->getAuthorisedViewLevels());
		$relModel->setState('filter.allowUnauth', 1);
		$relModel->setState('list.start', 0);
		$relModel->setState('list.limit', 0);

		// -- Language filter, for multi-language sites
		$relModel->setState('filter.language', '');

		if (Multilanguage::isEnabled($this->app))
		{
			$relModel->setState('filter.language', ['*', $this->app->getLanguage()->getTag()]);
		}

		// -- Minimum stability
		$relModel->setState('filter.minMaturity', $params->get('min_maturity', 'alpha'));

		// -- Latest releases filter
		$relModel->setState('filter.latest', true);

		// -- Apply the Order By from the page parameters (default: ordering ascending)
		$this->applyReleaseOrderBy($params->get('rel_orderby', 'order'), $relModel);

		// Let's get the correct categories
		$catModel->setState('filter.published', 1);
		$catModel->setState('filter.access', $user->getAuthorisedViewLevels());
		$catModel->setState('filter.allowUnauth', 1);
		$catModel->setState('list.start', 0);
		$catModel->setState('list.limit', 0);

		// -- Language filter, for multi-language sites
		$catModel->setState('filter.language', '');

		if (Multilanguage::isEnabled($this->app))
		{
			$catModel->setState('filter.language', ['*', $this->app->getLanguage()->getTag()]);
		}

		// -- Supported categories filter
		$catModel->setState('filter.supported', $params->get('cat_is_supported', ''));

		// -- Apply the Order By from the page parameters (default: ordering ascending)
		$this->applyCategoryOrderBy($params->get('cat_orderby', 'order'), $catModel);

		// Let's find the items. They must be published and accessible to our user.
		$itemsModel->setState('filter.published', 1);
		$itemsModel->setState('filter.access', $user->getAuthorisedViewLevels());
		$itemsModel->setState('filter.allowUnauth', 1);
		$itemsModel->setState('list.start', 0);
		$itemsModel->setState('list.limit', 0);

		// -- Language filter, for multi-language sites
		$itemsModel->setState('filter.language', '');

		if (Multilanguage::isEnabled($this->app))
		{
			$itemsModel->setState('filter.language', ['*', $this->app->getLanguage()->getTag()]);
		}

		// Note: the view must tell the ItemsModel instance to filter by specific IDs

		// Get and render the view
		$document       = $this->app->getDocument();
		$view           = $this->getView('latest', 'html');
		$view->document = $document;

		$view->setModel($catModel, true);
		$view->setModel($relModel);
		$view->setModel($itemsModel);

		$view->display();

		return $this;
	}

}