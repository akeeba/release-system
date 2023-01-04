<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Categories;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\ARS\Site\Model\CategoriesModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;

	/** @var  array  The items to display */
	public $items;

	/** @var  Registry  Page parameters */
	public $params;

	/** @var  string  The order column */
	public $order;

	/** @var  string  The order direction */
	public $order_Dir;

	/** @var  int  Active menu item ID */
	public $Itemid;

	/** @var  object  The active menu item */
	public $menu;

	/** @var  string  Custom repository file */
	public $customHtmlFile;

	public function onBeforeMain($tpl = null): void
	{
		// Load the model
		/** @var CategoriesModel $model */
		$model = $this->getModel();

		/** @var SiteApplication $app */
		$app    = Factory::getApplication();
		$params = $app->getParams();

		$this->items = $model->getItems();

		// Do I have a custom HTML file?
		$useCustomHtml      = $params->get('useCustomRepoFile', 1) == 1;
		$customRepoFilename = $params->get('customRepoFilename', 'repo.html');

		$this->customHtmlFile = JPATH_THEMES . '/' . $app->getTemplate() . '/html/com_ars/categories/' . $customRepoFilename;

		if (!$useCustomHtml || !File::exists($this->customHtmlFile))
		{
			$this->customHtmlFile = null;
		}

		// Get the ordering
		$this->order     = $model->getState('list.order', 'id');
		$this->order_Dir = $model->getState('list.direction', 'desc');

		// Pass page params
		$this->params = $app->getParams();
		$this->Itemid = $app->input->getInt('Itemid', 0);
		$this->menu   = $app->getMenu()->getActive();
	}
}