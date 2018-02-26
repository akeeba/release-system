<?php
/**
 *  @package  	AkeebaReleaseSystem
 *  @copyright	Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license   	GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Items;

defined('_JEXEC') or die;

use FOF30\View\DataView\Html as BaseView;
use JText;

class Html extends BaseView
{
	/** @var  string	Order column */
	public $order = 'id';

	/** @var  string Order direction, ASC/DESC */
	public $order_Dir = 'DESC';

	/** @var  array	Sorting order options */
	public $sortFields = [];

	public $filters = [];

	protected function onBeforeBrowse()
	{
		parent::onBeforeBrowse();

		$hash = 'ars'.strtolower($this->getName());

		// ...ordering
		$platform        = $this->container->platform;
		$input           = $this->input;
		$this->order     = $platform->getUserStateFromRequest($hash . 'filter_order', 'filter_order', $input, 'id');
		$this->order_Dir = $platform->getUserStateFromRequest($hash . 'filter_order_Dir', 'filter_order_Dir', $input, 'DESC');

		// ...filter state
		$this->filters['title'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_title', 'title', $input);
		$this->filters['category'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_category', 'category', $input);
		$this->filters['release'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_release', 'release', $input);
		$this->filters['type'] 	 	  	  = $platform->getUserStateFromRequest($hash . 'filter_type', 'type', $input);
		$this->filters['published']	 	  = $platform->getUserStateFromRequest($hash . 'filter_published', 'published', $input);
		$this->filters['access']	 	  = $platform->getUserStateFromRequest($hash . 'filter_access', 'access', $input);
		$this->filters['language']	 	  = $platform->getUserStateFromRequest($hash . 'filter_language', 'language', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'ordering' 	 		=> JText::_('LBL_VGROUPS_TITLE'),
			'release' 	 		=> JText::_('LBL_ITEMS_RELEASE'),
			'title' 	 		=> JText::_('LBL_VGROUPS_TITLE'),
			'type'	 	 		=> JText::_('LBL_ITEMS_TYPE'),
			'access'	 	 	=> JText::_('JFIELD_ACCESS_LABEL'),
			'published' 	 	=> JText::_('JPUBLISHED'),
			'hits'		 	 	=> JText::_('JGLOBAL_HITS'),
			'language'	 	 	=> JText::_('JFIELD_LANGUAGE_LABEL')
		);
	}
}