<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerARSViewParamsTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerCRIAccessTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerDisplayTrait;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use Akeeba\Component\ARS\Site\View\Items\HtmlView;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Controller\BaseController;

class ItemsController extends BaseController
{
	use ControllerEvents;
	use ControllerRegisterTasksTrait;
	use ControllerDisplayTrait;
	use ControllerARSViewParamsTrait;
	use ControllerCRIAccessTrait;

	protected $default_view = 'releases';

	protected function onBeforeDisplay(&$cachable, &$urlparams)
	{
		$layout = $this->input->getCmd('layout', 'default');
		$tmpl   = $this->input->getCmd('tmpl', '');

		if (($layout == 'modal') && ($tmpl == 'component'))
		{
			$this->onBeforeBrowseModal($cachable, $urlparams);

			return;
		}

		$cachable = true;

		$urlparams = is_array($urlparams) ? $urlparams : [];
		$urlparams = array_merge(self::$defaultUrlParams, $urlparams, [
			'task'       => 'CMD',
			'format'     => 'CMD',
			'layout'     => 'CMD',
			'release_id' => 'INT',
			'dlid'       => 'STRING',
		]);

		$params     = $this->app->getParams();
		$user       = $this->app->getIdentity();
		$release_id = $this->input->getInt('release_id', $params->get('category_id', 0));

		// Release access control
		$release = $this->accessControlRelease($release_id);

		// Category access control
		$category = $this->accessControlCategory($release->category_id);

		// Force category_id to the input, required for caching
		$this->input->set('release_id', $release_id);

		// Now, let's apply some filtering
		/** @var ItemsModel $model */
		$model = $this->getModel('', '', ['ignore_request' => true]);

		// Limit display to a specific category
		$model->setState('filter.release_id', $release_id);

		// Only show published items the user can access (or those allowing the display of unauthorized link)
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
		$this->applyItemsOrderBy($params->get('orderby', 'order'), $model);

		// Get pagination options from the request
		$value = $this->app->input->get('limit', $this->app->get('list_limit', 0), 'uint');
		$model->setState('list.limit', $value);

		$value = $this->app->input->get('limitstart', 0, 'uint');
		$model->setState('list.start', $value);

		// Push data to the view
		/** @var \Akeeba\Component\ARS\Site\View\Items\HtmlView $view */
		$view           = $this->getView('items', 'html');
		$view->category = $category;
		$view->release  = $release;

		$envModel = $this->getModel('Environments');
		$view->setModel($envModel);
	}

	protected function onBeforeBrowseModal(&$cachable, &$urlparams)
	{
		$cachable = false;

		$user = $this->app->getIdentity();

		// Now, let's apply some filtering
		/** @var ItemsModel $model */
		$model = $this->getModel('', '');

		// Populate the model's state from the request.
		$model->getState('_foobar');

		// Only show published releases the user can access (or those allowing the display of unauthorized link)
		$model->setState('filter.published', 1);
		$model->setState('filter.access', $user->getAuthorisedViewLevels());
		$model->setState('filter.allowUnauth', 1);

		/** @var HtmlView $view */
		$view                = $this->getView('items', 'html', '', [
			'base_path' => JPATH_ADMINISTRATOR . '/components/com_ars',
			'layout'    => 'modal',
		]);
		$view->modalFunction = $this->app->input->getCmd('function', 'arsSelectItem');
	}

	public function getView($name = '', $type = '', $prefix = '', $config = [])
	{
		/**
		 * Sneaky override for the modal dialog.
		 *
		 * We force the base_path to the component's backend to make sure that we always load the administrator view
		 * template in this case.
		 */
		if (($config['layout'] ?? 'default') === 'modal')
		{
			$config['base_path'] = JPATH_ADMINISTRATOR . '/components/com_ars';
		}

		return parent::getView($name, $type, $prefix, $config);
	}


}