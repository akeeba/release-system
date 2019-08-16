<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Logs;

defined('_JEXEC') or die;

use FOF30\View\DataView\Html as BaseView;
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
		$hash = 'ars'.strtolower($this->getName());

		// ...ordering
		$platform        = $this->container->platform;
		$input           = $this->input;
		$this->order     = $platform->getUserStateFromRequest($hash . 'filter_order', 'filter_order', $input, 'id');
		$this->order_Dir = $platform->getUserStateFromRequest($hash . 'filter_order_Dir', 'filter_order_Dir', $input, 'DESC');

		// ...filter state
		$this->filters['itemtext'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_itemtext', 'itemtext', $input);
		$this->filters['usertext'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_usertext', 'usertext', $input);
		$this->filters['referer'] 	 	  = $platform->getUserStateFromRequest($hash . 'filter_referer', 'referer', $input);
		$this->filters['ip'] 	 	  	  = $platform->getUserStateFromRequest($hash . 'filter_ip', 'ip', $input);
		$this->filters['country']	 	  = $platform->getUserStateFromRequest($hash . 'filter_country', 'country', $input);
		$this->filters['authorized']	  = $platform->getUserStateFromRequest($hash . 'filter_authorized', 'authorized', $input);
		$this->filters['version']	 	  = $platform->getUserStateFromRequest($hash . 'filter_version', 'version', $input);
		$this->filters['category']	 	  = $platform->getUserStateFromRequest($hash . 'filter_category', 'category', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'itemtext'    => Text::_('LBL_LOGS_ITEM'),
			'usertext'    => Text::_('LBL_LOGS_USER'),
			'accessed_on' => Text::_('LBL_LOGS_ACCESSED'),
			'referer'     => Text::_('LBL_LOGS_REFERER'),
			'ip'          => Text::_('LBL_LOGS_IP'),
			'country'     => Text::_('LBL_LOGS_COUNTRY'),
			'authorized'  => Text::_('LBL_LOGS_AUTHORIZED'),
		);

		parent::onBeforeBrowse();
	}
}