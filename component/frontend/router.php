<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Model\Releases;
use Akeeba\ReleaseSystem\Site\Dispatcher\Dispatcher;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\UpdateStreams;
use FOF30\Container\Container;
use FOF30\Model\DataModel;
use FOF30\Model\DataModel\Exception\RecordNotLoaded;
use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Menu\MenuItem;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class ArsRouter extends RouterBase
{
	private const DEFAULT_VIEW = 'Categories';

	private const VALID_VIEWS = [
		'Categories', 'Releases', 'Items', 'Item', 'Latest', 'Update', 'DownloadIDLabel', 'DownloadIDLabels',
	];

	/**
	 * List of file extensions which can be used as a Joomla 'format' parameter, being equivalent to the "raw" view.
	 *
	 * This list must not include any extensions potentially executable by the server to avoid any mishaps.
	 *
	 * The idea is to include a number of extensions commonly used with archive files, installable packages on different
	 * OS and document files.
	 */
	private const ACCEPTED_EXTENSIONS = [
		'zip', 'tar', 'gz', 'tgz', 'bz2', 'tbz', 'xz', 'txz', 'tar', 'rar', '7z',
		'exe', 'msi', 'msp', 'cab', 'dmg', 'pkg', 'rpm', 'deb',
		'pdf', 'epub', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'fodt', 'ods', 'fods', 'odp', 'fodp',
		'odg', 'fodg', 'odf',
	];

	/**
	 * The component container
	 *
	 * @var  Container|null
	 */
	private $container;

	private $modelCache = [
		'Categories' => [],
		'Releases'   => [],
		'Items'      => [],
	];

	/**
	 * Generic method to preprocess a URL.
	 *
	 * Its job is to analyze the query string parameters of a non-SEF URLs and come up with a suitable Itemid. If
	 * necessary, it can also modify the query string parameters, e.g. if something is missing.
	 *
	 * @param   array  $query  An associative array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   5.1.0
	 */
	public function preprocess($query): array
	{
		// Let's pluck some information out of the non-SEF URL's request parameters
		$itemId = $this->getAndPop($query, 'Itemid');
		$view   = $this->getAndPop($query, 'view');
		$format = $this->getAndPop($query, 'format', 'html');

		// If we have a legacy view we need its new name for our code to work.
		if (!empty($view))
		{
			$view = $this->translateLegacyView($view);
		}

		// If there's no Itemid we will try to use the active menu item's ID
		if (empty($itemId))
		{
			$activeMenuItem = $this->menu->getActive();
			$itemId         = (is_object($activeMenuItem) && property_exists($activeMenuItem, 'id'))
				? $activeMenuItem->id
				: null;
		}

		// Try to load the presumptive menu item object if we have an Itemid
		$menuItem = null;

		if (!empty($itemId))
		{
			$menuItem = $this->menu->getItem($itemId);
		}

		// Sanity check: the component must match
		if (!empty($menuItem))
		{
			if ($menuItem->component != $this->getContainer()->componentName)
			{
				$menuItem = null;
			}
		}

		// Sanity check: the view name must be something supported by my component
		if (!empty($menuItem))
		{
			$menuView = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);

			if (!in_array($menuView, self::VALID_VIEWS))
			{
				$menuItem = null;
			}
			/**
			 * I may have a non-SEF URL which does not specify a view name but does specify an Itemid.
			 *
			 * In this case the effective view is the one defined in the menu item.
			 */
			elseif (empty($view))
			{
				$view = $this->translateLegacyView($menuView);
			}
		}

		// Sanity check: the menu item must be compatible with the requested View and record ID
		if (!empty($menuItem))
		{
			$menuItem = $this->validateMenuItem($menuItem, $view, $query);
		}

		// If we don't have a menu item we'll try to find a suitable one
		if (empty($menuItem))
		{
			$menuItem = $this->getMenuItemForView($view, $query);
		}

		// If we have a menu item pass its Itemid
		if (!empty($menuItem))
		{
			// Pass the Itemid
			$query['Itemid'] = $menuItem->id;

			// If the menu view and the requested view are different set the $query['view'] back to what it was before.
			$menuView = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);

			if ($menuView != $view)
			{
				$query['view'] = $view;
			}
		}
		// If I don't have a valid menu item remove the Itemid from the query and reset the view to its previous value.
		elseif (isset($query['Itemid']))
		{
			unset($query['Itemid']);

			// Pass a sane view name
			$query['view'] = empty($view) ? self::DEFAULT_VIEW : $view;
		}

		// Set the format, making sure it's something valid for the requested view
		$query['format'] = $this->getValidFormatForView($view, $format, $query);

		/**
		 * Only keep a non-default layout.
		 *
		 * For most views this means a layout other than 'default'.
		 *
		 * For the Update XML view no layout is allowed. It's set automatically based on the task.
		 */
		$layout = $this->getAndPop($query, 'layout');

		if (!empty($layout) && ($layout != 'default'))
		{
			$query['layout'] = $layout;
		}

		return $query;
	}

	/**
	 * Build a SEF URL
	 *
	 * This runs after self::preprocess() is done modifying the query. We MUST NOT change the Itemid here.
	 *
	 * @param   array  &$query  An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   5.1.0
	 */
	public function build(&$query): array
	{
		$segments = [];
		$Itemid   = $query['Itemid'] ?? null;

		/**
		 * Since Joomla has executed preprocess() we have a valid Itemid. If not, fall back to Joomla's ugly, default
		 * SEF routes (/component/ars/...)
		 */
		if (empty($Itemid))
		{
			return $segments;
		}

		$menuItem = $this->menu->getItem($Itemid);
		$mView    = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);
		$view     = $this->getAndPop($query, 'view', null);

		/**
		 * If there was no view in the non-SEF URL or the menu item and non-SEF view match we have nothing to do.
		 *
		 * However, this DOES NOT apply to the Updates view. The Updates view always needs segments.
		 */
		if (empty($view) || (($view != 'Update') && ($mView == $view)))
		{
			$query['Itemid'] = $Itemid;

			return $segments;
		}

		switch ($view)
		{
			case 'Releases':
				// The only case where view=Release and view!=mView is when mView == Categories
				$category_id = $this->getAndPop($query, 'category_id');
				$category    = $this->getModelObject('Categories', $category_id);

				if (!$category->getId())
				{
					// You are asking for a list of releases with an invalid category ID. Ka-boom.
					return $segments;
				}

				$segments[] = $category->alias;
				break;

			case 'Items':
			case 'Item':
				if ($view === 'Item')
				{
					$item_id    = $this->getAndPop($query, 'id');
					$item_id    = $this->getAndPop($query, 'item_id', $item_id);
					$item       = $this->getModelObject('Items', $item_id);
					$release_id = $item->release_id;
				}
				else
				{
					$release_id = $this->getAndPop($query, 'release_id');
				}

				$release = $this->getModelObject('Releases', $release_id);

				if ($mView == 'Releases')
				{
					if (!$release->getId())
					{
						// You are asking for a list of items with an invalid release ID. Ka-boom.
						return $segments;
					}

					$segments[] = $release->alias;
				}
				else
				{
					$category = $this->getModelObject('Categories', $release->category_id);

					if (!$category->getId())
					{
						// You are asking for a list of items of a release with an invalid category ID. Ka-boom.
						return $segments;
					}

					$segments[] = $category->alias;
					$segments[] = $release->alias;
				}

				if ($view === 'Item')
				{
					$segments[] = $item->alias;

					// The default task for the Item view is 'download' and must be removed from the query.
					$task = $this->getAndPop($query, 'task', 'download');

					if ($task != 'download')
					{
						$query['task'] = $task;
					}
				}

			break;

			case 'DownloadIDLabels':
			case 'DownloadIDLabel':
				if ($mView != 'DownloadIDLabels')
				{
					// You should really have separate menu items for these views. This is a fugly solution!
					$segments[] = '__internal';
					$segments[] = $view;

					return $segments;
				}

				break;

			case 'Update':
				$id      = $this->getAndPop($query, 'id');
				$task    = $this->getAndPop($query, 'task');
				$layout  = $this->getAndPop($query, 'layout', $task);
				$mLayout = $menuItem->query['layout'] ?? null;

				if ($mView != $view)
				{
					// You should really have separate menu items for these views. This is a fugly solution!
					$segments[] = '__internal';
					$segments[] = $view;
				}

				if ($mLayout != $layout)
				{
					$segments[] = $layout;
				}

				switch ($layout)
				{
					case 'all':
					default:
						// No segment to add
						break;

					case 'category':
						$segments[] = $id;
						break;

					case 'stream':
					case 'ini':
					case 'download':
						$updateStream = $this->getModelObject('UpdateStreams', $id);
						$segments[]   = $updateStream->alias;
						break;
				}

				break;

			case 'Categories':
			case 'Latest':
			default:
				// You should really have separate menu items for these views. This is a fugly solution!
				$segments[] = '__internal';
				$segments[] = $view;

				return $segments;

				break;
		}

		return $segments;
	}

	/**
	 * Parse a SEF URL
	 *
	 * Based on the currently active menu item and the $segments array we reconstruct an array of query parameters.
	 *
	 * @param   array  &$segments  The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @since   5.1.0
	 */
	public function parse(&$segments): array
	{
		$query    = [];
		$menuItem = $this->menu->getActive();

		// This should never happen. Joomla should never call me without segments. But I can't rule out a future bug :)
		if (empty($segments))
		{
			return $query;
		}

		// Parsing a SEF route requires an active menu item. Without one we should just let it crash and burn.
		if (empty($menuItem))
		{
			return $query;
		}

		$mView = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);

		// Do we have our fugly fix for a missing menu item of a top-level view?
		if ($segments[0] === '__internal')
		{
			$junk          = array_shift($segments);
			$query['view'] = array_shift($segments);

			if ($query['view'] != 'Update')
			{
				return $query;
			}
		}

		switch ($mView)
		{
			// This is an Item view. The only segment is the Item alias.
			case 'Items':
				$item          = $this->getModelObject('Items', [
					'alias'      => array_shift($segments),
					'release_id' => $menuItem->query['release_id'] ?? null,
				]);
				$query['view'] = 'Item';
				$query['task'] = 'download';
				$query['id']   = $item->getId();
				break;

			// This is an Item or Items view depending on the number of segments.
			case 'Releases':
				$category_id  = $menuItem->query['category_id'] ?? null;
				$segmentCount = count($segments);

				if ($segmentCount === 2)
				{
					[$releaseAlias, $itemAlias] = $segments;

					$release = $this->getModelObject('Releases', [
						'alias'       => $releaseAlias,
						'category_id' => $category_id,
					]);

					$item = $this->getModelObject('Items', [
						'alias'      => $itemAlias,
						'release_id' => $release->getId(),
					]);

					$query['view'] = 'Item';
					$query['task'] = 'download';
					$query['id']   = $item->getId();
				}
				elseif ($segmentCount === 1)
				{
					[$releaseAlias] = $segments;

					$release = $this->getModelObject('Releases', [
						'alias'       => $releaseAlias,
						'category_id' => $category_id,
					]);

					$query['view']       = 'Items';
					$query['release_id'] = $release->getId();
				}

				break;

			// This is the Releases, Items or Item view depending on the number of segments.
			case 'Categories':
				$segmentCount = count($segments);
				$qView        = $query['view'] ?? null;

				if ($qView == 'Update')
				{
					/**
					 * Set the view and layout in the query and let this fall through to the next if-block after the
					 * case block.
					 *
					 * This trick allows me to handle an Update view no matter if the menu item is Update or Categories.
					 */
					$query['view']   = 'Update';
					$query['layout'] = array_shift($segments);
				}
				elseif ($segmentCount === 3)
				{
					[$categoryAlias, $releaseAlias, $itemAlias] = $segments;

					$category = $this->getModelObject('Categories', [
						'alias' => $categoryAlias,
					]);

					$release = $this->getModelObject('Releases', [
						'alias'       => $releaseAlias,
						'category_id' => $category->getId(),
					]);

					$item = $this->getModelObject('Items', [
						'alias'      => $itemAlias,
						'release_id' => $release->getId(),
					]);

					$query['view'] = 'Item';
					$query['task'] = 'download';
					$query['id']   = $item->getId();
				}
				elseif ($segmentCount === 2)
				{
					[$categoryAlias, $releaseAlias] = $segments;

					$category = $this->getModelObject('Categories', [
						'alias' => $categoryAlias,
					]);

					$release = $this->getModelObject('Releases', [
						'alias'       => $releaseAlias,
						'category_id' => $category->getId(),
					]);

					$query['view']       = 'Items';
					$query['release_id'] = $release->getId();
				}
				elseif ($segmentCount === 1)
				{
					[$categoryAlias] = $segments;

					$category = $this->getModelObject('Categories', [
						'alias' => $categoryAlias,
					]);

					$query['view']        = 'Releases';
					$query['category_id'] = $category->getId();
				}
				break;

			case 'Update':
				/**
				 * Set the view in the query and let this fall through to the next if-block after the case block.
				 *
				 * This trick allows me to handle an Update view no matter if the menu item is Update or Categories.
				 */
				$query['view'] = 'Update';
				break;
		}

		// Special handling for the Update view
		$qView = $query['view'] ?? self::DEFAULT_VIEW;

		if (($qView === 'Update') && !empty($segments))
		{
			switch ($query['layout'] ?? 'stream')
			{
				case 'all':
					// Do nothing. The main update stream doesn't have any arguments.
					break;

				case 'category':
					$query['id'] = array_pop($segments);
					break;

				case 'stream':
				case 'ini':
				case 'download':
				default:
					$stream      = $this->getModelObject('UpdateStreams', [
						'alias' => array_pop($segments),
					]);
					$query['id'] = $stream->getId();
					break;
			}
		}

		return $query;
	}

	/**
	 * Verifies we're using a valid format for the view and returns the most suitable format
	 *
	 * @param   string|null  $view    The name of the view
	 * @param   string       $format  The presumptive format from the non-SEF query string parameters
	 * @param   array        $query   The established query parameters so far
	 *
	 * @return  string  The most suitable format we should be using
	 *
	 * @since   5.1.0
	 */
	protected function getValidFormatForView(?string $view, string $format, array $query): string
	{
		switch ($view)
		{
			// This view supports JSON and RAW formats
			case 'Item':
				if (!in_array($format, ['json', 'raw']))
				{
					return 'html';
				}

				/**
				 * The raw format leads to .raw URLs. Let's convert them based on the extension of the downloaded file.
				 *
				 * This only applies for certain whitelisted, hardcoded extensions
				 */
				$item      = $this->getModelObject('Items', $query['id'] ?? null);
				$target    = ($item->type == 'file') ? $item->filename : $item->url;
				$bits      = explode('.', $target);
				$extension = strtolower((count($bits) > 1) ? array_pop($bits) : 'raw');

				if (in_array($extension, self::ACCEPTED_EXTENSIONS))
				{
					return $extension;
				}

				return 'raw';

				break;

			// These views support HTML and JSON formats
			case 'Categories':
			case 'Releases':
			case 'Items':
				if (!in_array($format, ['html', 'json']))
				{
					return 'html';
				}

				break;

			// These views support XML and INI formats
			case 'Update':
			case 'Updates':
				if (!in_array($format, ['xml', 'ini', 'raw']))
				{
					return 'xml';
				}

				break;

			// Everything else is HTML format only
			default:
				return 'html';

				break;
		}

		return $format;
	}

	/**
	 * Finds a suitable menu item for the given view and query string parameters
	 *
	 * @param   string  $view   The requested view in the non-SEF URL
	 * @param   array   $query  The non-SEF request parameters
	 *
	 * @return  MenuItem|null  A suitable menu item, null if none is a good fit.
	 *
	 * @since   5.1.0
	 */
	protected function getMenuItemForView(string &$view, array &$query): ?MenuItem
	{
		// Get the default menu item search options
		$queryOptions = [
			'option' => $this->getContainer()->componentName,
			'view'   => $view,
		];

		// If a specific language is requested add it to the menu item search options
		$language            = $this->getAndPop($query, 'language');
		$hasValidLangInQuery = !empty($language) && ($language != '*');

		if ($hasValidLangInQuery)
		{
			$queryOptions['lang'] = $language;
		}

		// Get the category, release or item ID from the query
		$id = $this->getAndPop($query, 'id');
		$id = $this->getAndPop($query, 'release_id', $id);
		$id = $this->getAndPop($query, 'category_id', $id);

		// If there is no ID for these views something has gone REALLY wrong...
		if (empty($id) && in_array($view, ['Releases', 'Items', 'Item']))
		{
			return null;
		}

		switch ($view)
		{
			/**
			 * The 'Releases' view needs a menu item for a specific category OR a menu item for Categories
			 */
			case 'Releases':
				// If no language was requested in the non-SEF URL use the language of the Category
				if (!$hasValidLangInQuery)
				{
					$category             = $this->getModelObject('Categories', $id);
					$queryOptions['lang'] = $category->language;
				}

				// Try to find the Releases view for the specific category
				$menuItem = $this->findMenu(array_merge($queryOptions, [
					'view'        => 'Releases',
					'category_id' => $id,
				]));

				// Fall back to legacy "category" view menu item
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'        => 'category',
						'category_id' => $id,
					]));

				// Fall back to the root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'Categories',
						'layout' => 'repository',
					]));

				// Fall back to the legacy root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'browses',
						'layout' => 'repository',
					]));

				// Fall back to the root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'Categories',
						'layout' => $this->getModelObject('Releases', $id)->type,
					]));

				// Fall back to the legacy root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'browses',
						'layout' => $this->getModelObject('Releases', $id)->type,
					]));
				// Pass back the view and ID to the query as needed
				$mView = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);

				if ($mView != $view)
				{
					$query['view']        = $view;
					$query['category_id'] = $id;
				}

				return $menuItem;

				break;

			/**
			 * The 'Items' view needs a menu item for a specific release OR a menu item for its parent category
			 * OR a menu item for Categories.
			 *
			 * The 'Item' view is the same but we're given the ID of an item, not a release, so we need to do some
			 * pre-processing first.
			 */
			case 'Items':
			case 'Item':
				// In a singular Item view fetch the release ID into $id so the rest of the code works
				if ($view == 'Item')
				{
					$id = $this->getModelObject('Items', $id)->release_id;
				}

				// If no language was requested in the non-SEF URL use the language of the Category
				if (!$hasValidLangInQuery)
				{
					$release              = $this->getModelObject('Releases', $id);
					$category             = $this->getModelObject('Categories', $release->id);
					$queryOptions['lang'] = $category->language;
				}

			// Try to find the Items view for the specific Release
			$menuItem = $this->findMenu(array_merge($queryOptions, [
				'view'       => 'Items',
				'release_id' => $id,
			]));

			// Fall back to legacy "release" view menu item
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'       => 'release',
					'release_id' => $id,
				]));

			// Fall back to Releases view for the specific category ID
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'        => 'Releases',
					'category_id' => $this->getModelObject('Releases', $id)->category_id,
				]));

			// Fall back to legacy "category" view for the specific category ID
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'        => 'category',
					'category_id' => $this->getModelObject('Releases', $id)->category_id,
				]));

			// Fall back to the root Categories menu item if necessary
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'   => 'Categories',
					'layout' => 'repository',
				]));

			// Fall back to the legacy root Categories menu item if necessary
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'   => 'browses',
					'layout' => 'repository',
				]));

			// Fall back to the root Categories menu item with a specific layout
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'   => 'Categories',
					'layout' => $release->type,
				]));

			// Fall back to the legacy root Categories menu item with a specific layout
			$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
					'view'   => 'browses',
					'layout' => $release->type,
				]));

			// Pass back the view and ID to the query as needed
			$mView = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);

			if ($mView != $view)
			{
				$query['view'] = $view;

				switch ($view)
				{
					case 'Item':
						$query['id'] = $id;
						break;

					case 'Items':
						$query['release_id'] = $id;
						break;

					case 'Releases':
						$query['category_id'] = $id;
						break;
				}
			}

			return $menuItem;

			break;

			/**
			 * The singular 'DownloadIDLabel' view always needs a menu item fow 'DownloadIDLabels'.
			 *
			 * Moreover, depending on the existence of a non-empty, non-zero ID its implied task can either be
			 * 'edit' or 'add'. Since we'll be replacing it with the plural view (DownloadIDLabels) we need to
			 * explicitly set the task, otherwise the view will break.
			 */
			case 'DownloadIDLabel':
				$view     = 'DownloadIDLabels';
				$menuItem = $this->findMenu(array_merge($queryOptions, [
					'view' => 'DownloadIDLabels',
				]));

				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'dlidlabels',
					]));

				// Fall back to the root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'Categories',
						'layout' => 'repository',
					]));

				// Fall back to the legacy root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'browses',
						'layout' => 'repository',
					]));

				// Fall back to the root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'Categories',
					]));

				// Fall back to the legacy root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'browses',
					]));

				// Set a missing task to 'edit' or 'add' depending on existence of an id parameter
				if (!empty($menuItem))
				{
					if (!empty($query['task'] ?? null))
					{
						$id            = $this->getAndPop($query, 'id');
						$query['task'] = 'add';

						if (!empty($id))
						{
							$query['task'] = 'edit';
							$query['id']   = $id;
						}
					}
				}

				return $menuItem;

				break;

			case 'Update':
				$task   = $this->getAndPop($query, 'task');
				$layout = $this->getAndPop($query, 'layout');

				if (!in_array($task, ['all', 'category', 'stream', 'ini', 'download']) && !empty($layout))
				{
					$task   = $layout;
					$layout = null;
				}

				$layout = null;

				// Menu items use layout, not task
				$queryOptions['layout'] = $task;

				if ($task != 'all')
				{
					// TODO This may not be correct for all tasks
					$queryOptions['id'] = $id;
				}

				// Find the most suitable menu item
				$menuItem = $this->findMenu($queryOptions);

				// Fallback to menu item with legacy view name
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'update',
					]));

				// Prepare for fallback to the main update menu link
				if (isset($queryOptions['id']))
				{
					unset($queryOptions['id']);
				}

				$queryOptions['layout'] = 'all';

				$menuItem = $menuItem ?? $this->findMenu($queryOptions);
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'update',
					]));

				// Prepare for fallback to main repo page
				unset($queryOptions['layout']);

				// Fall back to the root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'Categories',
						'layout' => 'repository',
					]));

				// Fall back to the legacy root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'browses',
						'layout' => 'repository',
					]));

				// Fall back to the root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'Categories',
					]));

				// Fall back to the legacy root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'browses',
					]));

				$query['layout'] = $task;

				if (!empty($id))
				{
					$query['id'] = $id;
				}

				return $menuItem;

				break;

			/**
			 * Everything else needs a menu item of the corresponding view.
			 *
			 * This covers the views: Categories, Latest, Update, DownloadIDLabels
			 *
			 * Note that the Categories menu is the root of the tree therefore it needs a menu item of its own
			 */
			default:
				// Find an up-to-date menu item
				$menuItem = $this->findMenu($queryOptions);

				// Find a legacy menu item
				$legacyViews = [
					'Categories'       => 'browse',
					'Releases'         => 'category',
					'Items'            => 'release',
					'Item'             => 'download',
					'Latest'           => 'latest',
					'Update'           => 'update',
					'DownloadIDLabel'  => 'dlidlabel',
					'DownloadIDLabels' => 'dlidlabels',
				];
				$altView     = $legacyViews[$queryOptions['view']] ?? $queryOptions['view'];

				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => $altView,
					]));

				// Fall back to the root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'Categories',
						'layout' => 'repository',
					]));

				// Fall back to the legacy root Categories menu item if necessary
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view'   => 'browses',
						'layout' => 'repository',
					]));

				// Fall back to the root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'Categories',
					]));

				// Fall back to the legacy root Categories menu item with a specific layout
				$menuItem = $menuItem ?? $this->findMenu(array_merge($queryOptions, [
						'view' => 'browses',
					]));

				if (($view == 'Update') && !empty($id))
				{
					$query['id'] = $id;
				}

				if (!empty($menuItem))
				{
					// If the implied default task 'browse' is present, remove it. Anything else is kept verbatim.
					$task = $this->getAndPop($query, 'task');

					if (!empty($task) && ($task != 'browse'))
					{
						$query['task'] = 'browse';
					}
				}

				return $menuItem;

				break;
		}
	}

	/**
	 * Validates that an existing menu item (based on Itemid) is usable for the non-SEF URL we're trying to route.
	 *
	 * @param   MenuItem     $menuItem  The menu item to validate
	 * @param   string|null  $view      The view requested in the non-SEF URL
	 * @param   array        $query     The non-SEF URL's request parameters
	 *
	 * @return  MenuItem|null  The validated menu item or NULL if it's not usable for the request
	 *
	 * @since   5.1.0
	 */
	protected function validateMenuItem(MenuItem $menuItem, ?string $view, array &$query): ?MenuItem
	{
		$mView = $this->translateLegacyView($menuItem->query['view'] ?? self::DEFAULT_VIEW);

		switch ($view)
		{
			case 'Releases':
				$id = $query['category_id'] ?? null;

				switch ($mView)
				{
					// Same view. Check that the category ID matches.
					case 'Releases':
						$mID = $menuItem->query['category_id'] ?? null;

						if ($id != $mID)
						{
							$menuItem = null;
						}
						break;

					// Root node. I can use it if it's the correct language.
					case 'Categories':
						// If the menu item is "All" languages I can use it.
						if ($menuItem->language == '*')
						{
							break;
						}

						$category = $this->getModelObject('Categories', $id);

						// If my Category is "All" languages I can use whichever menu item.
						if ($category->language == '*')
						{
							break;
						}

						// If the menu item and category languages differ I will have to find another menu item.
						if ($menuItem->language != $category->language)
						{
							$menuItem = null;
						}

						break;

					// Er, I can't use this view
					default:
						$menuItem = null;
						break;
				}

				return $menuItem;

			case 'Items':
				$id = $query['release_id'] ?? null;

				switch ($mView)
				{
					// Same view. Check that the release ID matches.
					case 'Items':
						$mID = $menuItem->query['release_id'] ?? null;

						if ($id != $mID)
						{
							$menuItem = null;
						}
						break;

					// Releases (of a category) view. Check that the category ID matches my release's category ID.
					case 'Releases':
						$mID = $menuItem->query['category_id'] ?? null;

						if (empty($id))
						{
							$menuItem = null;

							break;
						}

						$release = $this->getModelObject('Releases', $id);

						if ($release->getId() != $mID)
						{
							$menuItem = null;
						}

						break;

					// Root node. I can use it if it's the correct language.
					case 'Categories':
						// If the menu item is "All" languages I can use it.
						if ($menuItem->language == '*')
						{
							break;
						}

						$release  = $this->getModelObject('Releases', $id);
						$category = $this->getModelObject('Categories', $release->id);

						// If the menu item and category languages differ I will have to find another menu item.
						if ($menuItem->language != $category->language)
						{
							$menuItem = null;
						}

						break;

					// Er, I can't use this view
					default:
						$menuItem = null;
						break;
				}

				return $menuItem;

			case 'Item':
				$arsItemId = $query['id'] ?? null;
				$item      = $this->getModelObject('Items', $arsItemId);

				switch ($mView)
				{
					// Items (of a release) view. Check that the release ID matches.
					case 'Items':
						$mID = $menuItem->query['release_id'] ?? null;

						if ($item->release_id != $mID)
						{
							$menuItem = null;
						}
						break;

					// Releases (of a category) view. Check that the category ID matches my release's category ID.
					case 'Releases':
						$mID = $menuItem->query['category_id'] ?? null;

						if (empty($item->release_id))
						{
							$menuItem = null;

							break;
						}

						$release = $this->getModelObject('Releases', $item->release_id);

						if ($release->getId() != $mID)
						{
							$menuItem = null;
						}

						break;

					// Root node. I can use it if it's the correct language.
					case 'Categories':
						// If the menu item is "All" languages I can use it.
						if ($menuItem->language == '*')
						{
							break;
						}

						$release  = $this->getModelObject('Releases', $item->release_id);
						$category = $this->getModelObject('Categories', $release->id);

						// If the menu item and release languages differ I will have to find another menu item.
						if ($menuItem->language != $category->language)
						{
							$menuItem = null;
						}

						break;

					// Er, I can't use this view
					default:
						$menuItem = null;
						break;
				}

				return $menuItem;

			case 'Categories':
				// Match the layout, if one is set. The layout = repository is always allowed.
				$layout = $query['layout'] ?? null;

				if (empty($layout))
				{
					return $menuItem;
				}

				$mLayout = $menuItem->query['layout'] ?? 'repository';

				if (($mLayout != 'repository') && ($mLayout != $layout))
				{
					$menuItem = null;
				}
				break;

			case 'DownloadIDLabel':
			case 'DownloadIDLabels':
				// View must always be DownloadIDLabels
				if ($mView != 'DownloadIDLabels')
				{
					$menuItem = null;
				}
				break;

			case 'Update':
				// TODO Validate the Update menu item
				break;

			default:
				// The View must match
				if ($view != $mView)
				{
					$menuItem = null;
				}
				break;
		}

		return $menuItem;
	}

	/**
	 * Gets a temporary instance of the Akeeba Subscriptions container
	 *
	 * @return  Container
	 *
	 * @since   5.1.0
	 */
	private function getContainer(): Container
	{
		if (is_null($this->container))
		{
			$this->container = Container::getInstance('com_ars', [
				'tempInstance' => true,
			]);
		}

		return $this->container;
	}

	/**
	 * Finds a menu whose query parameters match those in $queryOptions
	 *
	 * @param   array  $queryOptions  The query parameters to look for
	 * @param   array  $params        The menu parameters to look for
	 *
	 * @return  null|MenuItem  Null if not found, or the menu item if we did find it
	 *
	 * @since   5.1.0
	 */
	private function findMenu(array $queryOptions = [], array $params = []): ?MenuItem
	{
		$menuitem = $this->menu->getActive();

		// First check the current menu item (fastest shortcut!)
		if (is_object($menuitem) && $this->checkMenu($menuitem, $queryOptions, $params))
		{
			return $menuitem;
		}

		foreach ($this->menu->getMenu() as $item)
		{
			if ($this->checkMenu($item, $queryOptions, $params))
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * Checks if a menu item conforms to the query options and parameters specified
	 *
	 * @param   MenuItem  $menu          A menu item
	 * @param   array     $queryOptions  The query options to look for
	 * @param   array     $params        The menu parameters to look for
	 *
	 * @return  bool
	 *
	 * @since   5.1.0
	 */
	private function checkMenu(MenuItem $menu, array $queryOptions, array $params = []): bool
	{
		// Test for menu item language
		if (isset($queryOptions['lang']))
		{
			$searchLanguage = $queryOptions['lang'];
			$searchLanguage = empty($searchLanguage) ? '*' : $searchLanguage;

			$menuLanguage = $menu->language ?? '*';
			$menuLanguage = empty($menuLanguage) ? '*' : $menuLanguage;

			unset($queryOptions['lang']);

			if (($searchLanguage != '*') && ($menuLanguage != '*') && ($menuLanguage != $searchLanguage))
			{
				return false;
			}
		}

		$query = $menu->query;

		foreach ($queryOptions as $key => $value)
		{
			// A null value was requested. Huh.
			if (is_null($value))
			{
				// If the key is set and is not empty it's not the menu item you're looking for
				if (isset($query[$key]) && !empty($query[$key]))
				{
					return false;
				}

				continue;
			}

			if (!isset($query[$key]))
			{
				return false;
			}

			if ($key == 'view')
			{
				// Treat views case-insensitive
				if (strtolower($query[$key]) != strtolower($value))
				{
					return false;
				}
			}
			elseif ($query[$key] != $value)
			{
				return false;
			}
		}

		if (empty($params))
		{
			return true;
		}

		$menuItemParams = $menu->getParams();
		$check          = $menuItemParams instanceof Registry ? $menuItemParams : $this->menu->getParams($menu->id);

		foreach ($params as $key => $value)
		{
			if (is_null($value))
			{
				continue;
			}

			if ($check->get($key) != $value)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the application's languages, in the preferred order for the current user.
	 *
	 * @return  string[]
	 *
	 * @since   5.1.0
	 */
	private function getApplicationLanguages(): array
	{
		$container      = $this->getContainer();
		$languages      = ['*'];
		$platform       = $container->platform;
		$isMultilingual = false;

		if (!$platform->isCli() && !$platform->isBackend() && !$platform->isApi())
		{
			$isMultilingual = method_exists($this->app, 'getLanguageFilter') ?
				$this->app->getLanguageFilter() : false;
		}

		if (!$isMultilingual)
		{
			return $languages;
		}

		// Get default site language
		$jLang = $this->app->getLanguage();

		return array_unique([
			$jLang->getTag(),
			$jLang->getDefault(),
			'*',
		]);
	}

	/**
	 * Return the view we're building a route from based on the query parameters in $query
	 *
	 * If $query['view'] is set we use it and we remove it from the $query array.
	 *
	 * Otherwise we check if there's an Itemid and use the view from that menu item.
	 *
	 * If this still fails we return self::DEFAULT_VIEW, close our eyes and hope for the best.
	 *
	 * @param   array  $query  Query parameters
	 *
	 * @return  string
	 *
	 * @since   5.1.0
	 */
	private function getViewFromQuery(array &$query): string
	{
		// If the query has a view use that one instead
		if (isset($query['view']) && !empty($query['view']))
		{
			$view = $query['view'];

			unset($query['view']);

			return $view;
		}

		// If we don't have an Itemid return the default view of the component
		if (!isset($query['Itemid']) || empty($query['Itemid']))
		{
			return self::DEFAULT_VIEW;
		}

		// Try to load the menu from Itemid
		$menu = $this->menu->getItem($query['Itemid']);

		// If loading the menu failed return the component's default view
		if (!is_object($menu) || ($menu->id != $query['Itemid']))
		{
			return self::DEFAULT_VIEW;
		}

		return $this->translateLegacyView($menu->query['view'] ?? self::DEFAULT_VIEW);
	}

	/**
	 * Translates ARS 1.x to 3.x view names to the new view names.
	 *
	 * This ensures menu items created in the olden days will still work without causing a minor site meltdown.
	 *
	 * @param   string  $view  The potentially legacy view name.
	 *
	 * @return  string
	 *
	 * @since   5.1.0
	 */
	private function translateLegacyView(string $view): string
	{
		/** @var Dispatcher $dispatcher */
		$dispatcher = $this->getContainer()->dispatcher;

		if (array_key_exists($view, $dispatcher->viewMap))
		{
			return $dispatcher->viewMap[$view];
		}

		return $view;
	}

	/**
	 * Get a value from a key-value array and eliminate the key from it
	 *
	 * If the key is not found in the array the default value is returned
	 *
	 * @param   array   $query    They key-value array
	 * @param   string  $key      The key to retrieve and eliminate from the array
	 * @param   mixed   $default  The default value to use if the key does not exist in the array.
	 *
	 * @return mixed The retrieved value (or the default, if the key was not present)
	 *
	 * @since   5.1.0
	 */
	private function getAndPop(array &$query, string $key, $default = null)
	{
		if (!isset($query[$key]))
		{
			return $default;
		}

		$value = $query[$key];

		unset($query[$key]);

		return $value;
	}

	/**
	 * Loads an ARS Category, Release or Item and returns its model object. Results are cached for performance.
	 *
	 * @param   string  $type  The type of the model: Categories, Releases, Items
	 * @param   mixed   $id    The numeric ID of the record to load or an array with the keys to look for
	 *
	 * @return  Categories|Releases|Items|UpdateStreams
	 *
	 * @see     .phpstorm.meta.php  for advanced type hinting of what is essentially a factory method
	 *
	 * @since   5.1.0
	 */
	private function getModelObject(string $type, $id): DataModel
	{
		/** @var DataModel $model */
		$model = $this->getContainer()->factory->model($type)->tmpInstance();

		// Do not load relationships through this method
		$model->with([]);

		if (empty($id))
		{
			return $model;
		}

		if (is_numeric($id))
		{
			$search = [
				'id' => (int) $id,
			];
		}
		elseif (is_array($id))
		{
			$search = $id;
		}
		else
		{
			return $model;
		}

		if (empty($search))
		{
			return $model;
		}

		ksort($search);

		$key = md5(json_encode($search));

		if (isset($this->modelCache[$type][$key]))
		{
			return $this->modelCache[$type][$key];
		}

		try
		{
			$this->modelCache[$type][$key] = $model->findOrFail($search);
		}
		catch (RecordNotLoaded $e)
		{
			$this->modelCache[$type][$key] = $model;
		}

		return $this->modelCache[$type][$key];
	}
}