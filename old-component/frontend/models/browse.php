<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once dirname(__FILE__).'/base.php';

class ArsModelBrowse extends ArsModelBaseFE
{
	/**
	 * Get a listing of all Categories
	 * @return array
	 */
	public function getCategories()
	{
		// Get state variables
		$grouping = $this->getState('grouping','normal');
		$orderby = $this->getState('orderby', 'order');

		$start = 0;
		$limit = 0;

		// Get all published categories
		$catModel = JModel::getInstance('Categories','ArsModel');
		$catModel->reset();
		$catModel->setState('limitstart',$start);
		$catModel->setState('limit',$limit);
		$catModel->setState('published',1);
		$catModel->setState('type','');
		
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$app = JFactory::getApplication();
			if($app->getLanguageFilter()) {
				$catModel->setState('language', JRequest::getCmd('language','*'));
			} else {
				$catModel->setState('language', JRequest::getCmd('language',''));
			}
		}

		// Apply ordering
		switch($orderby)
		{
			case 'alpha':
				$catModel->setState('order','title');
				$catModel->setState('dir','ASC');
				break;

			case 'ralpha':
				$catModel->setState('order','title');
				$catModel->setState('dir','DESC');
				break;

			case 'created':
				$catModel->setState('order','created');
				$catModel->setState('dir','ASC');
				break;

			case 'rcreated':
				$catModel->setState('order','created');
				$catModel->setState('dir','DESC');
				break;

			case 'order':
				$catModel->setState('order','ordering');
				$catModel->setState('dir','ASC');
				break;

		}

		$allCategories = $catModel->getItemList();

		// Filter and return the list
		$list = $this->filterList($allCategories);
		unset($allCategories);

		if($grouping != 'none') {
			$allCategories = $list;
			$list = array('normal' => array(), 'bleedingedge' => array());

			while(!empty($allCategories))
			{
				$cat = array_shift($allCategories);
				$list[$cat->type][] = $cat;
			}
		} else {
			$list = array('all' => $list);
		}

		return $list;
	}

	public function processFeedData($orderby = 'order')
	{
		if(empty($this->itemList)) return;

		$model = JModel::getInstance('Releases','ArsModel');

		if(empty($this->itemList)) return;

		foreach($this->itemList as $sectionname => $section)
		{
			if(!empty($section)) foreach($section as $cat)
			{
				$model->reset();
				$model->setState('category',		$cat->id);
				$model->setState('published',		1);
				$model->setState('limitstart',		0);
				$model->setState('limit',			1);
				switch($orderby)
				{
					case 'alpha':
						$model->setState('order','title');
						$model->setState('dir','ASC');
						break;

					case 'ralpha':
						$model->setState('order','title');
						$model->setState('dir','DESC');
						break;

					case 'created':
						$model->setState('order','created');
						$model->setState('dir','ASC');
						break;

					case 'rcreated':
						$model->setState('order','created');
						$model->setState('dir','DESC');
						break;

					case 'order':
						$model->setState('order','ordering');
						$model->setState('dir','ASC');
						break;

				}
				$model->setState('maturity',		$this->getState('maturity','alpha'));
				if(version_compare(JVERSION, '1.6.0', 'ge')) {
					$app = JFactory::getApplication();
					if($app->getLanguageFilter()) {
						$model->setState('language', JRequest::getCmd('language','*'));
					} else {
						$model->setState('language', JRequest::getCmd('language',''));
					}
				}

				$releases = $model->getItemList();

				if(empty($releases)) {
					$cat->release = null;
				} else {
					$cat->release = array_shift($releases);
				}
			}
		}
	}

	public function processLatest()
	{
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');
		
		$this->processFeedData($params->get('rel_orderby', 'order'));

		if(empty($this->itemList)) return;

		foreach($this->itemList as $sectionname => $section)
		{
			if(!empty($section)) foreach($section as $cat)
			{
				if(empty($cat->release)) {
					$cat->release = (object)array('id'=>null, 'files' => null);
					continue;
				}

				$model = JModel::getInstance('Items','ArsModel');
				$model->reset();

				$orderby = $params->get('items_orderby',	'order');
				switch($orderby)
				{
					case 'alpha':
						$model->setState('order','title');
						$model->setState('dir','ASC');
						break;

					case 'ralpha':
						$model->setState('order','title');
						$model->setState('dir','DESC');
						break;

					case 'created':
						$model->setState('order','created');
						$model->setState('dir','ASC');
						break;

					case 'rcreated':
						$model->setState('order','created');
						$model->setState('dir','DESC');
						break;

					case 'order':
						$model->setState('order','ordering');
						$model->setState('dir','ASC');
						break;

				}
				
				$model->setState('published',		1);
				$model->setState('release',			$cat->release->id);
				$model->setState('limitstart',		0);
				$model->setState('limit',			0);
                
				if(version_compare(JVERSION, '1.6.0', 'ge')) {
					if($app->getLanguageFilter()) {
						$model->setState('language', JRequest::getCmd('language','*'));
					} else {
						$model->setState('language', JRequest::getCmd('language',''));
					}
				}
				$rawlist = $model->getItemList();
				$cat->release->files = $this->filterList($rawlist);
			}
		}
	}
}