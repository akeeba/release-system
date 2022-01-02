<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\DownloadIDLabels;

defined('_JEXEC') or die;

use FOF40\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text;

class Html extends BaseView
{
	/** @var  string	Order column */
	public $order = 'ars_dlidlabel_id';

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
		$this->filters['label'] 	  = $platform->getUserStateFromRequest($hash . 'filter_label', 'label', $input);
		$this->filters['username']	  = $platform->getUserStateFromRequest($hash . 'filter_username', 'username', $input);
		$this->filters['dlid']	 	  = $platform->getUserStateFromRequest($hash . 'filter_dlid', 'dlid', $input);
		$this->filters['access']	  = $platform->getUserStateFromRequest($hash . 'filter_access', 'access', $input);
		$this->filters['enabled']	  = $platform->getUserStateFromRequest($hash . 'filter_enabled', 'enabled', $input);
		$this->filters['primary']	  = $platform->getUserStateFromRequest($hash . 'filter_primary', 'primary', $input);

		// Construct the array of sorting fields
		$this->sortFields = array(
			'ars_dlidlabel_id' => 'ID',
			'label'            => Text::_('COM_ARS_DLIDLABELS_FIELD_LABEL'),
			'username'         => Text::_('JGLOBAL_USERNAME'),
			'dlid'             => Text::_('COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID'),
			'enabled'          => Text::_('JPUBLISHED'),
		);
	}
}