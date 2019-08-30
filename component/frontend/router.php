<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use FOF30\Container\Container;
use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('FOF 3.0 is not installed', 500);
}

/**
 * Akeeba Release System component router
 *
 * This used to be separate functions for building and parsing routes. It has been converted to a router class since it
 * is necessary for Joomla! 4. Router classes are supported since Joomla! 3.3, so no lost compatibility there.
 *
 * @since   5.0.0
 */
class ArsRouter extends RouterBase
{
	/**
	 * Should I build routes for raw views?
	 *
	 * @var bool
	 */
	protected static $routeRaw = true;

	/**
	 * Should I build routes for html views?
	 *
	 * @var bool
	 */
	protected static $routeHtml = true;

	/**
	 * Build method for URLs
	 * This method is meant to transform the query parameters into a more human
	 * readable form. It is only executed when SEF mode is switched on.
	 *
	 * @param array  &$query An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   5.0.0
	 */
	public function build(&$query)
	{
		$format = isset($query['format']) ? $query['format'] : 'html';
		$view   = isset($query['view']) ? $query['view'] : 'browses';

		if (in_array($view, ['download', 'downloads', 'Item']))
		{
			$format = 'raw';
		}

		switch ($format)
		{
			case 'html':
				$segments = [];

				if (self::$routeHtml)
				{
					$segments = $this->arsBuildRouteHtml($query);
				}
				break;

			case 'xml':
				$segments = $this->arsBuildRouteXml($query);
				break;

			case 'ini':
				$segments = $this->arsBuildRouteIni($query);
				break;

			case 'raw':
			default:
				$segments = [];

				if (self::$routeRaw)
				{
					$segments = $this->arsBuildRouteRaw($query);
				}
				break;
		}

		return $segments;
	}

	/**
	 * Parse method for URLs
	 * This method is meant to transform the human readable URL back into
	 * query parameters. It is only executed when SEF mode is switched on.
	 *
	 * @param array  &$segments The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function parse(&$segments)
	{
		$container = Container::getInstance('com_ars');

		$input  = Factory::getApplication()->input;
		$config = $container->platform->getConfig();

		$format = $input->getCmd('format', null);

		if (is_null($format))
		{
			// If no format is specified in the URL we first assume it's "html"
			$format = 'html';

			// If we have SEF URL suffixes enabled we can infer the format from the suffix
			if ($config->get('sef_suffix', 0))
			{
				// Get the last part of the URI path
				$url      = Uri::getInstance()->toString();
				$basename = basename($url);

				// Lame way to get the extension via string manipulation (shoot me if you will, but this works everywhere)
				$ext    = substr(strtolower($basename), -4);
				$format = ltrim($ext, '.');

				// If SplFileInfo is available let's prefer it
				if (class_exists('\\SplFileInfo'))
				{
					$info   = new \SplFileInfo($basename);
					$format = $info->getExtension();
				}
			}
		}

		$segments = self::preconditionSegments($segments);

		switch ($format)
		{
			case 'html':
				return $this->arsParseRouteHtml($segments);
				break;

			// The default is here to catch formats like "zip", "exe" or whatever may be returned by the if-block above.
			case 'raw':
			default:
				return $this->arsParseRouteRaw($segments);
				break;

			case 'xml':
				return $this->arsParseRouteXml($segments);
				break;

			case 'ini':
				return $this->arsParseRouteIni($segments);
				break;
		}
	}

	private function arsBuildRouteHtml(array &$query): array
	{
		static $currentLang = null;

		if (is_null($currentLang))
		{
			$jLang       = Factory::getLanguage();
			$currentLang = $jLang->getTag();
		}

		$segments = [];

		// If there is only the option and Itemid, let Joomla! decide on the naming scheme
		if (isset($query['option']) && isset($query['Itemid']) &&
			!isset($query['view']) && !isset($query['task']) &&
			!isset($query['layout']) && !isset($query['id'])
		)
		{
			return $segments;
		}

		$container = Container::getInstance('com_ars');
		$menus     = AbstractMenu::getInstance('site');

		$view     = self::getAndPop($query, 'view', 'browses');
		$task     = self::getAndPop($query, 'task');
		$layout   = self::getAndPop($query, 'layout');
		$id       = self::getAndPop($query, 'id');
		$Itemid   = self::getAndPop($query, 'Itemid');
		$language = self::getAndPop($query, 'language', $currentLang);

		// The id in the Releases view is called category_id
		if ($view == 'Releases')
		{
			$alt_id = self::getAndPop($query, 'category_id');
			$id     = empty($alt_id) ? $id : $alt_id;
		}

		// The id in the Items view is called release_id
		if ($view == 'Items')
		{
			$alt_id = self::getAndPop($query, 'release_id');
			$id     = empty($alt_id) ? $id : $alt_id;
		}

		$queryOptions = [
			'option'   => 'com_ars',
			'view'     => $view,
			'task'     => $task,
			'layout'   => $layout,
			'id'       => $id,
			'language' => $language,
		];

		switch ($view)
		{
			case 'dlidlabel':
			case 'dlidlabels':
			case 'DownloadIDLabels':
			case 'DownloadIDLabel':
				if ($Itemid)
				{
					$menu  = $menus->getItem($Itemid);
					$mView = isset($menu->query['view']) ? $menu->query['view'] : 'Categories';

					// No, we have to find another root
					if (!in_array($mView, ['dlidlabel', 'dlidlabels', 'DownloadIDLabel', 'DownloadIDLabels']))
					{
						$Itemid = null;
					}
				}

				$possibleViews = ['dlidlabel', 'dlidlabels', 'DownloadIDLabel', 'DownloadIDLabels'];

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($queryOptions, ['view' => $possibleView]);
					$menu            = self::findMenu($altQueryOptions);
					$Itemid          = empty($menu) ? null : $menu->id;

					if (!empty($Itemid))
					{
						break;
					}
				}

				if (empty($Itemid))
				{
					$query['option'] = 'com_ars';
					$query['view']   = $view;

					if (!empty($language))
					{
						$query['language'] = $language;
					}
				}
				else
				{
					// Joomla! will let the menu item naming work its magic
					$query['Itemid'] = $Itemid;
				}

				if (!empty($task))
				{
					$query['task'] = $task;
				}

				if (!empty($layout))
				{
					$query['layout'] = $layout;
				}

				if (!empty($id))
				{
					$query['id'] = $id;
				}

				break;

			case 'browses':
			case 'categories':
			case 'Categories':
				// Is it a Categories list menu item?
				if ($Itemid)
				{
					$menu  = $menus->getItem($Itemid);
					$mView = isset($menu->query['view']) ? $menu->query['view'] : 'Categories';

					if (!in_array($mView, ['browses', 'browse', 'Categories']))
					{
						$Itemid = null;
					}
				}

				$possibleViews = ['browse', 'browses', 'Categories'];

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($queryOptions, ['view' => $possibleView]);
					$menu            = self::findMenu($altQueryOptions);
					$Itemid          = empty($menu) ? null : $menu->id;

					if (!empty($Itemid))
					{
						break;
					}
				}

				if (empty($Itemid))
				{
					// No menu found, let's add a segment based on the layout
					$segments[] = $layout;
				}
				else
				{
					// Joomla! will let the menu item naming work its magic
					$query['Itemid'] = $Itemid;
				}
				break;

			case 'category':
			case 'Releases':
				// Do we have a category menu item (showing the releases of a Category)?
				if ($Itemid)
				{
					$menu  = $menus->getItem($Itemid);
					$mView = isset($menu->query['view']) ? $menu->query['view'] : 'Categories';

					// No, we have to find another root
					if (in_array($mView, ['category', 'Releases']))
					{
						$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);

						if ($params->get('cat_id', 0) == $id)
						{
							$query['Itemid'] = $Itemid;

							return $segments;
						}
						else
						{
							$Itemid = null;
						}
					}
					elseif (!in_array($mView, ['browses', 'browse', 'Categories']))
					{
						$Itemid = null;
					}
				}

				// TODO Cache category aliases
				// Get category alias
				/** @var \Akeeba\ReleaseSystem\Site\Model\Categories $category */
				$category = $container->factory->model('Categories')->tmpInstance();

