<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Releases;

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
			'category_id' => Text::_('COM_ARS_RELEASES_FIELD_CATEGORY'),
			'version'     => Text::_('COM_ARS_RELEASES_FIELD_VERSION'),
			'maturity'    => Text::_('COM_ARS_RELEASES_FIELD_MATURITY'),
			'published'   => Text::_('JPUBLISHED'),
			'hits'        => Text::_('JGLOBAL_HITS'),
			'language'    => Text::_('JFIELD_LANGUAGE_LABEL'),
		);
	}
}