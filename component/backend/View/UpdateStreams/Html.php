<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\UpdateStreams;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\UpdateStreams;
use FOF40\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text;

class Html extends BaseView
{
	/** @var  string	Order column */
	public $order = 'id';

	/** @var  string Order direction, ASC/DESC */
	public $order_Dir = 'DESC';

	/** @var  array	Sorting order options */
	public $sortFields = [];

	/** @var array Current values of user filters */
	public $filters = [];

	protected function onBeforeBrowse(): void
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
			'name'      => Text::_('LBL_UPDATES_NAME'),
			'type'      => Text::_('LBL_UPDATES_TYPE'),
			'category'  => Text::_('COM_ARS_RELEASES_FIELD_CATEGORY'),
			'element'   => Text::_('LBL_UPDATES_ELEMENT'),
			'published' => Text::_('JPUBLISHED'),
		);
	}
}