				try
				{
					$category->find($id);
				}
				catch (\Exception $e)
				{
				}

				$catAlias = $category->alias;

				if (empty($Itemid))
				{
					// Try to find a menu item for this category
					$options = $queryOptions;
					unset($options['id']);
					$params = ['catid' => $id];

					$possibleViews = ['category', 'Releases'];

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$menu            = self::findMenu($altQueryOptions, $params);
						$Itemid          = empty($menu) ? null : $menu->id;

						if (!empty($Itemid))
						{
							break;
						}
					}

					if (!empty($Itemid))
					{
						// A category menu item found, use it
						$query['Itemid'] = $Itemid;
					}
					else
					{
						// Not found. Try fetching a Categories menu item
						$options = [
							'option'   => 'com_ars', 'view' => 'Categories', 'layout' => 'repository',
							'language' => $language,
						];

						$possibleViews = ['browse', 'browses', 'Categories'];

						foreach ($possibleViews as $possibleView)
						{
							$altQueryOptions = array_merge($options, ['view' => $possibleView]);
							$menu            = self::findMenu($altQueryOptions);
							$Itemid          = empty($menu) ? null : $menu->id;

							if (!empty($Itemid))
							{
								$query['Itemid'] = $menu->id;
								$segments[]      = $catAlias;

								break;
							}
						}

						if (empty($Itemid))
						{
							// Push the browser layout and category alias
							$segments[] = 'repository';
							$segments[] = $catAlias;
						}
					}
				}
				else
				{
					// This is a Categories menu item. Push the category alias
					$query['Itemid'] = $Itemid;
					$segments[]      = $catAlias;
				}

				break;

			case 'release':
			case 'Items':
				// TODO Cache category IDs and release alias per release, get category aliases from category cache
				// Get release info
				/** @var \Akeeba\ReleaseSystem\Site\Model\Releases $release */
				$release = $container->factory->model('Releases')->tmpInstance();

				try
				{
					$release->find($id);
				}
				catch (\Exception $e)
				{
				}

				// Get release info
				$releaseAlias = $release->alias;
				$catId        = 0;
				$catAlias     = '';

				if (is_object($release->category))
				{
					$catId    = $release->category->id;
					$catAlias = $release->category->alias;
				}

				// Do we have a "category" menu?
				if ($Itemid)
				{
					$menu  = $menus->getItem($Itemid);
					$mView = isset($menu->query['view']) ? $menu->query['view'] : 'Categories';

					if (in_array($mView, ['browses', 'browse', 'Categories']))
					{
						// No. It is a Categories menu item. We must add the category and release aliases.
						$query['Itemid'] = $Itemid;
						$segments[]      = $catAlias;
						$segments[]      = $releaseAlias;
					}
					elseif (in_array($mView, ['category', 'Releases']))
					{
						// Yes! Is it the category we want?
						$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);

						if ($params->get('catid', 0) == $catId)
						{
							// Cool! Just append the release alias
							$query['Itemid'] = $Itemid;
							$segments[]      = $releaseAlias;
						}
						else
						{
							// Nope. Gotta find a new menu item.
							$Itemid = null;
						}
					}
					else
					{
						// Probably a menu item to another release. Hmpf!
						$Itemid = null;
					}
				}

				if (empty($Itemid))
				{
					// Try to find a category menu item
					$options = ['view' => 'Category', 'option' => 'com_ars', 'language' => $language];
					$params  = ['catid' => $catId];

					$possibleViews = ['release', 'Items'];

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$menu            = self::findMenu($altQueryOptions, $params);

						if (!empty($menu))
						{
							$query['Itemid'] = $menu->id;

							break;
						}
					}

					if (!empty($menu))
					{
						// Found it! Just append the release alias
						$query['Itemid'] = $menu->id;
						$segments[]      = $releaseAlias;
					}
					else
					{
						// Nah. Let's find a browse menu item.
						$options = ['view' => 'browses', 'option' => 'com_ars', 'language' => $language];

						$possibleViews = ['browse', 'browses', 'Categories'];

						foreach ($possibleViews as $possibleView)
						{
							$altQueryOptions = array_merge($options, ['view' => $possibleView]);
							$menu            = self::findMenu($altQueryOptions);

							if (!empty($menu))
							{
								$query['Itemid'] = $menu->id;

								break;
							}
						}

						if (!empty($menu))
						{
							// We must add the category and release aliases.
							$query['Itemid'] = $menu->id;
							$segments[]      = $catAlias;
							$segments[]      = $releaseAlias;
						}
						else
						{
							// I must add the full path
							$segments[] = 'repository';
							$segments[] = $catAlias;
							$segments[] = $releaseAlias;
						}
					}
				}

				break;
		}

		return $segments;
	}

	private function arsBuildRouteRaw(array &$query): array
	{
		$segments = [];

		$view = self::getAndPop($query, 'view', 'invalid');
		$task = self::getAndPop($query, 'task', 'download');

		// Map all possible views
		if (in_array($view, ['download', 'Download', 'downloads', 'Downloads', 'Items', 'Item']))
		{
			$view = 'Item';
			$task = 'download';
		}

		if (($view != 'Item') || ($task != 'download'))
		{
			return $segments;
		}

		$container = Container::getInstance('com_ars');

		$id     = self::getAndPop($query, 'id');
		$Itemid = self::getAndPop($query, 'Itemid');

		$menus = AbstractMenu::getInstance('site');

		// Get download item info
		/** @var \Akeeba\ReleaseSystem\Site\Model\Items $download */
		$download = $container->factory->model('Items')->tmpInstance();
		$download->find($id);

		// If we have an extension other than html, raw, ini, xml, php try to set the format to manipulate the extension (if
		// Joomla! is configured to use extensions in URLs)
		$fileTarget = ($download->type == 'link') ? $download->url : $download->filename;
		$extension  = pathinfo($fileTarget, PATHINFO_EXTENSION);

		if (!in_array($extension, ['html', 'raw', 'ini', 'xml', 'php']))
		{
			$query['format'] = $extension;
		}

		// Get release info
		$release = $download->release;

		// Get category alias
		$catAlias = $release->category->alias;

		if ($Itemid)
		{
			$menu  = $menus->getItem($Itemid);
			$mview = '';

			if (!empty($menu))
			{
				if (isset($menu->query['view']))
				{
					$mview = $menu->query['view'];
				}
			}

			switch ($mview)
			{
				case 'browses':
				case 'browse':
				case 'Categories':
					$segments[]      = $catAlias;
					$segments[]      = $release->alias;
					$segments[]      = $download->alias;
					$query['Itemid'] = $Itemid;
					break;

				case 'category':
				case 'Releases':
					$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);

					if ($params->get('catid', 0) == $release->category_id)
					{
						$segments[]      = $release->alias;
						$segments[]      = $download->alias;
						$query['Itemid'] = $Itemid;
					}
					else
					{
						$Itemid = null;
					}
					break;

				case 'release':
				case 'Items':
					$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);

					if ($params->get('relid', 0) == $release->id)
					{
						$segments[]      = $download->alias;
						$query['Itemid'] = $Itemid;
					}
					else
					{
						$Itemid = null;
					}
					break;

				default:
					$Itemid = null;
			}
		}

		if (empty($Itemid))
		{
			$options = ['option' => 'com_ars', 'view' => 'Items'];
			$params  = ['relid' => $release->id];

			$possibleViews = ['Items', 'release'];
			$menu          = null;

			foreach ($possibleViews as $possibleView)
			{
				$altQueryOptions = array_merge($options, ['view' => $possibleView]);
				$menu            = self::findMenu($altQueryOptions, $params);

				if (is_object($menu))
				{
					break;
				}
			}

			if (is_object($menu))
			{
				$segments[]      = $download->alias;
				$query['Itemid'] = $menu->id;
			}
			else
			{
				$options = ['option' => 'com_ars', 'view' => 'category'];
				$params  = ['catid' => $release->category_id];

				$possibleViews = ['Releases', 'category'];

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$menu            = self::findMenu($altQueryOptions, $params);

					if (is_object($menu))
					{
						break;
					}
				}
			}

			if (is_object($menu))
			{
				$segments[]      = $release->alias;
				$segments[]      = $download->alias;
				$query['Itemid'] = $menu->id;
			}

			if (!is_object($menu))
			{
				$options = ['view' => 'browses', 'option' => 'com_ars'];

				$possibleViews = ['Categories', 'browse', 'browses'];
				$menu          = null;

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$menu            = self::findMenu($altQueryOptions);

					if (is_object($menu))
					{
						break;
					}
				}

				if (!is_object($menu))
				{
					$segments[] = 'repository';
					$segments[] = $catAlias;
					$segments[] = $release->alias;
					$segments[] = $download->alias;
				}
				else
				{
					$segments[]      = $catAlias;
					$segments[]      = $release->alias;
					$segments[]      = $download->alias;
					$query['Itemid'] = $menu->id;
				}
			}
		}

		return $segments;
	}

	private function arsBuildRouteXml(array &$query): array
	{
		$segments = [];

		$view     = self::getAndPop($query, 'view', 'Update');
		$my_task  = self::getAndPop($query, 'task', 'default');
		$Itemid   = self::getAndPop($query, 'Itemid', null);
		$local_id = self::getAndPop($query, 'id', 'components');

		if (!in_array($view, ['Update', 'updates', 'update']))
		{
			return $segments;
		}

		$task = 'all';
		$id   = 0;

		// Analyze the current Itemid
		if (!empty($Itemid))
		{
			// Get the specified menu
			$menus    = AbstractMenu::getInstance('site');
			$menuitem = $menus->getItem($Itemid);

			// Analyze URL
			$uri    = new Uri($menuitem->link);
			$option = $uri->getVar('option');

			// Sanity check
			if ($option != 'com_ars')
			{
				$Itemid = null;
			}
			else
			{
				$task   = $uri->getVar('task');
				$layout = $uri->getVar('layout');
				$format = $uri->getVar('format', 'ini');
				$id     = $uri->getVar('id', null);

				if (empty($task) && !empty($layout))
				{
					$task = $layout;
				}

				if (empty($task))
				{
					if ($format == 'ini')
					{
						$task = 'ini';
					}
					else
					{
						$task = 'all';
					}
				}

				// make sure we can grab the ID specified in menu item options
				if (empty($id))
				{
					switch ($task)
					{
						case 'category':
							$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
							$id     = $params->get('category', 'components');
							break;

						case 'ini':
						case 'stream':
							$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
							$id     = $params->get('streamid', 0);
							break;
					}
				}
			}
		}

		switch ($my_task)
		{
			case 'default':
			case 'all':
				if (empty($Itemid))
				{
					// Try to find an Itemid with the same properties
					$options = ['view' => 'updates', 'layout' => 'all', 'option' => 'com_ars'];

					$possibleViews = ['Update', 'Updates', 'update', 'updates'];
					$otherMenuItem = null;

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$otherMenuItem   = self::findMenu($altQueryOptions);

						if (!empty($otherMenuItem))
						{
							break;
						}
					}

					if (!empty($otherMenuItem))
					{
						// Exact match
						$query['Itemid'] = $otherMenuItem->id;
					}
					else
					{
						$segments[] = 'updates';
					}
				}
				else
				{
					if ($task == 'all')
					{
						$query['Itemid'] = $Itemid;
					}
					else
					{
						$segments[] = 'updates';
					}
				}
				break;

			case 'category':
				if (empty($Itemid))
				{
					// Try to find an Itemid with the same properties
					$options       = ['view' => 'updates', 'layout' => 'category', 'option' => 'com_ars'];
					$params        = ['category' => $local_id];
					$possibleViews = ['Update', 'Updates', 'update', 'updates'];
					$otherMenuItem = null;

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$otherMenuItem   = self::findMenu($altQueryOptions, $params);

						if (!empty($otherMenuItem))
						{
							break;
						}
					}

					if (!empty($otherMenuItem))
					{
						// Exact match
						$query['Itemid'] = $otherMenuItem->id;
					}
					else
					{
						// Try to find an Itemid for all categories
						$options       = ['view' => 'updates', 'layout' => 'all', 'option' => 'com_ars'];
						$possibleViews = ['Update', 'Updates', 'update', 'updates'];
						$otherMenuItem = null;

						foreach ($possibleViews as $possibleView)
						{
							$altQueryOptions = array_merge($options, ['view' => $possibleView]);
							$otherMenuItem   = self::findMenu($altQueryOptions);

							if (!empty($otherMenuItem))
							{
								break;
							}
						}

						// Try to find an Itemid for all categories
						if (!empty($otherMenuItem))
						{
							$query['Itemid'] = $otherMenuItem->id;
							$segments[]      = $local_id;
						}
						else
						{
							$segments[] = 'updates';
							$segments[] = $local_id;
						}
					}
				}
				else
				{
					// menu item id exists in the query
					if (($task == 'category') && ($id == $local_id))
					{
						$query['Itemid'] = $Itemid;
					}
					elseif ($task == 'all')
					{
						$query['Itemid'] = $Itemid;
						$segments[]      = $local_id;
					}
					else
					{
						$segments[] = 'updates';
						$segments[] = $local_id;
					}
				}
				break;

			case 'stream':
				// TODO Cache this?
				$db      = Factory::getDBO();
				$dbquery = $db->getQuery(true)
					->select([
						$db->qn('type'),
						$db->qn('alias'),
					])->from($db->qn('#__ars_updatestreams'))
					->where($db->qn('id') . ' = ' . $db->q($local_id));
				$db->setQuery($dbquery, 0, 1);
				$stream = $db->loadObject();

				if (empty($stream))
				{
					return $segments;
				}

				if (empty($Itemid))
				{
					// Try to find an Itemid with the same properties
					$options       = ['view' => 'updates', 'layout' => 'stream', 'option' => 'com_ars'];
					$params        = ['streamid' => $local_id];
					$possibleViews = ['Update', 'Updates', 'update', 'updates'];
					$otherMenuItem = null;

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$otherMenuItem   = self::findMenu($altQueryOptions, $params);

						if (!empty($otherMenuItem))
						{
							break;
						}
					}

					// Try to find an Itemid with the same properties
					if (!empty($otherMenuItem))
					{
						// Exact match
						$query['Itemid'] = $otherMenuItem->id;
					}
					else
					{
						// Try to find an Itemid for the parent category
						$options       = ['view' => 'updates', 'layout' => 'category', 'option' => 'com_ars'];
						$params        = ['category' => $stream->type];
						$possibleViews = ['Update', 'Updates', 'update', 'updates'];
						$otherMenuItem = null;

						foreach ($possibleViews as $possibleView)
						{
							$altQueryOptions = array_merge($options, ['view' => $possibleView]);
							$otherMenuItem   = self::findMenu($altQueryOptions, $params);

							if (!empty($otherMenuItem))
							{
								break;
							}
						}

						if (!empty($otherMenuItem))
						{
							$query['Itemid'] = $otherMenuItem->id;
							$segments[]      = $stream->alias;
						}
						else
						{
							// Try to find an Itemid for all categories
							$options       = ['view' => 'updates', 'layout' => 'all', 'option' => 'com_ars'];
							$possibleViews = ['Update', 'Updates', 'update', 'updates'];
							$otherMenuItem = null;

							foreach ($possibleViews as $possibleView)
							{
								$altQueryOptions = array_merge($options, ['view' => $possibleView]);
								$otherMenuItem   = self::findMenu($altQueryOptions);

								if (!empty($otherMenuItem))
								{
									break;
								}
							}

							if (!empty($otherMenuItem))
							{
								$query['Itemid'] = $otherMenuItem->id;
								$segments[]      = $stream->type;
								$segments[]      = $local_id;
							}
							else
							{
								$segments[] = 'updates';
								$segments[] = $stream->type;
								$segments[] = $stream->alias;
							}
						}
					}
				}
				else // if $Itemid is not empty
				{
					// menu item id exists in the query
					if (($task == 'stream') && ($id == $local_id))
					{
						$query['Itemid'] = $Itemid;
					}
					elseif (($task == 'category') && ($id == $stream->type))
					{
						$query['Itemid'] = $Itemid;
						$segments[]      = $stream->alias;
					}
					elseif ($task == 'all')
					{
						$query['Itemid'] = $Itemid;
						$segments[]      = $stream->type;
						$segments[]      = $stream->alias;
					}
					else
					{
						$segments[] = 'updates';
						$segments[] = $stream->type;
						$segments[] = $stream->alias;
					}
				}
				break;
		}

		return $segments;
	}

	private function arsBuildRouteIni(array &$query): array
	{
		$segments = [];

		$view     = self::getAndPop($query, 'view', 'update');
		$Itemid   = self::getAndPop($query, 'Itemid', null);
		$local_id = self::getAndPop($query, 'id', 'components');
		$task     = 'ini';
		$id       = '';

		if (!in_array($view, ['Update', 'updates', 'update']))
		{
			return $segments;
		}

		// Analyze the current Itemid
		if (!empty($Itemid))
		{
			// Get the specified menu
			$menus    = AbstractMenu::getInstance('site');
			$menuitem = $menus->getItem($Itemid);

			// Analyze URL
			$uri    = new Uri($menuitem->link);
			$option = $uri->getVar('option');

			// Sanity check
			if ($option != 'com_ars')
			{
				$Itemid = null;
			}
			else
			{
				$task   = $uri->getVar('task');
				$layout = $uri->getVar('layout');
				$format = $uri->getVar('format', 'ini');
				$id     = $uri->getVar('id', null);

				if (empty($task) && !empty($layout))
				{
					$task = $layout;
				}

				if (empty($task))
				{
					if ($format == 'ini')
					{
						$task = 'ini';
					}
					else
					{
						$task = 'all';
					}
				}

				// make sure we can grab the ID specified in menu item options
				if (empty($id))
				{
					switch ($task)
					{
						case 'category':
							$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
							$id     = $params->get('category', 'components');
							break;

						case 'ini':
						case 'stream':
							$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
							$id     = $params->get('streamid', 0);
							break;
					}
				}
			}
		}

		// TODO cache this?
		$db      = Factory::getDBO();
		$dbquery = $db->getQuery(true)
			->select([
				$db->qn('type'),
				$db->qn('alias'),
			])->from($db->qn('#__ars_updatestreams'))
			->where($db->qn('id') . ' = ' . $db->q($local_id));
		$db->setQuery($dbquery, 0, 1);
		$stream = $db->loadObject();

		if (empty($stream))
		{
			die();
		}

		if (empty($Itemid))
		{
			// Try to find an Itemid with the same properties
			$otherMenuItem = self::findMenu([
				'view' => 'updates', 'layout' => 'ini', 'option' => 'com_ars',
			], ['streamid' => $local_id]);
			if (!empty($otherMenuItem))
			{
				// Exact match
				$query['Itemid'] = $otherMenuItem->id;
			}
			else
			{
				$segments[] = 'updates';
				$segments[] = $stream->type;
				$segments[] = $stream->alias;
			}
		}
		else
		{
			// menu item id exists in the query
			if (($task == 'ini') && ($id == $local_id))
			{
				$query['Itemid'] = $Itemid;
			}
			else
			{
				$segments[] = 'updates';
				$segments[] = $stream->type;
				$segments[] = $stream->alias;
			}
		}

		return $segments;
	}

	private function arsParseRouteHtml(array &$segments): array
	{
		$query = [];
		$menus = AbstractMenu::getInstance('site');
		$menu  = $menus->getActive();

		if (!is_null($menu) && count($segments))
		{
			// We have a sub(-sub-sub)menu item
			$found = true;

			while ($found && count($segments))
			{
				$parent      = $menu->id;
				$lastSegment = array_shift($segments);

				$m = $menus->getItems(['parent_id', 'alias'], [$parent, $lastSegment], true);

				if (is_object($m))
				{
					$found = true;
					$menu  = $m;
				}
				else
				{
					$found = false;
					array_unshift($segments, $lastSegment);
				}
			}
		}

		if (is_null($menu))
		{
			// No menu. The segments are browse_layout/category_alias/release_alias
			switch (count($segments))
			{
				case 1:
					// Repository view
					$query['view']   = 'Categories';
					$query['layout'] = array_pop($segments);
					break;

				case 2:
					// Category view
					$query['view']   = 'Releases';
					$query['layout'] = null;
					$catalias        = array_pop($segments);
					$root            = array_pop($segments);

					// Load the category
					$db      = Factory::getDBO();
					$dbquery = $db->getQuery(true)
						->select('*')
						->from($db->qn('#__ars_categories'))
						->where($db->qn('alias') . ' = ' . $db->q($catalias));
					$db->setQuery($dbquery, 0, 1);
					$cat = $db->loadObject();

					if (empty($cat))
					{
						$query['view']   = 'Categories';
						$query['layout'] = 'repository';
					}
					else
					{
						$query['category_id'] = $cat->id;
					}
					break;

				case 3:
					// Release view
					$query['view']   = 'Items';
					$query['layout'] = null;
					$relalias        = array_pop($segments);
					$catalias        = array_pop($segments);
					$root            = array_pop($segments);

					// Load the release
					$db = Factory::getDBO();

					$dbquery = $db->getQuery(true)
						->select([
							$db->qn('r') . '.*',
							$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
							$db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
							$db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
							$db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
							$db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
							$db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
							$db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
						])
						->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r'))
						->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
							$db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
						->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
						->where($db->qn('c') . '.' . $db->qn('alias') . ' = ' . $db->q($catalias));

					$db->setQuery($dbquery, 0, 1);
					$rel = $db->loadObject();

					if (empty($rel))
					{
						$query['view']   = 'Categories';
						$query['layout'] = 'repository';
					}
					else
					{
						$query['release_id'] = $rel->id;
					}

					break;

				case 4:
					// Degenerate case :(
					return $this->arsParseRouteRaw($segments);
					break;
			}
		}
		else
		{
			// A menu item is defined
			$view     = $menu->query['view'];
			$catalias = null;
			$relalias = null;

			if (empty($view) || in_array($view, ['Categories', 'browse', 'browses']))
			{
				switch (count($segments))
				{
					case 1:
						// Category view
						$query['view'] = 'Releases';
						$catalias      = array_pop($segments);
						break;

					case 2:
						// Release view
						$query['view'] = 'Items';
						$relalias      = array_pop($segments);
						$catalias      = array_pop($segments);
						break;

					case 3:
						// Degenerate case :(
						return $this->arsParseRouteRaw($segments);
						break;
				}
			}
			elseif (empty($view) || in_array($view, ['Releases', 'category']))
			{
				switch (count($segments))
				{
					case 1:
						// Release view
						$query['view'] = 'Items';
						$relalias      = array_pop($segments);
						break;

					case 2:
						// Degenerate case :(
						return $this->arsParseRouteRaw($segments);
						break;
				}
			}
			else
			{
				if (in_array($view, ['DownloadIDLabels', 'dlidlabels']))
				{
					$query['view'] = $view;

					return $query;
				}

				if (in_array($view, ['DownloadIDLabel', 'dlidlabel']))
				{
					$query['view'] = $view;

					return $query;
				}

				// Degenerate case :(
				if (count($segments) == 2)
				{
					return $this->arsParseRouteRaw($segments);
				}

				$query['view'] = 'Items';
				$relalias      = array_pop($segments);
			}

			$db = Factory::getDBO();

			if ($relalias && $catalias)
			{
				$dbquery = $db->getQuery(true)
					->select([
						$db->qn('r') . '.*',
						$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
						$db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
						$db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
						$db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
						$db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
						$db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
						$db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
					])
					->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r'))
					->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
						$db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
					->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
					->where($db->qn('c') . '.' . $db->qn('alias') . ' = ' . $db->q($catalias));

				$db->setQuery($dbquery, 0, 1);
				$rel = $db->loadObject();

				if (empty($rel))
				{
					$query['view']   = 'Categories';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['release_id'] = $rel->id;
				}
			}
			elseif ($catalias && is_null($relalias))
			{
				$db = Factory::getDBO();

				$dbquery = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__ars_categories'))
					->where($db->qn('alias') . ' = ' . $db->q($catalias));

				$db->setQuery($dbquery, 0, 1);
				$cat = $db->loadObject();

				if (empty($cat))
				{
					$query['view']   = 'Categories';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['category_id'] = $cat->id;
				}
			}
			else
			{
				$params = is_object($menu->params) ? $menu->params : new JRegistry($menu->params);
				$catid  = $params->get('catid', 0);

				$dbquery = $db->getQuery(true)
					->select([
						$db->qn('r') . '.*',
						$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
						$db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
						$db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
						$db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
						$db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
						$db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
						$db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
					])
					->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r'))
					->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
						$db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
					->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
					->where($db->qn('c') . '.' . $db->qn('id') . ' = ' . $db->q($catid));

				$db->setQuery($dbquery, 0, 1);
				$rel = $db->loadObject();

				if (empty($rel))
				{
					$query['view']   = 'Categories';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['release_id'] = $rel->id;
				}
			}
		}

		return $query;
	}

	private function arsParseRouteRaw(array &$segments): array
	{
		$query           = [];
		$menus           = AbstractMenu::getInstance('site');
		$menu            = $menus->getActive();
		$query['view']   = 'Item';
		$query['task']   = 'download';
		$query['format'] = 'raw';

		if (is_null($menu))
		{
			// No menu. The segments are browse_layout/category_alias/release_alias/item_alias
			$query['layout'] = null;
			$itemalias       = array_pop($segments);
			$relalias        = array_pop($segments);
			$catalias        = array_pop($segments);
			$root            = array_pop($segments);

			// Load the release
			$db = Factory::getDBO();

			$dbquery = $db->getQuery(true)
				->select([
					$db->qn('i') . '.*',
					$db->qn('r') . '.' . $db->qn('category_id'),
					$db->qn('r') . '.' . $db->qn('version'),
					$db->qn('r') . '.' . $db->qn('maturity'),
					$db->qn('r') . '.' . $db->qn('alias') . ' AS ' . $db->qn('rel_alias'),
					$db->qn('r') . '.' . $db->qn('groups') . ' AS ' . $db->qn('rel_groups'),
					$db->qn('r') . '.' . $db->qn('access') . ' AS ' . $db->qn('rel_access'),
					$db->qn('r') . '.' . $db->qn('published') . ' AS ' . $db->qn('rel_published'),
					$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
					$db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
					$db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
					$db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
					$db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
					$db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
					$db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
				])
				->from($db->qn('#__ars_items') . ' AS ' . $db->qn('i'))
				->innerJoin($db->qn('#__ars_releases') . ' AS ' . $db->qn('r') . ' ON(' .
					$db->qn('r') . '.' . $db->qn('id') . '=' . $db->qn('i') . '.' . $db->qn('release_id') . ')')
				->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
					$db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
				->where($db->qn('i') . '.' . $db->qn('alias') . ' = ' . $db->q($itemalias))
				->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
				->where($db->qn('c') . '.' . $db->qn('alias') . ' = ' . $db->q($catalias));

			$db->setQuery($dbquery, 0, 1);
			$item = $db->loadObject();

			if (empty($item))
			{
				$query['view']   = 'Categories';
				$query['layout'] = 'repository';
				$query['format'] = 'html';
			}
			else
			{
				$query['id'] = $item->id;
			}
		}
		else
		{
			// A menu item is defined
			$view      = $menu->query['view'];
			$params    = is_object($menu->params) ? $menu->params : new JRegistry($menu->params);
			$itemalias = null;
			$catalias  = null;
			$catid     = null;
			$relalias  = null;
			$relid     = null;

			if (empty($view) || in_array($view, ['Categories', 'browse', 'browses']))
			{
				$itemalias = array_pop($segments);
				$relalias  = array_pop($segments);
				$catalias  = array_pop($segments);
			}
			elseif (in_array($view, ['Releases', 'category']))
			{
				$itemalias = array_pop($segments);
				$relalias  = array_pop($segments);
				$catid     = $params->get('catid', 0);
			}
			else
			{
				$itemalias = array_pop($segments);
				$relid     = $params->get('relid', 0);
			}

			$db = Factory::getDBO();

			$dbquery = $db->getQuery(true)
				->select([
					$db->qn('i') . '.*',
					$db->qn('r') . '.' . $db->qn('category_id'),
					$db->qn('r') . '.' . $db->qn('version'),
					$db->qn('r') . '.' . $db->qn('maturity'),
					$db->qn('r') . '.' . $db->qn('alias') . ' AS ' . $db->qn('rel_alias'),
					$db->qn('r') . '.' . $db->qn('groups') . ' AS ' . $db->qn('rel_groups'),
					$db->qn('r') . '.' . $db->qn('access') . ' AS ' . $db->qn('rel_access'),
					$db->qn('r') . '.' . $db->qn('published') . ' AS ' . $db->qn('rel_published'),
					$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
					$db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
					$db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
					$db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
					$db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
					$db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
					$db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
				])
				->from($db->qn('#__ars_items') . ' AS ' . $db->qn('i'))
				->innerJoin($db->qn('#__ars_releases') . ' AS ' . $db->qn('r') . ' ON(' .
					$db->qn('r') . '.' . $db->qn('id') . '=' . $db->qn('i') . '.' . $db->qn('release_id') . ')')
				->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
					$db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
				->where($db->qn('i') . '.' . $db->qn('alias') . ' = ' . $db->q($itemalias));

			if (!empty($relalias))
			{
				$dbquery->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias));
			}

			if (!empty($relid))
			{
				$dbquery->where($db->qn('r') . '.' . $db->qn('id') . ' = ' . $db->q($relid));
			}

			if (!empty($catalias))
			{
				$dbquery->where($db->qn('c') . '.' . $db->qn('alias') . ' = ' . $db->q($catalias));
			}

			if (!empty($catid))
			{
				$dbquery->where($db->qn('c') . '.' . $db->qn('id') . ' = ' . $db->q($catid));
			}

			$db->setQuery($dbquery, 0, 1);
			$item = $db->loadObject();

			if (empty($item))
			{
				// JError::raiseError('404', 'Item not found');
				$query['view']   = 'Categories';
				$query['layout'] = 'repository';
			}
			else
			{
				$query['id'] = $item->id;
			}
		}

		return $query;
	}

	private function arsParseRouteXml(array &$segments): array
	{
		$query           = [];
		$query['view']   = 'Update';
		$query['format'] = 'xml';

		$menus    = AbstractMenu::getInstance('site');
		$menuitem = $menus->getActive();

		// Analyze the current Itemid
		if (!empty($menuitem))
		{
			// Analyze URL
			$uri    = new Uri($menuitem->link);
			$option = $uri->getVar('option');

			// Sanity check
			if ($option != 'com_ars')
			{
				$Itemid = null;
			}
			else
			{
				$view   = $uri->getVar('view');
				$task   = $uri->getVar('task');
				$layout = $uri->getVar('layout');
				$format = $uri->getVar('format', 'ini');
				$id     = $uri->getVar('id', null);

				if (empty($task) && !empty($layout))
				{
					$task = $layout;
				}
				if (empty($task))
				{
					if ($format == 'ini')
					{
						$task = 'ini';
					}
					else
					{
						$task = 'all';
					}
				}

				// make sure we can grab the ID specified in menu item options
				if (empty($id))
				{
					switch ($task)
					{
						case 'category':
							$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
							$id     = $params->get('category', 'components');
							break;

						case 'ini':
						case 'stream':
							$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
							$id     = $params->get('streamid', 0);
							break;
					}
				}

				if (($option == 'com_ars') && in_array($view, ['Update', 'update', 'updates']))
				{
					switch ($task)
					{
						case 'stream':
							$query['task'] = 'stream';
							$query['id']   = $id;

							return $query;
							break;

						case 'category':
							array_unshift($segments, $id);
							array_unshift($segments, 'updates');
							break;

						case 'all':
						case 'ini':
							array_unshift($segments, 'updates');
							break;
					}
				}
			}
		}

		$check = array_shift($segments);

		if ($check != 'updates')
		{
			return $query;
		}

		$cat    = count($segments) ? array_shift($segments) : null;
		$stream = count($segments) ? array_shift($segments) : null;

		if (empty($cat) && empty($stream))
		{
			return $query;
		}
		elseif (!empty($cat) && empty($stream))
		{
			$query['task'] = 'category';
			$query['id']   = $cat;
		}
		else
		{
			$query['task'] = 'stream';
			$db            = Factory::getDBO();
			$dbquery       = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__ars_updatestreams'))
				->where($db->qn('alias') . ' = ' . $db->q($stream))
				->where($db->qn('type') . ' = ' . $db->q($cat));
			$db->setQuery($dbquery, 0, 1);
			$item = $db->loadObject();

			if (empty($item))
			{
				return $query;
			}

			$query['id'] = $item->id;
		}

		return $query;
	}

	private function arsParseRouteIni(array &$segments): array
	{
		$query           = [];
		$query['view']   = 'Update';
		$query['format'] = 'ini';
		$query['task']   = 'ini';

		$check = array_shift($segments);

		if ($check != 'updates')
		{
			return $query;
		}

		$cat    = count($segments) ? array_shift($segments) : null;
		$stream = count($segments) ? array_shift($segments) : null;

		$query['task'] = 'stream';

		$db      = Factory::getDBO();
		$dbquery = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__ars_updatestreams'))
			->where($db->qn('alias') . ' = ' . $db->q($stream))
			->where($db->qn('type') . ' = ' . $db->q($cat));
		$db->setQuery($dbquery, 0, 1);
		$item = $db->loadObject();

		if (empty($item))
		{
			return $query;
		}

		$query['id'] = $item->id;

		return $query;
	}

	/**
	 * Get a value from a key-value array and eliminate the key from it
	 *
	 * If the key is not found in the array the default value is returned
	 *
	 * @param array  $query   They key-value array
	 * @param string $key     The key to retrieve and eliminate from the array
	 * @param mixed  $default The default value to use if the key does not exist in the array.
	 *
	 * @return mixed The retrieved value (or the default, if the key was not present)
	 */
	private static function getAndPop(array &$query, string $key, $default = null)
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
	 * Finds a menu whose query parameters match those in $qoptions
	 *
	 * @param array $qoptions The query parameters to look for
	 * @param array $params   The menu parameters to look for
	 *
	 * @return null|object Null if not found, or the menu item if we did find it
	 * @throws Exception
	 */
	private static function findMenu(array $qoptions = [], ?array $params = null): ?object
	{
		$input = Factory::getApplication()->input;

		// Convert $qoptions to an object
		if (empty($qoptions) || !is_array($qoptions))
		{
			$qoptions = [];
		}

		$menus    = AbstractMenu::getInstance('site');
		$menuitem = $menus->getActive();

		// First check the current menu item (fastest shortcut!)
		if (is_object($menuitem) && self::checkMenu($menuitem, $qoptions, $params))
		{
			return $menuitem;
		}

		// Find all potential menu items
		$possible_items = [];

		foreach ($menus->getMenu() as $item)
		{
			if (self::checkMenu($item, $qoptions, $params))
			{
				$possible_items[] = $item;
			}
		}

		// If no potential item exists, return null
		if (empty($possible_items))
		{
			return null;
		}

		// Filter by language, if required
		/** @var JApplicationSite $app */
		$app      = Factory::getApplication();
		$langCode = $input->getCmd('language', '*');

		if ($app->getLanguageFilter())
		{
			$lang_filter_plugin = PluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg       = Factory::getLanguage();
				$langCode = $lg->getTag();
			}
		}

		if ($langCode == '*')
		{
			// No language filtering required, return the first item
			return array_shift($possible_items);
		}

		// Filter for exact language or *
		foreach ($possible_items as $item)
		{
			if (in_array($item->language, [$langCode, '*']))
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * Checks if a menu item conforms to the query options and parameters specified
	 *
	 * @param object $menu     A menu item
	 * @param array  $qoptions The query options to look for
	 * @param array  $params   The menu parameters to look for
	 *
	 * @return bool
	 * @throws Exception
	 */
	private static function checkMenu(object $menu, array $qoptions, ?array $params = null): bool
	{
		$query = $menu->query;

		foreach ($qoptions as $key => $value)
		{
			//if(is_null($value)) continue;
			if ($key == 'language')
			{
				continue;
			}

			if (empty($value))
			{
				continue;
			}

			if (!isset($query[$key]))
			{
				return false;
			}

			if ($query[$key] != $value)
			{
				return false;
			}
		}

		if (isset($qoptions['language']))
		{
			if (($menu->language != $qoptions['language']) && ($menu->language != '*'))
			{
				return false;
			}
		}

		if (is_null($params))
		{
			return true;
		}

		$menus = AbstractMenu::getInstance('site');
		$check = $menu->params instanceof JRegistry ? $menu->params : $menus->getParams($menu->id);

		foreach ($params as $key => $value)
		{
			if (is_null($value))
			{
				continue;
			}

			if ($key == 'language')
			{
				$v = $check->get($key);

				if (($v != $value) && ($v != '*') && !empty($v))
				{
					return false;
				}

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
	 * Precondition the SEF URL segments returned by Joomla
	 *
	 * Joomla replaces the first dash of each segment with a colon. For example /foo/bar-baz-bat will result in the
	 * array ['foo', 'bar:baz-bat']. This is based on the assumption that we write SEF URLs like Joomla used to back in
	 * 2005 when it was in version 1.0. Nowadays we want dashes to be kept as dashes. Hence this method which converts
	 * colons back to dashes.
	 *
	 * @param array $segments
	 *
	 * @return array
	 */
	private static function preconditionSegments(array $segments): array
	{
		$newSegments = [];

		if (empty($segments))
		{
			return [];
		}

		foreach ($segments as $segment)
		{
			if (strstr($segment, ':'))
			{
				$segment = str_replace(':', '-', $segment);
			}

			if (is_array($segment))
			{
				$newSegments[] = implode('-', $segment);

				continue;
			}

			$newSegments[] = $segment;
		}

		return $newSegments;
	}

	/**
	 * @param bool $routeRaw
	 */
	public static function setRouteRaw(bool $routeRaw): void
	{
		self::$routeRaw = $routeRaw;
	}

	/**
	 * @param bool $routeHtml
	 */
	public static function setRouteHtml(bool $routeHtml): void
	{
		self::$routeHtml = $routeHtml;
	}
}
