<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Dlidlabels;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\LoadAnyTemplate;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelsModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use LoadAnyTemplate;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  1.6
	 */
	public $activeFilters = [];

	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public $filterForm;

	/**
	 * Base64-encoded return URL for the list form and all action URLs
	 *
	 * @var   string
	 * @since 7.0.5
	 */
	public $returnURL;

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  1.6
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  1.6
	 */
	protected $state;

	/** @inheritdoc */
	public function display($tpl = null)
	{
		/** @var DlidlabelsModel $model */
		$model               = $this->getModel();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->document->getWebAssetManager()
			->useScript('com_ars.copy_button');
		Text::script('COM_ARS_DLIDLABELS_LBL_COPIED');
		Text::script('COM_ARS_DLIDLABELS_LBL_COPY_FAIL');

		$this->returnURL = $this->returnURL ?: Uri::current();

		parent::display($tpl);
	}
}