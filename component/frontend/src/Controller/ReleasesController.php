<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerARSViewParamsTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerCRIAccessTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerDisplayTrait;
use Akeeba\Component\ARS\Site\Model\BleedingedgeModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Controller\BaseController;

class ReleasesController extends BaseController
{
	use ControllerEvents;
	use ControllerRegisterTasksTrait;
	use ControllerDisplayTrait;
	use ControllerARSViewParamsTrait;
	use ControllerCRIAccessTrait;

	protected $default_view = 'releases';

	protected function onBeforeDisplay(&$cachable, &$urlparams)
	{
		$cachable = true;

		$urlparams = is_array($urlparams) ? $urlparams : [];
		$urlparams = array_merge(self::$defaultUrlParams, $urlparams, [
			'task'        => 'CMD',
			'format'      => 'CMD',
			'layout'      => 'CMD',
			'category_id' => 'INT',
			'dlid'        => 'STRING',
		]);

		$params      = $this->app->getParams();
		$user        = $this->app->getIdentity();
		$category_id = $this->input->getInt('category_id', $params->get('category_id', 0));

		// Category access control
		$category = $this->accessControlCategory($category_id);

		// Force category_id to the input, required for caching
		$this->input->set('category_id', $category_id);

		// Now, let's apply some filtering
		/** @var ReleasesModel $model */
		$model = $this->getModel('', '', ['ignore_request' => true]);

		// Limit display to a specific category
		$model->setState('filter.category_id', $category_id);

		// Only show published releases the user can access (or those allowing the display of unauthorized link)
		$model->setState('filter.published', 1);
		$model->setState('filter.access', $user->getAuthorisedViewLevels());
		$model->setState('filter.allowUnauth', 1);

		// Language filter, for multi-language sites
		$model->setState('filter.language', '');

		if (Multilanguage::isEnabled($this->app))
		{
			$model->setState('filter.language', ['*', $this->app->getLanguage()->getTag()]);
		}

		// Apply the Order By from the page parameters (default: ordering)
		$this->applyReleaseOrderBy($params->get('rel_orderby', 'order'), $model);

		// Get pagination options from the request
		$value = $this->app->input->get('limit', $this->app->get('list_limit', 0), 'uint');
		$model->setState('list.limit', $value);

		$value = $this->app->input->get('limitstart', 0, 'uint');
		$model->setState('list.start', $value);

		// Run the BleedingEdge detection code
		/** @var BleedingedgeModel $beModel */
		$beModel = $this->getModel('Bleedingedge');
		$beModel->scanCategory($category);

		// Push data to the view
		/** @var \Akeeba\Component\ARS\Site\View\Releases\HtmlView $view */
		$view           = $this->getView();
		$view->category = $category;

		$itemsModel = $this->getModel('Items', 'Site', ['ignore_request' => true]);
		$view->setModel($itemsModel, false);
	}
}
