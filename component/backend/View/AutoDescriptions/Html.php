<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\AutoDescriptions;

defined('_JEXEC') or die;

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
		parent::onBeforeBrowse();

		$hash = 'ars'.strtolower($this->getName());

		// ...ordering
		$platform        = $this->container->platform;
		$input           = $this->input;
		$this->order     = $platform->getUserStateFromRequest($hash . 'filter_order', 'filter_order', $input, 'id');
		$this->order_Dir = $platform->getUserStateFromRequest($hash . 'filter_order_Dir', 'filter_order_Dir', $input, 'DESC');

		// ...filter state
		$this->filters['title'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_title', 'title', $input);
		$this->filters['published']	 	  = $platform->getUserStateFromRequest($hash . 'filter_published', 'published', $input);
		$this->filters['packname']		  = $platform->getUserStateFromRequest($hash . 'filter_packname', 'packname', $input);
		$this->filters['category']	 	  = $platform->getUserStateFromRequest($hash . 'filter_category', 'category', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'title'     => Text::_('LBL_AUTODESC_TITLE'),
			'published' => Text::_('JPUBLISHED'),
			'packname'  => Text::_('LBL_AUTODESC_PACKNAME'),
			'category'  => Text::_('LBL_AUTODESC_CATEGORY'),
		);
	}
}