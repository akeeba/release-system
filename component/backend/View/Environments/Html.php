<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Environments;

defined('_JEXEC') or die;

use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text;

class Html extends BaseView
{
	/** @var  string	Order column */
	public $order = 'title';

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
		$this->filters['title'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_title', 'search', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'title' => Text::_('LBL_ENVIRONMENTS_TITLE'),
		);
	}
}