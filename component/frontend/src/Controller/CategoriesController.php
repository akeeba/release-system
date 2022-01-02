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
use Akeeba\Component\ARS\Site\Controller\Mixin\DisplayAware;
use Akeeba\Component\ARS\Site\Model\CategoriesModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Controller\BaseController;

class CategoriesController extends BaseController
{
	use ControllerEvents;
	use RegisterControllerTasks;
	use DisplayAware;
	use ARSViewParamsAware;

	protected $default_view = 'categories';

	protected function onBeforeDisplay(&$cachable, &$urlparams)
	{
		$cachable = true;

		$urlparams = is_array($urlparams) ? $urlparams : [];
		$urlparams = array_merge(self::$defaultUrlParams, $urlparams, [
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
		]);

		$params = $this->app->getParams();

		// Apply one of the allowed layouts
		if (!in_array($this->input->get('layout', 'repository'), ['normal', 'bleedingedge', 'repository']))
		{
			$this->input->set('layout', 'repository');
		}

		// Now, let's apply some filtering
		/** @var CategoriesModel $model */
		$model = $this->getModel('', '', ['ignore_request' => true]);

		$user = $this->app->getIdentity();

		// Only show published releases the user can access (or those allowing the display of unauthorized link)
		$model->setState('filter.published', 1);
		$model->setState('filter.access', $user->getAuthorisedViewLevels());
		$model->setState('filter.allowUnauth', 1);

		// Only show still supported software?
		$model->setState('filter.supported', ($params->get('cat_is_supported', 0) == 1) ? 1 : '');

		// Language filter, for multi-language sites
		$model->setState('filter.language', '');

		if (Multilanguage::isEnabled($this->app))
		{
			$model->setState('filter.language', ['*', $this->app->getLanguage()->getTag()]);
		}

		// Apply the Order By from the page parameters (default: ordering)
		$this->applyCategoryOrderBy($params->get('orderby', 'order'), $model);

		// Show all categories (non-paginated)
		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);
	}
}