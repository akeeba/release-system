<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

include_once JPATH_LIBRARIES.'/fof/include.php';

function arsBuildRoute(&$query)
{
	$format = isset($query['format']) ? $query['format'] : 'html';
	$view = isset($query['view']) ? $query['view'] : 'browse';

	if($view == 'download') $format = 'raw';

	switch($format)
	{
		case 'html':
			$segments = arsBuildRouteHtml($query);
			break;
		case 'feed':
			$segments = arsBuildRouteFeed($query);
			break;
		case 'xml':
			$segments = arsBuildRouteXml($query);
			break;
		case 'ini':
			$segments = arsBuildRouteIni($query);
			break;
		case 'raw':
		default:
			$segments = arsBuildRouteRaw($query);
			break;
	}
	
	return $segments;
}

function arsBuildRouteHtml(&$query)
{
	$segments = array();

	//If there is only the option and Itemid, let Joomla! decide on the naming scheme
	if( isset($query['option']) && isset($query['Itemid']) &&
		!isset($query['view']) && !isset($query['task']) &&
		!isset($query['layout']) && !isset($query['id']) )
	{
		return $segments;
	}

	$menus = JMenu::getInstance('site');

	$view = ArsRouterHelper::getAndPop($query, 'view', 'browse');
	$task = ArsRouterHelper::getAndPop($query, 'task');
	$layout = ArsRouterHelper::getAndPop($query, 'layout');
	$id = ArsRouterHelper::getAndPop($query, 'id');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid');

	$qoptions = array( 'option' => 'com_ars', 'view' => $view, 'task' => $task, 'layout' => $layout, 'id' => $id );
	switch($view)
	{
		case 'browse':
			// Is it a browser menu?
			if($Itemid) {
				$menu = $menus->getItem($Itemid);
				$mView = isset($menu->query['view']) ? $menu->query['view'] : 'browse';
				// No, we have to find another root
				if( ($mView != 'browse') ) $Itemid = null;
			}

			if(empty($Itemid))
			{
				$menu = ArsRouterHelper::findMenu($qoptions);
				$Itemid = empty($menu) ? null : $menu->id;
			}

			if(empty($Itemid))
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
			// Do we have a category menu?
			if($Itemid)
			{
				$menu = $menus->getItem($Itemid);
				$mView = isset($menu->query['view']) ? $menu->query['view'] : 'browse';
				// No, we have to find another root
				if( ($mView == 'category') )
				{
					$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);
					if($params->get('cat_id',0) == $id)
					{
						$query['Itemid'] = $Itemid;
						return $segments;
					}
					else
					{
						$Itemid = null;
					}
				}
				elseif($mView != 'browse')
				{
					$Itemid = null;
				}
			}


			// Get category alias
			$catModel = FOFModel::getTmpInstance('Categories','ArsModel');
			$catalias = $catModel->getItem($id)->alias;

			if(empty($Itemid))
			{
				// Try to find a menu item for this category
				$options = $qoptions; unset($options['id']);
				$params = array('catid' => $id);
				$menu = ArsRouterHelper::findMenu($options, $params);
				$Itemid = empty($menu) ? null : $menu->id;

				if(!empty($Itemid))
				{
					// A category menu item found, use it
					$query['Itemid'] = $Itemid;
				}
				else
				{
					// Not found. Try fetching a browser menu item
					$options = array('option' => 'com_ars', 'view' => 'browse', 'layout' => 'repository');
					$menu = ArsRouterHelper::findMenu($options);
					$Itemid = empty($menu) ? null : $menu->id;
					if(!empty($Itemid))
					{
						// Push the Itemid and category alias
						$query['Itemid'] = $menu->id;
						$segments[] = $catalias;
					}
					else
					{
						// Push the browser layout and category alias
						$segments[] = 'repository';
						$segments[] = $catalias;
					}
				}
			}
			else
			{
				// This is a browser menu. Push the category alias
				$query['Itemid'] = $Itemid;
				$segments[] = $catalias;
			}

			break;

		case 'release':
			// Get release info
			$relModel = FOFModel::getTmpInstance('Releases','ArsModel');
			$release = $relModel->getItem($id);

			// Get category alias
			$catModel = FOFModel::getTmpInstance('Categories','ArsModel');
			$catalias = $catModel->getItem($release->category_id)->alias;

			// Do we have a "category" menu?
			if($Itemid)
			{
				$menu = $menus->getItem($Itemid);
				$mView = isset($menu->query['view']) ? $menu->query['view'] : 'browse';
				if( ($mView == 'browse') )
				{
					// No. It is a browse menu item. We must add the category and release aliases.
					$query['Itemid'] = $Itemid;
					$segments[] = $catalias;
					$segments[] = $release->alias;
				}
				elseif( ($mView == 'category') )
				{
					// Yes! Is it the category we want?
					$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);
					if($params->get('catid',0) == $release->category_id)
					{
						// Cool! Just append the release alias
						$query['Itemid'] = $Itemid;
						$segments[] = $release->alias;
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

			if(empty($Itemid))
			{
				// Try to find a category menu item
				$options = array('view'=>'category', 'option' => 'com_ars');
				$params = array('catid'=>$release->category_id);
				$menu = ArsRouterHelper::findMenu($options, $params);
				if(!empty($menu))
				{
					// Found it! Just append the release alias
					$query['Itemid'] = $menu->id;
					$segments[] = $release->alias;
				}
				else
				{
					// Nah. Let's find a browse menu item.
					$options = array('view'=>'browse', 'option' => 'com_ars');
					$menu = ArsRouterHelper::findMenu($options);
					if(!empty($menu))
					{
						// We must add the category and release aliases.
						$query['Itemid'] = $menu->id;
						$segments[] = $catalias;
						$segments[] = $release->alias;
					}
					else
					{
						// I must add the full path
						$segments[] = 'repository';
						$segments[] = $catalias;
						$segments[] = $release->alias;
					}
				}
			}

			break;
	}

	return $segments;
}

function arsBuildRouteFeed(&$query)
{

	$segments = array();

	$view = ArsRouterHelper::getAndPop($query, 'view', 'browse');
	$layout = ArsRouterHelper::getAndPop($query, 'layout', 'repository');
	$id = ArsRouterHelper::getAndPop($query, 'id', 0);
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid');

	$menus = JMenu::getInstance('site');

	$query['format'] = 'feed';

	switch($view)
	{
		case 'browse':
		case 'categories':
			$view = 'browse';
			$query['Itemid'] = $Itemid;
			break;

		case 'category':
			if($Itemid)
			{
				$menu = $menus->getItem($Itemid);
				if(empty($menu))
				{
					$Itemid = null;
				}
				elseif( (isset($menu->query['view']) ? $menu->query['view'] : 'browse') == 'category' )
				{
					$params = is_object($menu->params) ? $menu->params : new JRegistry($menu->params);
					if($params->get('catid',0) != $id)
					{
						$Itemid = null;
					}
					else
					{
						$query['Itemid'] = $menu->id;
					}
				}
				else
				{
					$Itemid = null;
				}
			}

			if(empty($Itemid))
			{
				$options = array('view'=>'category', 'option' => 'com_ars');
				$params = array('catid'=>$id);
				$menu = ArsRouterHelper::findMenu($options, $params);
				if(!empty($menu))
				{
					// Found it!
					$query['Itemid'] = $menu->id;
				}
				else
				{
					// Nah. Let's find a browse menu item.
					$options = array('view'=>'browse', 'option' => 'com_ars');
					$menu = ArsRouterHelper::findMenu($options);

					$model = FOFModel::getTmpInstance('Categories','ArsModel');
					$category = $model->getItem($id);

					if(!empty($menu))
					{
						// We must add the category and release aliases.
						$query['Itemid'] = $menu->id;
						$segments[] = $category->alias;
					}
					else
					{
						// I must add the full path
						$segments[] = 'repository';
						$segments[] = $category->alias;
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

	$view = isset($query['view']) ? $query['view'] : '';
	if($view != 'download' ) return $segments;
	
	$view = ArsRouterHelper::getAndPop($query, 'view', 'browse');
	$task = ArsRouterHelper::getAndPop($query, 'task');
	$layout = ArsRouterHelper::getAndPop($query, 'layout');
	$id = ArsRouterHelper::getAndPop($query, 'id');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid');

	$qoptions = array( 'option' => 'com_ars', 'view' => $view, 'task' => $task, 'layout' => $layout, 'id' => $id );
	$menus = JMenu::getInstance('site');
	
	// Get download item info
	$dlModel = FOFModel::getTmpInstance('Items','ArsModel');
	$download = $dlModel->getItem($id);

	// Get release info
	$relModel = FOFModel::getTmpInstance('Releases','ArsModel');
	$release = $relModel->getItem($download->release_id);

	// Get category alias
	$catModel = FOFModel::getTmpInstance('Categories','ArsModel');
	$catalias = $catModel->getItem($release->category_id)->alias;

	if($Itemid)
	{
		$menu = $menus->getItem($Itemid);
		$mview = '';
		if(!empty($menu)) if(isset($menu->query['view'])) $mview = $menu->query['view'];
		switch($mview)
		{
			case 'browse':
				$segments[] = $catalias;
				$segments[] = $release->alias;
				$segments[] = $download->alias;
				$query['Itemid'] = $Itemid;
				break;

			case 'category':
				$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);
				if($params->get('catid',0) == $release->category_id)
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
				$params = ($menu->params instanceof JRegistry) ? $menu->params : $menus->getParams($Itemid);
				if($params->get('relid',0) == $release->id)
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

	if(empty($Itemid))
	{
		$options = array('option' => 'com_ars', 'view'=>'release');
		$params = array('relid' => $release->id);
		$menu = ArsRouterHelper::findMenu($options, $params);
		if(is_object($menu))
		{
			$segments[] = $download->alias;
			$query['Itemid'] = $menu->id;
		}
		if(!is_object($menu))
		{
			$options = array('option' => 'com_ars', 'view'=>'category');
			$params = array('catid' => $release->category_id);
			$menu = ArsRouterHelper::findMenu($options, $params);
		}
		if(is_object($menu))
		{
			$segments[] = $release->alias;
			$segments[] = $download->alias;
			$query['Itemid'] = $menu->id;
		}
		if(!is_object($menu))
		{
			$options = array('view'=>'browse', 'option' => 'com_ars');
			$menu = ArsRouterHelper::findMenu($options);
			if(!is_object($menu))
			{
				$segments[] = 'repository';
				$segments[] = $catalias;
				$segments[] = $release->alias;
				$segments[] = $download->alias;
			}
			else
			{
				$segments[] = $catalias;
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

	$view = ArsRouterHelper::getAndPop($query, 'view', 'update');
	$my_task = ArsRouterHelper::getAndPop($query, 'task', 'default');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid', null);
	$local_id = ArsRouterHelper::getAndPop($query, 'id', 'components');

	// Analyze the current Itemid
	if(!empty($Itemid)) {
		// Get the specified menu
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getItem($Itemid);
		
		// Analyze URL
		$uri = new JURI($menuitem->link);
		$option = $uri->getVar('option');
		// Sanity check
		if($option != 'com_ars')
		{
			$Itemid = null;
		}
		else
		{
			$view = $uri->getVar('view');
			$task = $uri->getVar('task');
			$layout = $uri->getVar('layout');
			$format = $uri->getVar('format','ini');
			$id = $uri->getVar('id',null);
			if(empty($task) && !empty($layout)) $task = $layout;
			if(empty($task)) {
				if($format == 'ini') {
					$task = 'ini';
				} else {
					$task = 'all';
				}
			}
			
			// make sure we can grab the ID specified in menu item options
			if(empty($id)) switch($task)
			{
				case 'category':
					$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
					$id = $params->get('category','components');
					break;
				
				case 'ini':
				case 'stream':
					$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
					$id = $params->get('streamid',0);
					break;
			}
		}
	}
	
	switch($my_task)
	{
		case 'default':
		case 'all':
			if(empty($Itemid))
			{
				// Try to find an Itemid with the same properties				
				$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'all', 'option' => 'com_ars'));
				if(!empty($otherMenuItem)) {
					// Exact match
					$query['Itemid'] = $otherMenuItem->id;
				} else {
					$segments[] = 'updates';
				}
			}
			else
			{
				if($task == 'all') {
					$query['Itemid'] = $Itemid;
				} else {
					$segments[] = 'updates';
				}
			}
			break;

		case 'category':
			if(empty($Itemid))
			{
				// Try to find an Itemid with the same properties				
				$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'category', 'option' => 'com_ars'),array('category'=>$local_id));
				if(!empty($otherMenuItem)) {
					// Exact match
					$query['Itemid'] = $otherMenuItem->id;
				} else {
					// Try to find an Itemid for all categories
					$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'all', 'option' => 'com_ars'));
					if(!empty($otherMenuItem)) {
						$query['Itemid'] = $otherMenuItem->id;
						$segments[] = $local_id;
					} else {
						$segments[] = 'updates';
						$segments[] = $local_id;
					}
				}
			}
			else
			{
				// menu item id exists in the query
				if( ($task == 'category') && ($id == $local_id) ) {
					$query['Itemid'] = $Itemid;
				} elseif( $task == 'all' ) {
					$query['Itemid'] = $Itemid;
					$segments[] = $local_id;
				} else {
					$segments[] = 'updates';
					$segments[] = $local_id;
				}
			}
			break;

		case 'stream':
			$db = JFactory::getDBO();
			$dbquery = $db->getQuery(true)
				->select(array(
					$db->qn('type'),
					$db->qn('alias'),
				))->from($db->qn('#__ars_updatestreams'))
				->where($db->qn('id').' = '.$db->q($local_id));
			$db->setQuery($dbquery, 0, 1);
			$stream = $db->loadObject();

			if(empty($stream)) die();
			
			if(empty($Itemid))
			{
				// Try to find an Itemid with the same properties				
				$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'stream', 'option' => 'com_ars'),array('streamid'=>$local_id));
				if(!empty($otherMenuItem)) {
					// Exact match
					$query['Itemid'] = $otherMenuItem->id;
				} else {
					// Try to find an Itemid for the parent category
					$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'category', 'option' => 'com_ars'),array('category'=>$stream->type));
					if(!empty($otherMenuItem))
					{
						$query['Itemid'] = $otherMenuItem->id;
						$segments[] = $stream->alias;
					}
					else
					{
						// Try to find an Itemid for all categories
						$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'all', 'option' => 'com_ars'));
						if(!empty($otherMenuItem)) {
							$query['Itemid'] = $otherMenuItem->id;
							$segments[] = $stream->type;
							$segments[] = $local_id;
						} else {
							$segments[] = 'updates';
							$segments[] = $stream->type;
							$segments[] = $stream->alias;
						}
					}
				}
			}
			else
			{
				// menu item id exists in the query
				if( ($task == 'stream') && ($id == $local_id) ) {
					$query['Itemid'] = $otherMenuItem->id;
				} elseif( ($task == 'category') && ($id == $stream->type) ) {
					$query['Itemid'] = $Itemid;
					$segments[] = $stream->alias;
				} elseif( $task == 'all' ) {
					$query['Itemid'] = $Itemid;
					$segments[] = $stream->type;
					$segments[] = $stream->alias;
				} else {
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
	$my_task = ArsRouterHelper::getAndPop($query, 'task', 'default');
	$Itemid = ArsRouterHelper::getAndPop($query, 'Itemid', null);
	$local_id = ArsRouterHelper::getAndPop($query, 'id', 'components');
	
	// Analyze the current Itemid
	if(!empty($Itemid)) {
		// Get the specified menu
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getItem($Itemid);
		
		// Analyze URL
		$uri = new JURI($menuitem->link);
		$option = $uri->getVar('option');
		// Sanity check
		if($option != 'com_ars')
		{
			$Itemid = null;
		}
		else
		{
			$view = $uri->getVar('view');
			$task = $uri->getVar('task');
			$layout = $uri->getVar('layout');
			$format = $uri->getVar('format','ini');
			$id = $uri->getVar('id',null);
			if(empty($task) && !empty($layout)) $task = $layout;
			if(empty($task)) {
				if($format == 'ini') {
					$task = 'ini';
				} else {
					$task = 'all';
				}
			}
			
			// make sure we can grab the ID specified in menu item options
			if(empty($id)) switch($task)
			{
				case 'category':
					$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
					$id = $params->get('category','components');
					break;
				
				case 'ini':
				case 'stream':
					$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
					$id = $params->get('streamid',0);
					break;
			}
		}
	}
	
	$db = JFactory::getDBO();
	$dbquery = $db->getQuery(true)
		->select(array(
			$db->qn('type'),
			$db->qn('alias'),
		))->from($db->qn('#__ars_updatestreams'))
		->where($db->qn('id').' = '.$db->q($local_id));
	$db->setQuery($dbquery, 0, 1);
	$stream = $db->loadObject();

	if(empty($stream)) die();
	
	if(empty($Itemid))
	{
		// Try to find an Itemid with the same properties				
		$otherMenuItem = ArsRouterHelper::findMenu(array('view'=>'updates','layout'=>'ini', 'option' => 'com_ars'),array('streamid'=>$local_id));
		if(!empty($otherMenuItem)) {
			// Exact match
			$query['Itemid'] = $otherMenuItem->id;
		} else {
			$segments[] = 'updates';
			$segments[] = $stream->type;
			$segments[] = $stream->alias;
		}
	}
	else
	{
		// menu item id exists in the query
		if( ($task == 'ini') && ($id == $local_id) ) {
			$query['Itemid'] = $otherMenuItem->id;
		} else {
			$segments[] = 'updates';
			$segments[] = $stream->type;
			$segments[] = $stream->alias;
		}
	}

	return $segments;
}

function arsParseRoute(&$segments)
{
	$format = JRequest::getCmd('format','html');
	$url = JURI::getInstance()->toString();
	$ext = substr(strtolower($url),-4);
	if($ext == '.raw') $format = 'raw';
	if($ext == '.xml') $format = 'xml';
	if($ext == '.ini') $format = 'ini';

	switch($format)
	{
		case 'feed':
			return arsParseRouteFeed($segments);
			break;

		case 'html':
			$segments = ArsRouterHelper::preconditionSegments($segments);
			return arsParseRouteHtml($segments);
			break;

		case 'raw':
		default:
			$segments = ArsRouterHelper::preconditionSegments($segments);
			return arsParseRouteRaw($segments);
			break;

		case 'xml':
			$segments = ArsRouterHelper::preconditionSegments($segments);
			return arsParseRouteXml($segments);
			break;

		case 'ini':
			$segments = ArsRouterHelper::preconditionSegments($segments);
			return arsParseRouteIni($segments);
			break;

	}
}

function arsParseRouteFeed(&$segments)
{
	$query = array();
	$menus = JMenu::getInstance('site');

	$query['format'] = 'feed';
	$query['view'] = 'browse';

	if(!empty($segments))
	{
		$alias = array_pop($segments);
		$query['view'] = 'category';
		$query['layout'] = 'default';

		$db = JFactory::getDBO();
		$dbquery = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__ars_categories'))
			->where($db->qn('alias').' = '.$db->q($alias))
			->where($db->qn('published').' = '.$db->q('1'));
		$db->setQuery($dbquery);
		$records = $db->loadObjectList();

		if(!empty($records)) {
			$record = array_pop($records);
			$query['id'] = (int)$record->id;
		}
	}

	return $query;
}

function arsParseRouteHtml(&$segments)
{
	$query = array();
	$menus = JMenu::getInstance('site');
	$menu = $menus->getActive();

	if(is_null($menu))
	{
		// No menu. The segments are browse_layout/category_alias/release_alias
		switch(count($segments))
		{
			case 1:
				// Repository view
				$query['view'] = 'browse';
				$query['layout'] = array_pop($segments);
				break;

			case 2:
				// Category view
				$query['view'] = 'category';
				$query['layout'] = null;
				$catalias = array_pop($segments);
				$root = array_pop($segments);

				// Load the category
				$db = JFactory::getDBO();
				$dbquery = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__ars_categories'))
					->where($db->qn('alias').' = '.$db->q($catalias));
				$db->setQuery($dbquery, 0, 1);
				$cat = $db->loadObject();

				if(empty($cat))
				{
					$query['view'] = 'browse';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['id'] = $cat->id;
				}
				break;

			case 3:
				// Release view
				$query['view'] = 'release';
				$query['layout'] = null;
				$relalias = array_pop($segments);
				$catalias = array_pop($segments);
				$root = array_pop($segments);

				// Load the release
				$db = JFactory::getDBO();

				$dbquery = $db->getQuery(true)
					->select(array(
						$db->qn('r').'.*',
						$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
						$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
						$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
						$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
						$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
						$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
						$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
					))
					->from($db->qn('#__ars_releases').' AS '.$db->qn('r'))
					->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
						$db->qn('c').'.'.$db->qn('id').'='.$db->qn('r').'.'.$db->qn('category_id').')')
					->where($db->qn('r').'.'.$db->qn('alias').' = '.$db->q($relalias))
					->where($db->qn('c').'.'.$db->qn('alias').' = '.$db->q($catalias))
				;
				
				$db->setQuery($dbquery, 0, 1);
				$rel = $db->loadObject();

				if(empty($rel))
				{
					$query['view'] = 'browse';
					$query['layout'] = 'repository';
				}
				else
				{
					$query['id'] = $rel->id;
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
		
		if( empty($view) || ($view == 'browse') || ($view == 'browses') )
		{
			switch(count($segments))
			{
				case 1:
					// Category view
					$query['view'] = 'category';
					$catalias = array_pop($segments);
					break;

				case 2:
					// Release view
					$query['view'] = 'release';
					$relalias = array_pop($segments);
					$catalias = array_pop($segments);
					break;

				case 3:
					// Degenerate case :(
					return arsParseRouteRaw($segments);
					break;
			}
		}
		elseif( empty($view) || ($view == 'category') )
		{
			switch(count($segments))
			{
				case 1:
					// Release view
					$query['view'] = 'release';
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
			// Degenerate case :(
			if(count($segments) == 2) return arsParseRouteRaw($segments);

			$query['view'] = 'release';
			$relalias = array_pop($segments);
		}

		$db = JFactory::getDBO();
		if( $relalias && $catalias )
		{
			$dbquery = $db->getQuery(true)
					->select(array(
						$db->qn('r').'.*',
						$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
						$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
						$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
						$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
						$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
						$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
						$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
					))
					->from($db->qn('#__ars_releases').' AS '.$db->qn('r'))
					->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
						$db->qn('c').'.'.$db->qn('id').'='.$db->qn('r').'.'.$db->qn('category_id').')')
					->where($db->qn('r').'.'.$db->qn('alias').' = '.$db->q($relalias))
					->where($db->qn('c').'.'.$db->qn('alias').' = '.$db->q($catalias))
				;
			
			$db->setQuery($dbquery, 0, 1);
			$rel = $db->loadObject();

			if(empty($rel))
			{
				$query['view'] = 'browse';
				$query['layout'] = 'repository';
			}
			else
			{
				$query['id'] = $rel->id;
			}
		}
		elseif( $catalias && is_null($relalias) )
		{
			$db = JFactory::getDBO();
			
			$dbquery = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__ars_categories'))
				->where($db->qn('alias').' = '.$db->q($catalias));
			
			$db->setQuery($dbquery, 0, 1);
			$cat = $db->loadObject();

			if(empty($cat))
			{
				$query['view'] = 'browse';
				$query['layout'] = 'repository';
			}
			else
			{
				$query['id'] = $cat->id;
			}
		}
		else
		{
			$params = is_object($menu->params) ? $menu->params : new JRegistry($menu->params);
			$catid = $params->get('catid',0);
			
			$dbquery = $db->getQuery(true)
				->select(array(
					$db->qn('r').'.*',
					$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
					$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
					$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
					$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
					$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
					$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
					$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
				))
				->from($db->qn('#__ars_releases').' AS '.$db->qn('r'))
				->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
					$db->qn('c').'.'.$db->qn('id').'='.$db->qn('r').'.'.$db->qn('category_id').')')
				->where($db->qn('r').'.'.$db->qn('alias').' = '.$db->q($relalias))
				->where($db->qn('c').'.'.$db->qn('id').' = '.$db->q($catid))
			;
				
			$db->setQuery($dbquery, 0, 1);
			$rel = $db->loadObject();

			if(empty($rel))
			{
				$query['view'] = 'browse';
				$query['layout'] = 'repository';
			}
			else
			{
				$query['id'] = $rel->id;
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
	$query['view'] = 'download';
	$query['format'] = 'raw';

	if(is_null($menu))
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
				$db->qn('i').'.*',
				$db->qn('r').'.'.$db->qn('category_id'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('maturity'),
				$db->qn('r').'.'.$db->qn('alias').' AS '.$db->qn('rel_alias'),
				$db->qn('r').'.'.$db->qn('groups').' AS '.$db->qn('rel_groups'),
				$db->qn('r').'.'.$db->qn('access').' AS '.$db->qn('rel_access'),
				$db->qn('r').'.'.$db->qn('published').' AS '.$db->qn('rel_published'),
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
				$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
				$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
				$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
				$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
				$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
				$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
			))
			->from($db->qn('#__ars_items').' AS '.$db->qn('i'))
			->innerJoin($db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.
				$db->qn('r').'.'.$db->qn('id').'='.$db->qn('i').'.'.$db->qn('release_id').')')
			->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
				$db->qn('c').'.'.$db->qn('id').'='.$db->qn('r').'.'.$db->qn('category_id').')')
			->where($db->qn('i').'.'.$db->qn('alias').' = '.$db->q($itemalias))
			->where($db->qn('r').'.'.$db->qn('alias').' = '.$db->q($relalias))
			->where($db->qn('c').'.'.$db->qn('alias').' = '.$db->q($catalias))
		;
		
		$db->setQuery($dbquery, 0, 1);
		$item = $db->loadObject();

		if(empty($item))
		{
			$query['view'] = 'browse';
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

		if( empty($view) || ($view == 'browse') )
		{
			$itemalias = array_pop($segments);
			$relalias = array_pop($segments);
			$catalias = array_pop($segments);
		}
		elseif($view == 'category')
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
				$db->qn('i').'.*',
				$db->qn('r').'.'.$db->qn('category_id'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('maturity'),
				$db->qn('r').'.'.$db->qn('alias').' AS '.$db->qn('rel_alias'),
				$db->qn('r').'.'.$db->qn('groups').' AS '.$db->qn('rel_groups'),
				$db->qn('r').'.'.$db->qn('access').' AS '.$db->qn('rel_access'),
				$db->qn('r').'.'.$db->qn('published').' AS '.$db->qn('rel_published'),
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
				$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
				$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
				$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
				$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
				$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
				$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
			))
			->from($db->qn('#__ars_items').' AS '.$db->qn('i'))
			->innerJoin($db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.
				$db->qn('r').'.'.$db->qn('id').'='.$db->qn('i').'.'.$db->qn('release_id').')')
			->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
				$db->qn('c').'.'.$db->qn('id').'='.$db->qn('r').'.'.$db->qn('category_id').')')
			->where($db->qn('i').'.'.$db->qn('alias').' = '.$db->q($itemalias))
		;

		if(!empty($relalias)) {
			$dbquery->where($db->qn('r').'.'.$db->qn('alias').' = '.$db->q($relalias));
		}
		if(!empty($relid)) {
			$dbquery->where($db->qn('r').'.'.$db->qn('id').' = '.$db->q($relid));
		}
		if(!empty($catalias)) {
			$dbquery->where($db->qn('c').'.'.$db->qn('alias').' = '.$db->q($catalias));
		}
		if(!empty($catid)) {
			$dbquery->where($db->qn('c').'.'.$db->qn('id').' = '.$db->q($catid));
		}

		$db->setQuery($dbquery, 0, 1);
		$item = $db->loadObject();

		if(empty($item))
		{
			JError::raiseError('404', 'Item not found');
			$query['view'] = 'browse';
			$query['layout'] = 'repository';
		}
		else
		{
			$query['id'] = $item->id;
		}
	}

	//var_dump($query);die();
	return $query;
}

