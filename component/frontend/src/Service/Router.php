<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Service;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Akeeba\Component\ARS\Administrator\Table\UpdatestreamTable;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class Router extends RouterView
{
	use MVCFactoryAwareTrait;
	use DatabaseAwareTrait;

	private const OLD_VIEW_MAP = [
		'category'         => 'releases',
		'release'          => 'items',
		'downloadidlabels' => 'dlidlabels',
		'downloadidlabel'  => 'dlidlabel',
		'latests'          => 'latest',
		'updates'          => 'update',
	];

	/**
	 * @var array
	 */
	private static $releaseToCategory;

	/**
	 * @var array
	 */
	private static $itemToRelease;

	public function __construct(?SiteApplication $app = null, ?AbstractMenu $menu = null, ?CategoryFactoryInterface $categoryFactory = null, ?DatabaseInterface $db = null)
	{
		if ($db instanceof DatabaseInterface)
		{
			$this->setDatabase($db);
		}

		$categories = new RouterViewConfiguration('categories');
		$categories->addLayout('repository');
		$categories->addLayout('normal');
		$categories->addLayout('bleedingedge');
		$this->registerView($categories);

		$releases = new RouterViewConfiguration('releases');
		$releases->setKey('category_id');
		$releases->setParent($categories, 'category_id');
		$this->registerView($releases);

		$items = new RouterViewConfiguration('items');
		$items->setKey('release_id');
		$items->setParent($releases, 'category_id');
		$items->addLayout('default');
		$items->addLayout('modal');
		$this->registerView($items);

		$item = new RouterViewConfiguration('item');
		$item->setKey('item_id');
		$item->setParent($items, 'release_id');
		$this->registerView($item);

		$latest = new RouterViewConfiguration('latest');
		$this->registerView($latest);

		$dlidlabels = new RouterViewConfiguration('dlidlabels');
		$this->registerView($dlidlabels);

		$dlidlabel = new RouterViewConfiguration('dlidlabel');
		$dlidlabel->setKey('id');
		$dlidlabel->setParent($dlidlabels, 'id');
		$this->registerView($dlidlabel);

		$newdlidlabel = new RouterViewConfiguration('newdlidlabel');
		$newdlidlabel
			->setParent($dlidlabels);
		$this->registerView($newdlidlabel);

		$update = new RouterViewConfiguration('update');
		$update->setKey('stream_id');
		$update->addLayout('all');
		$update->addLayout('category');
		$update->addLayout('stream');
		$update->addLayout('ini');
		$update->addLayout('download');
		$this->registerView($update);

		// Migrate legacy menu items
		$allItems = $menu->getItems('component_id', ComponentHelper::getComponent('com_ars')->id);
		array_walk($allItems, [$this, 'migrateMenuItem']);

		parent::__construct($app, $menu);

		// The menu rules are fucking broken!
		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/** @inheritDoc */
	public function preprocess($query)
	{
		$query = parent::preprocess($query);

		// Don't let the controller be set in a SEF URL.
		if (isset($query['controller']))
		{
			unset ($query['controller']);
		}

		// Support viewName.taskName tasks.
		if (isset($query['task']) && (strpos($query['task'], '.') !== false))
		{
			[$view, $task] = $query['task'];

			$query['view'] = $view;
			$query['task'] = $task;
		}

		// Convert the view name, taking into account the ones used in older versions of the component.
		if (isset($query['view']))
		{
			$query['view'] = $this->translateOldViewName($query['view']);
		}

		// Get the default and current Itemid
		$activeMenuItem   = $this->menu->getActive();
		$defaultMenuItem  = $this->menu->getDefault();
		$defaultItemid    = $defaultMenuItem ? $defaultMenuItem->id : 0;
		$currentItemid    = $activeMenuItem ? $activeMenuItem->id : $defaultItemid;
		$currentComponent = $activeMenuItem ? $activeMenuItem->component : null;
		$queryItemId = $query['Itemid'] ?? null;
		$queryItemId = ($queryItemId !== null) ? intval($queryItemId) : null;

		/**
		 * Joomla 4 always adds the current (or default) menu item's Itemid. We need to remove it when it's not the
		 * right Item ID to use for the URL.
		 *
		 * The CORRECT way to do it is the $unsetItemIdCorrect method where we check if the item ID is the default OR
		 * if it's the currently selected item **BUT** the currently selected item is not a com_ars component.
		 *
		 * However, we chose not to use this method because ARS is primarily made for our own use and there is a quirk
		 * in our site. We have a top-level Download menu item which is the custom repository view. To make it possible
		 * to list all software, even what is not included in the custom repository view, we have a menu item under it
		 * which displays the normal releases.
		 *
		 * Because of this, we'd get two different URLs per software: /download/software.html if it's linked from the
		 * custom repository page and /download/official/software.html if it's linked from the normal releases page. We
		 * do not want that. So we use the "wrong" way to remove the Itemid in $unsetItemIdWrong where we DO NOT check
		 * if the current menu item is a com_ars menu item.
		 *
		 * In case someone wants to use this software on their own site we have added a component option to control it,
		 * “Use ItemID to build SEF URLs” (router_itemid_behaviour). Set it to “Yes” on your sites.
		 */

		$unsetItemIdCorrect = $queryItemId == $defaultItemid ||
			($queryItemId == $currentItemid && $currentComponent !== 'com_ars');
		$unsetItemIdWrong   = $queryItemId == $defaultItemid || $queryItemId == $currentItemid;

		$unsetItemId = (ComponentHelper::getParams('com_ars')->get('router_itemid_behaviour', '0') == 0)
			? $unsetItemIdWrong
			: $unsetItemIdCorrect;

		// Joomla 4 always adds the current (or default) menu item's Itemid. I don't want it, it makes for wonky URLs.
		if ($unsetItemId)
		{
			unset($query['Itemid']);
		}

		/**
		 * Set the parent IDs for the Category --> Release --> Item hierarchy.
		 *
		 * This is required for RouterView to figure out the correct hierarchy and create the correct SEF URLs. This
		 * means that an item link needs to provide the item_id, release_id and category_id. If it doesn't, we need to
		 * fill in the blanks.
		 *
		 * Here are the views in play:
		 * - "categories" is a root node. Nothing to do.
		 * - "category" maps to "releases".
		 * - "releases" already has the category_id.
		 * - "release" maps to "items".
		 * - "items" has a "release_id" but is missing the category_id.
		 * - "item" has an "item_id" but is missing the release_id and the category_id
		 */
		switch ($query['view'] ?? 'categories')
		{
			case 'newdlidlabel':
				$query['Itemid'] = $query['Itemid']
					?? $this->getItemIdForView($query['view'] ?? null)
					?? $this->getItemIdForView('dlidlabels')
					?? $this->getItemIdForView('dlidlabel')
					?? $this->getItemIdForRepository()
					?? $currentItemid ?? $defaultItemid;
				break;

			case 'dlidlabel':
			case 'dlidlabels':
				$altView = ($query['view'] === 'dlidlabel') ? 'dlidlabels' : 'dlidlabel';

				$query['Itemid'] = $query['Itemid']
					?? $this->getItemIdForView($query['view'] ?? null)
					?? $this->getItemIdForView($altView)
					?? $this->getItemIdForRepository()
					?? $currentItemid ?? $defaultItemid;
				break;

			case 'categories':
				$query['Itemid'] = $query['Itemid']
					?? $this->getItemIdForRepository($query['layout'] ?? null)
					?? $currentItemid ?? $defaultItemid;
				break;

			case 'releases':
				$query['Itemid'] = $query['Itemid']
					?? $this->getItemIdForCategory($query['category_id'] ?? null)
					?? $this->getItemIdForRepository()
					?? $currentItemid ?? $defaultItemid;
				break;

			case 'items':
				$query['category_id'] = ($query['category_id'] ?? 0) ?: $this->getCategoryForRelease($query['release_id'] ?? null) ?: 0;

				$query['Itemid']      = $query['Itemid']
					?? $this->getItemIdForRelease($query['release_id'] ?? null)
					?? $this->getItemIdForCategory($query['category_id'])
					?? $this->getItemIdForRepository()
					?? $currentItemid ?? $defaultItemid;
				break;

			case 'item':
				$query['release_id']  = ($query['release_id'] ?? 0) ?: $this->getReleaseForItem($query['item_id']);
				$query['category_id'] = ($query['category_id'] ?? 0) ?: $this->getCategoryForRelease($query['release_id']);

				if (($query['task'] ?? '') === 'download' && ($query['format'] ?? '') === 'raw')
				{
					unset($query['task']);
				}

				$query['Itemid'] = $query['Itemid']
					?? $this->getItemIdForItem($query['item_id'] ?? null)
					?? $this->getItemIdForRelease($query['release_id'] ?? null)
					?? $this->getItemIdForCategory($query['category_id'] ?? null)
					?? $this->getItemIdForRepository()
					?? $currentItemid ?? $defaultItemid;

				break;

			default:
				$query['Itemid'] = $query['Itemid']
					?? $this->getItemIdForView($query['view'] ?? null)
					?? $this->getItemIdForRepository()
					?? $currentItemid ?? $defaultItemid;
				break;
		}

		return $query;
	}

	public function getDlidlabelSegment($dlidlabelId, $query)
	{
		return [$dlidlabelId];
	}

	public function getDlidlabelId($segment, $query)
	{
		$segment = is_numeric($segment) ? (int) $segment : null;

		return (is_int($segment) && ($segment > 0)) ? $segment : false;
	}

	public function getReleasesSegment($category_id, $query)
	{
		/** @var CategoryTable $category */
		$category = $this->getMVCFactory()->createTable('Category', 'Administrator');

		if (!$category->load((int) $category_id))
		{
			return [];
		}

		return [$category->alias];
	}

	public function getReleasesId($segment, $query)
	{
		$db  = $this->getDatabase();
		$sql = $db->getQuery(true)
		          ->select($db->quoteName('id'))
		          ->from($db->quoteName('#__ars_categories'))
		          ->where($db->quoteName('alias') . ' = :alias')
		          ->bind(':alias', $segment);

		return $db->setQuery($sql)->loadResult() ?: false;
	}

	public function getItemsSegment($release_id, $query)
	{
		/** @var ReleaseTable $release */
		$release = $this->getMVCFactory()->createTable('Release', 'Administrator');

		if (!$release->load((int) $release_id))
		{
			return [];
		}

		return [$release->alias];
	}

	public function getItemsId($segment, $query)
	{
		$catId = $query['category_id'] ?? null;
		$db    = $this->getDatabase();
		$sql   = $db->getQuery(true)
		            ->select($db->quoteName('id'))
		            ->from($db->quoteName('#__ars_releases'))
		            ->where($db->quoteName('alias') . ' = :alias')
		            ->bind(':alias', $segment);

		if ($catId)
		{
			$sql->where($db->quoteName('category_id') . ' = :cat_id')
			    ->bind(':cat_id', $catId, ParameterType::INTEGER);
		}

		return $db->setQuery($sql)->loadResult() ?: false;
	}

	public function getItemSegment($item_id, $query)
	{
		/** @var ItemTable $item */
		$item = $this->getMVCFactory()->createTable('Item', 'Administrator');

		if (!$item->load((int) $item_id))
		{
			return [];
		}

		return [$item->alias];
	}

	public function getItemId($segment, $query)
	{
		$releaseId = $query['release_id'] ?? null;
		$db        = $this->getDatabase();
		$sql       = $db->getQuery(true)
		                ->select($db->quoteName('id'))
		                ->from($db->quoteName('#__ars_items'))
		                ->where($db->quoteName('alias') . ' = :alias')
		                ->bind(':alias', $segment);

		if ($releaseId)
		{
			$sql->where($db->quoteName('release_id') . ' = :release_id')
			    ->bind(':release_id', $releaseId, ParameterType::INTEGER);
		}

		return $db->setQuery($sql)->loadResult() ?: false;
	}

	public function getUpdateSegment($id, $query)
	{
		/** @var UpdatestreamTable $update */
		$update = $this->getMVCFactory()->createTable('Updatestream', 'Administrator');

		if (!$update->load((int) $id))
		{
			return [];
		}

		return [$update->alias];
	}

	public function getUpdateId($segment, $query)
	{
		$db  = $this->getDatabase();
		$sql = $db->getQuery(true)
		          ->select($db->quoteName('id'))
		          ->from($db->quoteName('#__ars_items'))
		          ->where($db->quoteName('alias') . ' = :alias')
		          ->bind(':alias', $segment);

		return $db->setQuery($sql)->loadResult() ?: false;
	}

	public function getUpdatesSegment($id, $query)
	{
		return $this->getUpdateSegment($id, $query);
	}

	public function getUpdatesId($segment, $query)
	{
		return $this->getUpdateId($segment, $query);
	}

	/**
	 * Get the menu item ID linked to a specific Item download. NULL if there is no such menu item.
	 *
	 * @param   int  $id  The Item id to download
	 *
	 * @return  int|null
	 * @since   7.0.7
	 */
	private function getItemIdForItem(?int $id): ?int
	{
		if (empty($id))
		{
			return null;
		}

		foreach ($this->menu->getItems('component_id', ComponentHelper::getComponent('com_ars')->id) as $menu)
		{
			if (($menu->query['view'] ?? '') === 'item' && ($menu->query['item_id'] ?? '') == $id)
			{
				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Get the menu item ID linked to a specific items listing. NULL if there is no such menu item.
	 *
	 * @param   int  $id  The release ID to list items for
	 *
	 * @return  int|null
	 * @since   7.0.7
	 */
	private function getItemIdForRelease(?int $id): ?int
	{
		if (empty($id))
		{
			return null;
		}

		foreach ($this->menu->getItems('component_id', ComponentHelper::getComponent('com_ars')->id) as $menu)
		{
			if (($menu->query['view'] ?? '') === 'items' && ($menu->query['release_id'] ?? '') == $id)
			{
				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Get the menu item ID linked to a specific releases listing. NULL if there is no such menu item.
	 *
	 * @param   int  $id  The category ID to list releases for
	 *
	 * @return  int|null
	 * @since   7.0.7
	 */
	private function getItemIdForCategory(?int $id): ?int
	{
		if (empty($id))
		{
			return null;
		}

		foreach ($this->menu->getItems('component_id', ComponentHelper::getComponent('com_ars')->id) as $menu)
		{
			if (($menu->query['view'] ?? '') === 'releases' && ($menu->query['category_id'] ?? '') == $id)
			{
				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Get the menu item ID for the respository page. NULL if there is no such menu item.
	 *
	 * @param   string|null  $layout  Optional layout to look for.
	 *
	 * @return  int|null
	 * @since   7.0.0
	 */
	private function getItemIdForRepository(?string $layout = null): ?int
	{
		foreach ($this->menu->getItems('component_id', ComponentHelper::getComponent('com_ars')->id) as $menu)
		{
			if (($menu->query['view'] ?? '') === 'categories')
			{
				if ($layout !== null && ($menu->query['layout'] ?? null) != $layout)
				{
					continue;
				}

				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Get the menu item ID for a specific ARS view. NULL if there is no such menu item.
	 *
	 * @param   string|null  $viewName  The name of the view to search for
	 *
	 * @return  int|null
	 * @since   7.0.7
	 */
	private function getItemIdForView(?string $viewName): ?int
	{
		if (empty($viewName))
		{
			return null;
		}

		foreach ($this->menu->getItems('component_id', ComponentHelper::getComponent('com_ars')->id) as $menu)
		{
			if (($menu->query['view'] ?? '') === $viewName)
			{
				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Translates view names from older versions of the component to the ones currently in use.
	 *
	 * @param   string  $oldViewName
	 *
	 * @return  string
	 */
	private function translateOldViewName(string $oldViewName): string
	{
		$oldViewName = strtolower($oldViewName);

		return self::OLD_VIEW_MAP[$oldViewName] ?? $oldViewName;
	}

	/**
	 * Backwards compatibility for older versions of the component.
	 *
	 * 1. Older versions had Formal case views (e.g. Categories instead of categories) which cause the Joomla View
	 *    Router to choke and die when building and parsing routes. This fixes that problem.
	 *
	 * 2. Much older versions have used the view names category (instead of releases), release (instead of items) etc.
	 *    This will transparently convert the view name of existing menu items to the new view names.
	 *
	 * This method must be used TWICE in a router:
	 * a. Building a route, if there is a detected menu item; and
	 * b. Parsing a route, if there is an active menu item.
	 *
	 * @param   MenuItem|null  $item  The menu item to address or null if there's no menu item.
	 */
	private function migrateMenuItem(?MenuItem $item): void
	{
		if (
			empty($item)
			|| ($item->component !== 'com_' . $this->getName())
			|| empty($item->query['view'] ?? '')
		)
		{
			return;
		}

		// Convert the view name
		$item->query['view'] = $this->translateOldViewName($item->query['view']);

		// Migration: "Category" view used to set catid instead of id
		switch ($item->query['view'])
		{
			case 'releases':
				// Releases view used to define catid instead of category_id
				$item->query['category_id'] = ($item->query['category_id'] ?? 0)
					?: ($item->query['catid'] ?? 0)
						?: $item->getParams()->get('catid')
							?: 0;
				break;

			case 'items':
				// Items view used to define relid instead of release_id
				$item->query['release_id'] = ($item->query['release_id'] ?? 0)
					?: ($item->query['relid'] ?? 0)
						?: $item->getParams()->get('relid')
							?: 0;
				break;

			case 'update':
				// Update view used to define streamid instead of stream_id
				$item->query['stream_id'] = ($item->query['stream_id'] ?? 0)
					?: ($item->query['streamid'] ?? 0)
						?: $item->getParams()->get('streamid')
							?: 0;
				break;
		}
	}

	private function getCategoryForRelease($id): ?int
	{
		if (!is_array(self::$releaseToCategory))
		{
			self::$releaseToCategory = $this->getReleaseToCategoryMap();
		}

		return self::$releaseToCategory[$id] ?? null;
	}

	private function getReleaseForItem($id): ?int
	{
		if (!is_array(self::$itemToRelease))
		{
			self::$itemToRelease = $this->getItemToReleaseMap();
		}

		return self::$itemToRelease[$id] ?? null;
	}

	private function getReleaseToCategoryMap(): array
	{
		$db  = $this->getDatabase();
		$sql = $db->getQuery(true)
		          ->select([
			          $db->quoteName('id'),
			          $db->quoteName('category_id'),
		          ])
		          ->from($db->quoteName('#__ars_releases'));

		return $db->setQuery($sql)->loadAssocList('id', 'category_id') ?? [];
	}

	private function getItemToReleaseMap(): array
	{
		$db  = $this->getDatabase();
		$sql = $db->getQuery(true)
		          ->select([
			          $db->quoteName('id'),
			          $db->quoteName('release_id'),
		          ])
		          ->from($db->quoteName('#__ars_items'));

		return $db->setQuery($sql)->loadAssocList('id', 'release_id') ?? [];
	}

}