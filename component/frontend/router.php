<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('FOF 3.0 is not installed', 500);
}

if (!class_exists('ComArsRouter'))
{
	require_once __DIR__ . '/Helper/ComArsRouter.php';
}

if (!class_exists('ArsRouterHelper'))
{
	require_once __DIR__ . '/Helper/ArsRouterHelper.php';
}

function arsBuildRoute(&$query)
{
	$format = isset($query['format']) ? $query['format'] : 'html';
	$view = isset($query['view']) ? $query['view'] : 'browses';

	if (in_array($view, ['download', 'downloads', 'Item']))
	{
		$format = 'raw';
	}

	switch ($format)
	{
		case 'html':
			if (ComArsRouter::$routeHtml)
			{
				$segments = arsBuildRouteHtml($query);
			}
			else
			{
				$segments = array();
			}
			break;

		case 'xml':
			$segments = arsBuildRouteXml($query);
			break;

		case 'ini':
			$segments = arsBuildRouteIni($query);
			break;

		case 'raw':
		default:
			if (ComArsRouter::$routeRaw)
			{
				$segments = arsBuildRouteRaw($query);
			}
			else
			{
				$segments = array();
			}
			break;
	}

	return $segments;
}

function arsBuildRouteHtml(&$query)
{
	static $currentLang = null;

	if (is_null($currentLang))
	{
		$jLang       = JFactory::getLanguage();
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

	$container = \FOF30\Container\Container::getInstance('com_ars');
	$menus     = JMenu::getInstance('site');

	$view     = ArsRouterHelper::getAndPop($query, 'view', 'browses');
	$task     = ArsRouterHelper::getAndPop($query, 'task');
	$layout   = ArsRouterHelper::getAndPop($query, 'layout');
	$id       = ArsRouterHelper::getAndPop($query, 'id');
	$Itemid   = ArsRouterHelper::getAndPop($query, 'Itemid');
	$language = ArsRouterHelper::getAndPop($query, 'language', $currentLang);
	$vgroupid = ArsRouterHelper::getAndPop($query, 'vgroupid');

	// The id in the Releases view is called category_id
	if ($view == 'Releases')
	{
		$alt_id = ArsRouterHelper::getAndPop($query, 'category_id');
		$id     = empty($alt_id) ? $id : $alt_id;
	}

	// The id in the Items view is called release_id
	if ($view == 'Items')
	{
		$alt_id = ArsRouterHelper::getAndPop($query, 'release_id');
		$id     = empty($alt_id) ? $id : $alt_id;
	}

	$queryOptions = [
		'option'   => 'com_ars',
		'view'     => $view,
		'task'     => $task,
		'layout'   => $layout,
		'id'       => $id,
		'language' => $language,
		'vgroupid' => $vgroupid,
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
				$menu            = ArsRouterHelper::findMenu($altQueryOptions);
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
				$menu            = ArsRouterHelper::findMenu($altQueryOptions);
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

			// TODO Cache category aliases and vgroup ID
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

			$catAlias    = $category->alias;
			$catVgroupId = $category->vgroup_id;

			if (empty($Itemid))
			{
				// Try to find a menu item for this category
				$options = $queryOptions;
				unset($options['id']);
				$params = ['catid' => $id];

				// Does the category have a visual group?  If so, let's add that to the query options

				if ($catVgroupId)
				{
					$options['vgroupid'] = $catVgroupId;
				}

				$possibleViews = ['category', 'Releases'];

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$menu            = ArsRouterHelper::findMenu($altQueryOptions, $params);
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

					// Does the category have a visual group?  If so, let's add that to the query options
					if ($catVgroupId)
					{
						$options['vgroupid'] = $catVgroupId;
					}

					$possibleViews = ['browse', 'browses', 'Categories'];

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$menu            = ArsRouterHelper::findMenu($altQueryOptions);
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
			// TODO Cache category IDs and release alias per release, get category aliases and vgroup IDs from category cache
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
			$catVgroup    = 0;

			if (is_object($release->category))
			{
				$catId     = $release->category->id;
				$catAlias  = $release->category->alias;
				$catVgroup = $release->category->vgroup_id;
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

				// Does the category have a visual group?  If so, let's add that to the query options
				$catVgroupId = $catVgroup;

				if ($catVgroupId)
				{
					$options['vgroupid'] = $catVgroupId;
				}

				$possibleViews = ['release', 'Items'];

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$menu            = ArsRouterHelper::findMenu($altQueryOptions, $params);

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

					// Does the category have a visual group?  If so, let's add that to the query options
					if ($catVgroupId)
					{
						$options['vgroupid'] = $catVgroupId;
					}

					$possibleViews = ['browse', 'browses', 'Categories'];

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$menu            = ArsRouterHelper::findMenu($altQueryOptions);

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

function arsBuildRouteRaw(&$query)
{
	$segments = array();

	$view = ArsRouterHelper::getAndPop($query, 'view', 'invalid');
	$task = ArsRouterHelper::getAndPop($query, 'task', 'download');

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

	$container = \FOF30\Container\Container::getInstance('com_ars');

	$id = ArsRouterHelper::getAndPop($query, 'id');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid');

	$menus = JMenu::getInstance('site');

	// Get download item info
	/** @var \Akeeba\ReleaseSystem\Site\Model\Items $download */
	$download = $container->factory->model('Items')->tmpInstance();
	$download->find($id);

	// If we have an extension other than html, raw, ini, xml, php try to set the format to manipulate the extension (if
	// Joomla! is configured to use extensions in URLs)
	$fileTarget = ($download->type == 'link') ? $download->url : $download->filename;
	$extension = pathinfo($fileTarget, PATHINFO_EXTENSION);

	if (!in_array($extension, ['html', 'raw', 'ini', 'xml', 'php']))
	{
		$query['format'] = $extension;
	}

	// Get release info
	$release = $download->release;

	// Get category alias
	$catAlias = $release->category->alias;
	$catVgroupId = $release->category->vgroup_id;

	if ($Itemid)
	{
		$menu = $menus->getItem($Itemid);
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
				$segments[] = $catAlias;
				$segments[] = $release->alias;
				$segments[] = $download->alias;
				$query['Itemid'] = $Itemid;
				break;

			case 'category':
			case 'Releases':
				$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);

				if ($params->get('catid', 0) == $release->category_id)
				{
					$segments[] = $release->alias;
					$segments[] = $download->alias;
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
					$segments[] = $download->alias;
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
		$options = array('option' => 'com_ars', 'view' => 'Items');
		$params = array('relid' => $release->id);

		if ($catVgroupId)
		{
			$options['vgroupid'] = $catVgroupId;
		}

		$possibleViews = ['Items', 'release'];
		$menu = null;

		foreach ($possibleViews as $possibleView)
		{
			$altQueryOptions = array_merge($options, ['view' => $possibleView]);
			$menu = ArsRouterHelper::findMenu($altQueryOptions, $params);

			if (is_object($menu))
			{
				break;
			}
		}

		if (is_object($menu))
		{
			$segments[] = $download->alias;
			$query['Itemid'] = $menu->id;
		}
		else
		{
			$options = array('option' => 'com_ars', 'view' => 'category');
			$params = array('catid' => $release->category_id);

			if ($catVgroupId)
			{
				$options['vgroupid'] = $catVgroupId;
			}

			$possibleViews = ['Releases', 'category'];

			foreach ($possibleViews as $possibleView)
			{
				$altQueryOptions = array_merge($options, ['view' => $possibleView]);
				$menu = ArsRouterHelper::findMenu($altQueryOptions, $params);

				if (is_object($menu))
				{
					break;
				}
			}
		}

		if (is_object($menu))
		{
			$segments[] = $release->alias;
			$segments[] = $download->alias;
			$query['Itemid'] = $menu->id;
		}

		if (!is_object($menu))
		{
			$options = array('view' => 'browses', 'option' => 'com_ars');

			if ($catVgroupId)
			{
				$options['vgroupid'] = $catVgroupId;
			}

			$possibleViews = ['Categories', 'browse', 'browses'];
			$menu = null;

			foreach ($possibleViews as $possibleView)
			{
				$altQueryOptions = array_merge($options, ['view' => $possibleView]);
				$menu = ArsRouterHelper::findMenu($altQueryOptions);

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
				$segments[] = $catAlias;
				$segments[] = $release->alias;
				$segments[] = $download->alias;
				$query['Itemid'] = $menu->id;
			}
		}
	}

	return $segments;
}

function arsBuildRouteXml(&$query)
{
	$segments = array();

	$view = ArsRouterHelper::getAndPop($query, 'view', 'Update');
	$my_task = ArsRouterHelper::getAndPop($query, 'task', 'default');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid', null);
	$local_id = ArsRouterHelper::getAndPop($query, 'id', 'components');

	if (!in_array($view, ['Update', 'updates', 'update']))
	{
		return $segments;
	}

	$task = 'all';
	$id = 0;

	// Analyze the current Itemid
	if (!empty($Itemid))
	{
		// Get the specified menu
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getItem($Itemid);

		// Analyze URL
		$uri = new JUri($menuitem->link);
		$option = $uri->getVar('option');

		// Sanity check
		if ($option != 'com_ars')
		{
			$Itemid = null;
		}
		else
		{
			$task = $uri->getVar('task');
			$layout = $uri->getVar('layout');
			$format = $uri->getVar('format', 'ini');
			$id = $uri->getVar('id', null);

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
						$id = $params->get('category', 'components');
						break;

					case 'ini':
					case 'stream':
						$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
						$id = $params->get('streamid', 0);
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
				$options       = array('view' => 'updates', 'layout' => 'all', 'option' => 'com_ars');

				$possibleViews = ['Update', 'Updates', 'update', 'updates'];
				$otherMenuItem = null;

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$otherMenuItem = ArsRouterHelper::findMenu($altQueryOptions);

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
				$options = array('view' => 'updates', 'layout' => 'category', 'option' => 'com_ars');
				$params = array('category' => $local_id);
				$possibleViews = ['Update', 'Updates', 'update', 'updates'];
				$otherMenuItem = null;

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$otherMenuItem = ArsRouterHelper::findMenu($altQueryOptions, $params);

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
					$options = array('view' => 'updates', 'layout' => 'all', 'option' => 'com_ars');
					$possibleViews = ['Update', 'Updates', 'update', 'updates'];
					$otherMenuItem = null;

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$otherMenuItem = ArsRouterHelper::findMenu($altQueryOptions);

						if (!empty($otherMenuItem))
						{
							break;
						}
					}

					// Try to find an Itemid for all categories
					if (!empty($otherMenuItem))
					{
						$query['Itemid'] = $otherMenuItem->id;
						$segments[] = $local_id;
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
					$segments[] = $local_id;
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
			$db = JFactory::getDBO();
			$dbquery = $db->getQuery(true)
						  ->select(array(
							  $db->qn('type'),
							  $db->qn('alias'),
						  ))->from($db->qn('#__ars_updatestreams'))
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
				$options = array('view' => 'updates', 'layout' => 'stream', 'option' => 'com_ars');
				$params = array('streamid' => $local_id);
				$possibleViews = ['Update', 'Updates', 'update', 'updates'];
				$otherMenuItem = null;

				foreach ($possibleViews as $possibleView)
				{
					$altQueryOptions = array_merge($options, ['view' => $possibleView]);
					$otherMenuItem = ArsRouterHelper::findMenu($altQueryOptions, $params);

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
					$options = array('view' => 'updates', 'layout' => 'category', 'option' => 'com_ars');
					$params = array('category' => $stream->type);
					$possibleViews = ['Update', 'Updates', 'update', 'updates'];
					$otherMenuItem = null;

					foreach ($possibleViews as $possibleView)
					{
						$altQueryOptions = array_merge($options, ['view' => $possibleView]);
						$otherMenuItem = ArsRouterHelper::findMenu($altQueryOptions, $params);

						if (!empty($otherMenuItem))
						{
							break;
						}
					}

					if (!empty($otherMenuItem))
					{
						$query['Itemid'] = $otherMenuItem->id;
						$segments[] = $stream->alias;
					}
					else
					{
						// Try to find an Itemid for all categories
						$options = array('view' => 'updates', 'layout' => 'all', 'option' => 'com_ars');
						$possibleViews = ['Update', 'Updates', 'update', 'updates'];
						$otherMenuItem = null;

						foreach ($possibleViews as $possibleView)
						{
							$altQueryOptions = array_merge($options, ['view' => $possibleView]);
							$otherMenuItem = ArsRouterHelper::findMenu($altQueryOptions);

							if (!empty($otherMenuItem))
							{
								break;
							}
						}

						if (!empty($otherMenuItem))
						{
							$query['Itemid'] = $otherMenuItem->id;
							$segments[] = $stream->type;
							$segments[] = $local_id;
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
					$segments[] = $stream->alias;
				}
				elseif ($task == 'all')
				{
					$query['Itemid'] = $Itemid;
					$segments[] = $stream->type;
					$segments[] = $stream->alias;
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

function arsBuildRouteIni(&$query)
{
	$segments = array();

	$view = ArsRouterHelper::getAndPop($query, 'view', 'update');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid', null);
	$local_id = ArsRouterHelper::getAndPop($query, 'id', 'components');
	$task = 'ini';
	$id = '';

	if (!in_array($view, ['Update', 'updates', 'update']))
	{
		return $segments;
	}

	// Analyze the current Itemid
	if (!empty($Itemid))
	{
		// Get the specified menu
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getItem($Itemid);

		// Analyze URL
		$uri = new JUri($menuitem->link);
		$option = $uri->getVar('option');

		// Sanity check
		if ($option != 'com_ars')
		{
			$Itemid = null;
		}
		else
		{
			$task = $uri->getVar('task');
			$layout = $uri->getVar('layout');
			$format = $uri->getVar('format', 'ini');
			$id = $uri->getVar('id', null);

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
						$id = $params->get('category', 'components');
						break;

					case 'ini':
					case 'stream':
						$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
						$id = $params->get('streamid', 0);
						break;
				}
			}
		}
	}

	// TODO cache this?
	$db = JFactory::getDBO();
	$dbquery = $db->getQuery(true)
				  ->select(array(
					  $db->qn('type'),
					  $db->qn('alias'),
				  ))->from($db->qn('#__ars_updatestreams'))
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
		$otherMenuItem = ArsRouterHelper::findMenu(array('view' => 'updates', 'layout' => 'ini', 'option' => 'com_ars'), array('streamid' => $local_id));
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

function arsParseRoute(&$segments)
{
	$container = \FOF30\Container\Container::getInstance('com_ars');

	$input = JFactory::getApplication()->input;
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
			$url = JURI::getInstance()->toString();
			$basename = basename($url);

			// Lame way to get the extension via string manipulation (shoot me if you will, but this works everywhere)
			$ext = substr(strtolower($basename), -4);
			$format = ltrim($ext, '.');

			// If SplFileInfo is available let's prefer it
			if (class_exists('\\SplFileInfo'))
			{
				$info = new \SplFileInfo($basename);
				$format = $info->getExtension();
			}
		}
	}

	$segments = ArsRouterHelper::preconditionSegments($segments);

	switch ($format)
	{
		case 'html':
			return arsParseRouteHtml($segments);
			break;

		// The default is here to catch formats like "zip", "exe" or whatever may be returned by the if-block above.
		case 'raw':
		default:
			return arsParseRouteRaw($segments);
			break;

		case 'xml':
			return arsParseRouteXml($segments);
			break;

		case 'ini':
			return arsParseRouteIni($segments);
			break;
	}
}

function arsParseRouteHtml(&$segments)
{
	$query = array();
	$menus = JMenu::getInstance('site');
	$menu = $menus->getActive();

	if (!is_null($menu) && count($segments))
	{
		// We have a sub(-sub-sub)menu item
		$found = true;

		while ($found && count($segments))
		{
			$parent = $menu->id;
			$lastSegment = array_shift($segments);

			$m = $menus->getItems(array('parent_id', 'alias'), array($parent, $lastSegment), true);

			if (is_object($m))
			{
				$found = true;
				$menu = $m;
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
				$query['view'] = 'Categories';
				$query['layout'] = array_pop($segments);
				break;

			case 2:
				// Category view
				$query['view'] = 'Releases';
				$query['layout'] = null;
				$catalias = array_pop($segments);
				$root = array_pop($segments);

				// Load the category
				$db = JFactory::getDBO();
				$dbquery = $db->getQuery(true)
							  ->select('*')
							  ->from($db->qn('#__ars_categories'))
							  ->where($db->qn('alias') . ' = ' . $db->q($catalias));
				$db->setQuery($dbquery, 0, 1);
				$cat = $db->loadObject();

				if (empty($cat))
				{
					$query['view'] = 'Categories';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['category_id'] = $cat->id;
				}
				break;

			case 3:
				// Release view
				$query['view'] = 'Items';
				$query['layout'] = null;
				$relalias = array_pop($segments);
				$catalias = array_pop($segments);
				$root = array_pop($segments);

				// Load the release
				$db = JFactory::getDBO();

				$dbquery = $db->getQuery(true)
							  ->select(array(
								  $db->qn('r') . '.*',
								  $db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
								  $db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
								  $db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
								  $db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
								  $db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
								  $db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
								  $db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
							  ))
							  ->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r'))
							  ->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
								  $db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
							  ->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
							  ->where($db->qn('c') . '.' . $db->qn('alias') . ' = ' . $db->q($catalias));

				$db->setQuery($dbquery, 0, 1);
				$rel = $db->loadObject();

				if (empty($rel))
				{
					$query['view'] = 'Categories';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['release_id'] = $rel->id;
				}

				break;

			case 4:
				// Degenerate case :(
				return arsParseRouteRaw($segments);
				break;
		}
	}
	else
	{
		// A menu item is defined
		$view = $menu->query['view'];
		$catalias = null;
		$relalias = null;

		if (empty($view) || in_array($view, ['Categories', 'browse', 'browses']))
		{
			switch (count($segments))
			{
				case 1:
					// Category view
					$query['view'] = 'Releases';
					$catalias = array_pop($segments);
					break;

				case 2:
					// Release view
					$query['view'] = 'Items';
					$relalias = array_pop($segments);
					$catalias = array_pop($segments);
					break;

				case 3:
					// Degenerate case :(
					return arsParseRouteRaw($segments);
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
					$relalias = array_pop($segments);
					break;

				case 2:
					// Degenerate case :(
					return arsParseRouteRaw($segments);
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
				return arsParseRouteRaw($segments);
			}

			$query['view'] = 'Items';
			$relalias = array_pop($segments);
		}

		$db = JFactory::getDBO();

		if ($relalias && $catalias)
		{
			$dbquery = $db->getQuery(true)
						  ->select(array(
							  $db->qn('r') . '.*',
							  $db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
							  $db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
							  $db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
							  $db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
							  $db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
							  $db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
							  $db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
						  ))
						  ->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r'))
						  ->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
							  $db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
						  ->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
						  ->where($db->qn('c') . '.' . $db->qn('alias') . ' = ' . $db->q($catalias));

			$db->setQuery($dbquery, 0, 1);
			$rel = $db->loadObject();

			if (empty($rel))
			{
				$query['view'] = 'Categories';
				$query['layout'] = 'repository';
			}
			else
			{
				$query['release_id'] = $rel->id;
			}
		}
		elseif ($catalias && is_null($relalias))
		{
			$db = JFactory::getDBO();

			$dbquery = $db->getQuery(true)
						  ->select('*')
						  ->from($db->qn('#__ars_categories'))
						  ->where($db->qn('alias') . ' = ' . $db->q($catalias));

			$db->setQuery($dbquery, 0, 1);
			$cat = $db->loadObject();

			if (empty($cat))
			{
				$query['view'] = 'Categories';
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
			$catid = $params->get('catid', 0);

			$dbquery = $db->getQuery(true)
						  ->select(array(
							  $db->qn('r') . '.*',
							  $db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
							  $db->qn('c') . '.' . $db->qn('alias') . ' AS ' . $db->qn('cat_alias'),
							  $db->qn('c') . '.' . $db->qn('type') . ' AS ' . $db->qn('cat_type'),
							  $db->qn('c') . '.' . $db->qn('groups') . ' AS ' . $db->qn('cat_groups'),
							  $db->qn('c') . '.' . $db->qn('directory') . ' AS ' . $db->qn('cat_directory'),
							  $db->qn('c') . '.' . $db->qn('access') . ' AS ' . $db->qn('cat_access'),
							  $db->qn('c') . '.' . $db->qn('published') . ' AS ' . $db->qn('cat_published'),
						  ))
						  ->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r'))
						  ->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
							  $db->qn('c') . '.' . $db->qn('id') . '=' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
						  ->where($db->qn('r') . '.' . $db->qn('alias') . ' = ' . $db->q($relalias))
						  ->where($db->qn('c') . '.' . $db->qn('id') . ' = ' . $db->q($catid));

			$db->setQuery($dbquery, 0, 1);
			$rel = $db->loadObject();

			if (empty($rel))
			{
				$query['view'] = 'Categories';
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

function arsParseRouteRaw(&$segments)
{
	$query = array();
	$menus = JMenu::getInstance('site');
	$menu = $menus->getActive();
	$query['view'] = 'Item';
	$query['task'] = 'download';
	$query['format'] = 'raw';

	if (is_null($menu))
	{
		// No menu. The segments are browse_layout/category_alias/release_alias/item_alias
		$query['layout'] = null;
		$itemalias = array_pop($segments);
		$relalias = array_pop($segments);
		$catalias = array_pop($segments);
		$root = array_pop($segments);

		// Load the release
		$db = JFactory::getDBO();

		$dbquery = $db->getQuery(true)
					  ->select(array(
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
					  ))
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
			$query['view'] = 'Categories';
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
		$view = $menu->query['view'];
		$params = is_object($menu->params) ? $menu->params : new JRegistry($menu->params);
		$itemalias = null;
		$catalias = null;
		$catid = null;
		$relalias = null;
		$relid = null;

		if (empty($view) || in_array($view, ['Categories', 'browse', 'browses']))
		{
			$itemalias = array_pop($segments);
			$relalias = array_pop($segments);
			$catalias = array_pop($segments);
		}
		elseif (in_array($view, ['Releases', 'category']))
		{
			$itemalias = array_pop($segments);
			$relalias = array_pop($segments);
			$catid = $params->get('catid', 0);
		}
		else
		{
			$itemalias = array_pop($segments);
			$relid = $params->get('relid', 0);
		}

		$db = JFactory::getDBO();

		$dbquery = $db->getQuery(true)
					  ->select(array(
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
					  ))
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
			$query['view'] = 'Categories';
			$query['layout'] = 'repository';
		}
		else
		{
			$query['id'] = $item->id;
		}
	}

	return $query;
}

function arsParseRouteXml(&$segments)
{
	$query = array();
	$query['view'] = 'Update';
	$query['format'] = 'xml';

	$menus = JMenu::getInstance('site');
	$menuitem = $menus->getActive();

	// Analyze the current Itemid
	if (!empty($menuitem))
	{
		// Analyze URL
		$uri = new JURI($menuitem->link);
		$option = $uri->getVar('option');

		// Sanity check
		if ($option != 'com_ars')
		{
			$Itemid = null;
		}
		else
		{
			$view = $uri->getVar('view');
			$task = $uri->getVar('task');
			$layout = $uri->getVar('layout');
			$format = $uri->getVar('format', 'ini');
			$id = $uri->getVar('id', null);

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
						$id = $params->get('category', 'components');
						break;

					case 'ini':
					case 'stream':
						$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
						$id = $params->get('streamid', 0);
						break;
				}
			}

			if (($option == 'com_ars') && in_array($view, ['Update', 'update', 'updates']))
			{
				switch ($task)
				{
					case 'stream':
						$query['task'] = 'stream';
						$query['id'] = $id;

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

	$cat = count($segments) ? array_shift($segments) : null;
	$stream = count($segments) ? array_shift($segments) : null;

	if (empty($cat) && empty($stream))
	{
		return $query;
	}
	elseif (!empty($cat) && empty($stream))
	{
		$query['task'] = 'category';
		$query['id'] = $cat;
	}
	else
	{
		$query['task'] = 'stream';
		$db = JFactory::getDBO();
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
	}

	return $query;
}

function arsParseRouteIni(&$segments)
{
	$query = array();
	$query['view'] = 'Update';
	$query['format'] = 'ini';
	$query['task'] = 'ini';

	$check = array_shift($segments);

	if ($check != 'updates')
	{
		return $query;
	}

	$cat = count($segments) ? array_shift($segments) : null;
	$stream = count($segments) ? array_shift($segments) : null;

	$query['task'] = 'stream';

	$db = JFactory::getDBO();
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