function arsParseRouteXml(&$segments)
{
	$query = array();
	$query['view'] = 'update';
	$query['format'] = 'xml';
	
	$menus = JMenu::getInstance('site');
	$menuitem = $menus->getActive();

	// Analyze the current Itemid
	if(!empty($menuitem)) {
		// Analyze URL
		$uri = new JURI($menuitem->link);
		$option = $uri->getVar('option');
		// Sanity check
		if($option != 'com_ars')
		{
			$Itemid = null;
		}
		else
		{
			$view = $uri->getVar('view');
			$task = $uri->getVar('task');
			$layout = $uri->getVar('layout');
			$format = $uri->getVar('format','ini');
			$id = $uri->getVar('id',null);
			if(empty($task) && !empty($layout)) $task = $layout;
			if(empty($task)) {
				if($format == 'ini') {
					$task = 'ini';
				} else {
					$task = 'all';
				}
			}
			
			// make sure we can grab the ID specified in menu item options
			if(empty($id)) switch($task)
			{
				case 'category':
					$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
					$id = $params->get('category','components');
					break;
				
				case 'ini':
				case 'stream':
					$params = ($menuitem->params instanceof JRegistry) ? $menuitem->params : new JRegistry($menuitem->params);
					$id = $params->get('streamid',0);
					break;
			}
			
			if( ($option == 'com_ars') && ($view == 'update'))
			{
				switch($task)
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
	if($check != 'updates') die();

	$cat = count($segments) ? array_shift($segments) : null;
	$stream = count($segments) ? array_shift($segments) : null;

	if( empty($cat) && empty($stream) )
	{
		return $query;
	} elseif( !empty($cat) && empty($stream) )
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
			->where($db->qn('alias').' = '.$db->q($stream))
			->where($db->qn('type').' = '.$db->q('$cat'));
		$db->setQuery($dbquery, 0, 1);
		$item = $db->loadObject();
		if(empty($item)) die();
		$query['id'] = $item->id;
	}

	return $query;
}

function arsParseRouteIni(&$segments)
{
	$query = array();
	$query['view'] = 'update';
	$query['format'] = 'ini';
	$query['task'] = 'ini';

	$check = array_shift($segments);
	if($check != 'updates') die();

	$cat = count($segments) ? array_shift($segments) : null;
	$stream = count($segments) ? array_shift($segments) : null;

	$query['task'] = 'stream';
	$db = JFactory::getDBO();
	$dbquery = $db->getQuery(true)
		->select('*')
		->from($db->qn('#__ars_updatestreams'))
		->where($db->qn('alias').' = '.$db->q($stream))
		->where($db->qn('type').' = '.$db->q($cat));
	$db->setQuery($dbquery, 0, 1);
	$item = $db->loadObject();
	if(empty($item)) die();
	$query['id'] = $item->id;

	return $query;
}

class ArsRouterHelper
{
	static function getAndPop(&$query, $key, $default = null)
	{
		if(isset($query[$key]))
		{
			$value = $query[$key];
			unset($query[$key]);
			return $value;
		}
		else
		{
			return $default;
		}
	}

	/**
	 * Finds a menu whose query parameters match those in $qoptions
	 * @param array $qoptions The query parameters to look for
	 * @param array $params The menu parameters to look for
	 * @return null|object Null if not found, or the menu item if we did find it
	 */
	static public function findMenu($qoptions = array(), $params = null)
	{
		// Convert $qoptions to an object
		if(empty($qoptions) || !is_array($qoptions)) $qoptions = array();

		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getActive();

		// First check the current menu item (fastest shortcut!)
		if(is_object($menuitem)) {
			if(self::checkMenu($menuitem, $qoptions, $params)) {
				return $menuitem;
			}
		}

		// Find all potential menu items
		$possible_items = array();
		foreach($menus->getMenu() as $item)
		{
			if(self::checkMenu($item, $qoptions, $params)) {
				$possible_items[] = $item;
			}
		}
		
		// If no potential item exists, return null
		if(empty($possible_items)) {
			return null;
		}
		
		// Filter by language, if required
		$app = JFactory::getApplication();
		$langCode = '*';
		if($app->getLanguageFilter()) {
			$lang_filter_plugin = JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);
			if ($lang_filter_params->get('remove_default_prefix')) {
				// Get default site language
				$lg = JFactory::getLanguage();
				$langCode = $lg->getTag();
			}else{
				$langCode = JRequest::getCmd('language', '*');
			}
		} else {
			$langCode = JRequest::getCmd('language', '*');
		}
		
		if($langCode == '*') {
			// No language filtering required, return the first item
			return array_shift($possible_items);
		} else {
			// Filter for exact language or *
			foreach($possible_items as $item) {
				if(in_array($item->language, array($langCode, '*'))) {
					return $item;
				}
			}
		}
		return null;
	}

	/**
	 * Checks if a menu item conforms to the query options and parameters specified
	 *
	 * @param object $menu A menu item
	 * @param array $qoptions The query options to look for
	 * @param array $params The menu parameters to look for
	 * @return bool
	 */
	static public function checkMenu($menu, $qoptions, $params = null)
	{
		$query = $menu->query;
		foreach($qoptions as $key => $value)
		{
			if(is_null($value)) continue;
			if(!isset($query[$key])) return false;
			if($query[$key] != $value) return false;
		}

		if(!is_null($params))
		{
			$menus = JMenu::getInstance('site');
			$check =  $menu->params instanceof JRegistry ? $menu->params : $menus->getParams($menu->id);

			foreach($params as $key => $value)
			{
				if(is_null($value)) continue;
				if( $check->get($key) != $value ) return false;
			}
		}

		return true;
	}

	static public function preconditionSegments($segments)
	{
		$newSegments = array();
		if(!empty($segments)) foreach($segments as $segment)
		{
			if(strstr($segment,':'))
			{
				$segment = str_replace(':','-',$segment);
			}
			if(is_array($segment)) {
				$newSegments[] = implode('-', $segment);
			} else {
				$newSegments[] = $segment;
			}
		}
		return $newSegments;
	}
}