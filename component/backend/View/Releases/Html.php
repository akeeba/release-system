<?php
/**
 *  @package  	AkeebaReleaseSystem
 *  @copyright	Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license   	GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Releases;

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
		$this->filters['category_id'] 	  = $platform->getUserStateFromRequest($hash . 'filter_category_id', 'category_id', $input);
		$this->filters['version']	 	  = $platform->getUserStateFromRequest($hash . 'filter_version', 'version', $input);
		$this->filters['maturity']	 	  = $platform->getUserStateFromRequest($hash . 'filter_maturity', 'maturity', $input);
		$this->filters['access']	 	  = $platform->getUserStateFromRequest($hash . 'filter_access', 'access', $input);
		$this->filters['published']	 	  = $platform->getUserStateFromRequest($hash . 'filter_published', 'published', $input);
		$this->filters['language']	 	  = $platform->getUserStateFromRequest($hash . 'filter_language', 'language', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'category_id' 		=> JText::_('COM_ARS_RELEASES_FIELD_CATEGORY'),
			'version' 			=> JText::_('COM_ARS_RELEASES_FIELD_VERSION'),
			'maturity' 			=> JText::_('COM_ARS_RELEASES_FIELD_MATURITY'),
			'published' 	 	=> JText::_('JPUBLISHED'),
			'hits' 	 			=> JText::_('JGLOBAL_HITS'),
			'language' 			=> JText::_('JFIELD_LANGUAGE_LABEL'),
		);
	}
}