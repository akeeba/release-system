<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Helper;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

class Breadcrumbs
{
	/**
	 * Adds the repository root to the breadcrumbs pathway
	 *
	 * @param   string|null  $repoType
	 *
	 * @throws  Exception
	 */
	public static function addRepositoryRoot(?string $repoType = ''): void
	{
		/** @var SiteApplication $app */
		$app = Factory::getApplication();

		$menus    = $app->getMenu();
		$menuitem = $menus->getActive();
		$repoType = $repoType ?? '';

		$rootName = null;

		$rootViewNames = ['browse', 'categories'];

		if (is_object($menuitem) && in_array(strtolower($menuitem->query['view']), $rootViewNames))
		{
			return;
		}

		// Preferably find a menu item linking to a specific repository type
		$itemId   = null;
		$allItems = $menus->getItems('type', 'component', false);

		foreach ($allItems as $item)
		{
			$qOption = $item->query['option'] ?? '';
			$qView   = $item->query['view'] ?? '';

			if (!($item->published ?? true) || $qOption !== 'com_ars' || !in_array(strtolower($qView), $rootViewNames))
			{
				continue;
			}

			if (($item->query['layout'] ?? '') == $repoType)
			{
				$itemId   = $item->id;
				$rootName = $item->title;
				$rootURI  = Route::_($item->link . '&Itemid=' . $itemId);

				break;
			}

			if ((($item->query['layout'] ?? '') == 'repository') && empty($itemId))
			{
				$itemId   = $item->id;
				$rootName = $item->title;
				$rootURI  = Route::_($item->link . '&Itemid=' . $itemId);
			}
		}

		if (!is_null($rootName) && isset($rootURI))
		{
			$app->getPathway()->addItem($rootName, $rootURI);
		}
	}

	/**
	 * Adds an ARS category to the breadcrumbs pathway
	 *
	 * @param   int     $id    Category ID to add
	 * @param   string  $name  The name in the pathway
	 *
	 * @throws Exception
	 */
	public static function addCategory(?int $id, ?string $name): void
	{
		/** @var SiteApplication $app */
		$app      = Factory::getApplication();
		$menus    = $app->getMenu();
		$menuitem = $menus->getActive();
		$id       = $id ?? 0;
		$name     = $name ?? '';

		$releasesViews = ['category', 'releases'];

		if (is_object($menuitem) && in_array(strtolower($menuitem->query['view']), $releasesViews))
		{
			return;
		}

		// Preferably find a menu item linking to a specific repository type
		$itemId   = null;
		$allItems = $menus->getItems('type', 'component', false);

		if (empty($allItems))
		{
			return;
		}

		$rootName = null;

		foreach ($allItems as $item)
		{
			$qOption = $item->query['option'] ?? '';
			$qView   = $item->query['view'] ?? '';

			if (!($item->published ?? true) || $qOption != 'com_ars' || !in_array($qView, $releasesViews))
			{
				continue;
			}

			$params = $item->getParams();
			$params = is_object($params) ? $params : new Registry($params);

			$catId = $params->get('category_id', 0) ?: $params->get('catid', 0);

			if ($catId == $id)
			{
				$itemId   = $item->id;
				$rootName = $item->title;
				$rootURI  = Route::_($item->link . '&Itemid=' . $itemId);
			}
		}

		if (is_null($itemId))
		{
			$input  = Factory::getApplication()->input;
			$itemId = $input->getInt('Itemid', null);
			$itemId = empty($itemId) ? '' : '&Itemid=' . $itemId;

			$rootName = $name;
			$rootURI  = Route::_('index.php?option=com_ars&view=releases&category_id=' . $id . $itemId);
		}

		if (!is_null($rootName) && isset($rootURI))
		{
			$app->getPathway()->addItem($rootName, $rootURI);
		}
	}

	/**
	 * Adds an ARS release to the breadcrumbs pathway
	 *
	 * @param   int     $id    Release ID to add
	 * @param   string  $name  The name in the pathway
	 *
	 * @throws Exception
	 */
	public static function addRelease(int $id, string $name): void
	{
		/** @var SiteApplication $app */
		$app      = Factory::getApplication();
		$menus    = $app->getMenu();
		$menuitem = $menus->getActive();

		$itemsViews = ['release', 'items'];

		if (is_object($menuitem) && in_array(strtolower($menuitem->query['view']), $itemsViews))
		{
			return;
		}

		// Preferably find a menu item linking to a specific repository type
		$itemId   = null;
		$allItems = $menus->getItems('type', 'component', false);

		foreach ($allItems as $item)
		{
			$qOption = $item->query['option'] ?? '';
			$qView   = $item->query['view'] ?? '';
			$qLayout = $item->query['layout'] ?? '';

			if (!($item->published ?? true) || $qOption != 'com_ars' || $qView != 'release')
			{
				continue;
			}

			$params = $item->getParams();
			$params = is_object($params) ? $params : new Registry($params);

			$relId = $params->get('release_id', 0) ?: $params->get('relid', 0);

			if ($relId == $id)
			{
				$itemId   = $item->id;
				$rootName = $item->title;
				$rootURI  = Route::_($item->link . '&Itemid=' . $itemId);
			}
		}

		if (is_null($itemId))
		{
			$input  = Factory::getApplication()->input;
			$itemId = $input->getInt('Itemid', null);
			$itemId = empty($itemId) ? '' : '&Itemid=' . $itemId;

			$rootName = $name;
			$rootURI  = Route::_('index.php?option=com_ars&view=Items&release_id=' . $id . $itemId);
		}

		if (isset($rootName) && isset($rootURI))
		{
			$app->getPathway()->addItem($rootName, $rootURI);
		}
	}
}
