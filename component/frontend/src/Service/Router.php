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
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Model\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;

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

	public function __construct(SiteApplication $app = null, AbstractMenu $menu = null, DatabaseInterface $db, MVCFactory $factory)
	{
		$this->setDbo($db);
		$this->setMVCFactory($factory);

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

		parent::__construct($app, $menu);

		// The menu rules are fucking broken!
		//$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	public function build(&$query)
	{
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

		// Lowercase the menu item's view, if defined; addresses Formal case views in the previous versions.
		$item = $this->menu->getItem($query['Itemid'] ?? null) ?: null;
		$this->migrateMenuItem($item);

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
		switch (($query['view'] ?? ''))
		{
			case 'items':
				$query['category_id'] = ($query['category_id'] ?? null) ?: $this->getCategoryForRelease($query['release_id'] ?? 0);
				break;

			case 'item':
				$query['release_id']  = ($query['release_id'] ?? null) ?: $this->getReleaseForItem($query['item_id']);
				$query['category_id'] = ($query['category_id'] ?? null) ?: $this->getCategoryForRelease($query['release_id']);
				break;
		}

		return parent::build($query);
	}

	public function parse(&$segments)
	{
		// Address old versions' view names
		$active = $this->menu->getActive() ?: null;
		$this->migrateMenuItem($active);

		$query = parent::parse($segments);

		if (isset($query['view']))
		{
			$query['view'] = $this->translateOldViewName($query['view']);
		}

		return $query;
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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__ars_categories'))
			->where($db->quoteName('alias') . ' = :alias')
			->bind(':alias', $segment);

		return $db->setQuery($query)->loadResult() ?: false;
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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('alias') . ' = :alias')
			->bind(':alias', $segment);

		return $db->setQuery($query)->loadResult() ?: false;
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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__ars_items'))
			->where($db->quoteName('alias') . ' = :alias')
			->bind(':alias', $segment);

		return $db->setQuery($query)->loadResult() ?: false;
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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__ars_items'))
			->where($db->quoteName('alias') . ' = :alias')
			->bind(':alias', $segment);

		return $db->setQuery($query)->loadResult() ?: false;
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
				$item->query['category_id'] = $item->query['category_id'] ?? $item->query['catid'] ?? 0;
				break;

			case 'items':
				// Items view used to define relid instead of release_id
				$item->query['release_id'] = $item->query['release_id'] ?? $item->query['relid'] ?? 0;
				break;

			case 'update':
				// Update view used to define streamid instead of stream_id
				$item->query['stream_id'] = $item->query['stream_id'] ?? $item->query['streamid'] ?? 0;
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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('id'),
				$db->quoteName('category_id'),
			])
			->from($db->quoteName('#__ars_releases'));

		return $db->setQuery($query)->loadAssocList('id', 'category_id') ?? [];
	}

	private function getItemToReleaseMap(): array
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('id'),
				$db->quoteName('release_id'),
			])
			->from($db->quoteName('#__ars_items'));

		return $db->setQuery($query)->loadAssocList('id', 'release_id') ?? [];
	}

}