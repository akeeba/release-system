<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Categories;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Categories;
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

		/** @var Categories $model */
		$model = $this->getModel();

		// ...filter state
		$this->filters['title'] 	 	  = $model->getState('title');
		$this->filters['published']	 	  = $model->getState('published');
		$this->filters['type']			  = $model->getState('type');
		$this->filters['access']	 	  = $model->getState('access');
		$this->filters['language']	 	  = $model->getState('language');

		// Construct the array of sorting fields
		$this->sortFields = array(
			'published' => Text::_('JPUBLISHED'),
		);
	}
}