<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Items;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Mixin\LoadAnyTemplate;
use Akeeba\Component\ARS\Administrator\Mixin\TaskBasedEvents;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Akeeba\Component\ARS\Site\Helper\Breadcrumbs;
use Akeeba\Component\ARS\Site\Model\EnvironmentsModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use TaskBasedEvents;
	use LoadAnyTemplate;

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
	 * @var ReleaseTable
	 */
	public $release;

	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination;

	public $downloadId;

	public $directlink;

	public $directlink_extensions;

	public $directlink_description;

	/**
	 * The search tools form
	 *
	 * @var    Form
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 */
	public $activeFilters = [];

	/** @var string Callback JavaScript function for the modal layout */
	public $modalFunction;

	/**
	 * The model state
	 *
	 * @var    Registry
	 */
	protected $state;

	public function onBeforeMain($tpl = null): void
	{
		if ($this->getLayout() === 'modal')
		{
			$this->onBeforeModal($tpl);

			return;
		}

		// Load the model
		/** @var ReleasesModel $model */
		$model = $this->getModel();

		/** @var SiteApplication $app */
		$app     = Factory::getApplication();
		$params  = $app->getParams();
		$cParams = ComponentHelper::getParams('com_ars');
		$user    = $app->getIdentity();

		$this->items = $model->getItems();

		// Breadcrumbs
		$repoType = $this->category->type;
		Breadcrumbs::addRepositoryRoot($repoType);
		Breadcrumbs::addCategory($this->category->id, $this->category->title);
		Breadcrumbs::addRelease($this->release->id, $this->release->version);

		// DirectLink information
		$this->downloadId = HTMLHelper::_('ars.downloadId', $user->id);
		$this->directlink = $cParams->get('show_directlink', 1) && !$user->guest;

		if ($this->directlink)
		{
			$linkExtensions = explode(',', $cParams->get('directlink_extensions', 'zip,tar,tar.gz,tgz,tbz,tar.bz2')) ?: [];

			$this->directlink_extensions = array_map(function ($ext) {
				return '.' . trim($ext, '. \t\n\r\0\0x0B');
			}, $linkExtensions);

			$this->directlink_description = $cParams->get('directlink_description', Text::_('COM_ARS_CONFIG_DIRECTLINKDESCRIPTION_DEFAULT'));
		}

		// Get the ordering
		$this->order     = $model->getState('list.order', 'id');
		$this->order_Dir = $model->getState('list.direction', 'desc');

		// Pass page params
		$this->pagination = $model->getPagination();
		$this->params     = $app->getParams();
		$this->Itemid     = $app->input->getInt('Itemid', 0);
		$this->menu       = $app->getMenu()->getActive();
	}

	public function getItemUrl(object $item): array
	{
		$basename  = basename($item->type == 'file' ? $item->filename : $item->url);
		$lastDot   = strrpos($basename, '.');
		$extension = 'raw';

		if ($lastDot !== false)
		{
			$extension = substr($basename, $lastDot + 1);
		}

		$itemUrl = Route::_(sprintf("index.php?option=com_ars&view=item&format=%s&category_id=%d&release_id=%d&item_id=%s&Itemid=%s", $extension, $item->cat_id, $item->release_id, $item->id, $this->Itemid));

		$hasAccess = in_array($item->access, Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());

		if ($hasAccess)
		{
			return [$itemUrl, true];
		}

		if (!$item->show_unauth_links || empty($item->redirect_unauth))
		{
			return [$itemUrl, true];
		}

		$redirectUrl = $item->redirect_unauth;

		if ((substr($redirectUrl, 0, 7) !== 'http://') && (substr($redirectUrl, 0, 8) !== 'https://'))
		{
			$redirectUrl = Route::_($redirectUrl) ?: $itemUrl;
		}

		return [$redirectUrl, false];
	}

	public function getDirectLink(object $item, string $downloadUrl): ?string
	{
		$basename   = ($item->type == 'file') ? $item->filename : $item->url;
		$directLink = false;

		if (empty($basename))
		{
			return null;
		}

		foreach ($this->directlink_extensions as $ext)
		{
			if (substr($basename, -strlen($ext)) != $ext)
			{
				continue;
			}

			$directLink = true;
		}

		if (!$directLink)
		{
			return null;
		}

		return $downloadUrl .
			(strpos($downloadUrl, '?') !== false ? '&' : '?') .
			'dlid=' . $this->downloadId;
	}

	public function environmentTitle(int $id): ?string
	{
		static $map;

		if (is_null($map))
		{
			/** @var EnvironmentsModel $envModel */
			$envModel = $this->getModel('Environments');

			$map = $envModel->getEnvironmentTitles();
		}

		return $map[$id] ?? null;
	}

	private function onBeforeModal($tpl)
	{
		// Load the model
		/** @var ReleasesModel $model */
		$model = $this->getModel();

		/** @var SiteApplication $app */
		$app = Factory::getApplication();

		$this->items = $model->getItems();

		// Get the ordering
		$this->order     = $model->getState('list.order', 'id');
		$this->order_Dir = $model->getState('list.direction', 'desc');

		// Pass page params
		$this->pagination = $model->getPagination();
		$this->params     = $app->getParams();
		$this->Itemid     = $app->input->getInt('Itemid', 0);
		$this->menu       = $app->getMenu()->getActive();

		// Adapt pagination
		$this->pagination->setAdditionalUrlParam('option', 'com_ars');
		$this->pagination->setAdditionalUrlParam('view', 'items');
		$this->pagination->setAdditionalUrlParam('layout', 'modal');
		$this->pagination->setAdditionalUrlParam('tmpl', 'component');
		$this->pagination->setAdditionalUrlParam('Itemid', '');

		$this->document
			->addScriptOptions('ars.itemsProxyCallback', $this->modalFunction);
	}
}