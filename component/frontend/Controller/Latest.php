<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use Joomla\Registry\Registry;

class Latest extends Controller
{
	/**
	 * Page parameters
	 *
	 * @var  Registry
	 */
	public $params;

	/**
	 * Overrides the default display method to add caching support
	 *
	 * @param   bool        $cachable  Is this a cacheable view?
	 * @param   bool|array  $urlparams Registered URL parameters
	 * @param   null|string $tpl       Sub-template (not really used...)
	 */
	public function display($cachable = false, $urlparams = false, $tpl = null)
	{
		$cachable = true;

		if (!is_array($urlparams))
		{
			$urlparams = [];
		}

		$additionalParams = array(
			'option'      => 'CMD',
			'view'        => 'CMD',
			'task'        => 'CMD',
			'format'      => 'CMD',
			'layout'      => 'CMD',
			'id'          => 'INT',
		);

		$urlparams = array_merge($additionalParams, $urlparams);

		parent::display($cachable, $urlparams, $tpl);
	}

	public function execute($task)
	{
		$task         = 'main';
		$this->layout = 'latest';

		return parent::execute($task);
	}

	public function onBeforeMain()
	{
		// Push page parameters to the model
		/** @var \JApplicationSite $app */
		$app          = \JFactory::getApplication();
		$this->params = $app->getParams('com_ars');

		/** @var Releases $model */
		$model = $this->getModel();
		$model->reset(true)
		      ->orderby_filter($this->params->get('rel_orderby', 'order'))
		      ->published(1)
		      ->latest(true)
		      ->access_user($this->container->platform->getUser()->id)
		      ->category_id(0)
		      ->maturity($this->params->get('min_maturity', 'alpha'))
		      ->with(['items']);

		/** @var Categories $categoriesModel */
		$categoriesModel = $this->getModel('Categories');
		$categoriesModel->reset(true)
		                ->orderby_filter($this->params->get('orderby', 'order'))
		                ->published(1)
		                ->is_supported((bool) $this->params->get('cat_is_supported', false))
		                ->access_user($this->container->platform->getUser()->id)
		                ->with([]);
		$this->getView()->setModel('Categories', $categoriesModel);
	}
}
