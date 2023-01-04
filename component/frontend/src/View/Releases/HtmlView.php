<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Releases;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Akeeba\Component\ARS\Site\Helper\Breadcrumbs;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
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

	/**
	 * The category these releases belong to.
	 *
	 * @var CategoryTable
	 */
	public $category;

	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination;

	public function onBeforeMain($tpl = null): void
	{
		// Load the model
		/** @var ReleasesModel $model */
		$model = $this->getModel();

		/** @var SiteApplication $app */
		$app = Factory::getApplication();

		$this->items = $model->getItems();

		// Breadcrumbs
		$repoType = $this->category->type;
		Breadcrumbs::addRepositoryRoot($repoType);
		Breadcrumbs::addCategory($this->category->id, $this->category->title);

		// Get the ordering
		$this->order     = $model->getState('list.order', 'id');
		$this->order_Dir = $model->getState('list.direction', 'desc');

		// Pass page params
		$this->pagination = $model->getPagination();
		$this->params     = $app->getParams();
		$this->Itemid     = $app->input->getInt('Itemid', 0);
		$this->menu       = $app->getMenu()->getActive();
	}

	public function getReleaseUrl(object $release)
	{
		$releaseUrl = Route::_(sprintf("index.php?option=com_ars&view=items&release_id=%u&Itemid=%u", $release->id, $this->Itemid));

		$hasAccess = in_array($release->access, Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());

		if ($hasAccess)
		{
			return $releaseUrl;
		}

		if (!$release->show_unauth_links || empty($release->redirect_unauth))
		{
			return $releaseUrl;
		}

		$redirectUrl = $release->redirect_unauth;

		if ((substr($redirectUrl, 0, 7) !== 'http://') && (substr($redirectUrl, 0, 8) !== 'https://'))
		{
			$redirectUrl = Route::_($redirectUrl) ?: $releaseUrl;
		}

		return $redirectUrl;
	}
}