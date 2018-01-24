<?php
/**
 *  @package  	AkeebaReleaseSystem
 *  @copyright	Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license   	GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\UpdateStreams;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\UpdateStreams;
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
		/** @var UpdateStreams $model */
		$model = $this->getModel();
		$model->with(['categoryObject']);

		parent::onBeforeBrowse();

		$hash = 'ars'.strtolower($this->getName());

		// ...ordering
		$platform        = $this->container->platform;
		$input           = $this->input;
		$this->order     = $platform->getUserStateFromRequest($hash . 'filter_order', 'filter_order', $input, 'id');
		$this->order_Dir = $platform->getUserStateFromRequest($hash . 'filter_order_Dir', 'filter_order_Dir', $input, 'DESC');

		// ...filter state
		$this->filters['name'] 	  	= $platform->getUserStateFromRequest($hash . 'filter_name', 'name', $input);
		$this->filters['type'] 	  	= $platform->getUserStateFromRequest($hash . 'filter_type', 'type', $input);
		$this->filters['category'] 	= $platform->getUserStateFromRequest($hash . 'filter_category', 'category', $input);
		$this->filters['element']	= $platform->getUserStateFromRequest($hash . 'filter_element', 'element', $input);
		$this->filters['published']	= $platform->getUserStateFromRequest($hash . 'filter_published', 'published', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'name' 		=> JText::_('LBL_UPDATES_NAME'),
			'type' 		=> JText::_('LBL_UPDATES_TYPE'),
			'category'	=> JText::_('COM_ARS_RELEASES_FIELD_CATEGORY'),
			'element' 	=> JText::_('LBL_UPDATES_ELEMENT'),
			'published'	=> JText::_('JPUBLISHED'),
		);
	}
}