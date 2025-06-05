<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\View\Logs;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\ARS\Administrator\Model\LogsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public Form $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  1.6
	 */
	public array $activeFilters = [];

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected array $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  1.6
	 */
	protected Pagination $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  1.6
	 */
	protected Registry $state;

	public function display($tpl = null)
	{
		/** @var LogsModel $model */
		$model               = $this->getModel();
		$this->items         = $model->getItems() ?: [];
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		$model->applyDownloadItemsMeta($this->items);
		$model->applyUserMeta($this->items);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		ToolbarHelper::preferences('com_ars');

		parent::display($tpl);
	}

	private function addToolbar()
	{
		$user    = Factory::getApplication()->getIdentity();
		$toolbar = $this->getToolbarCompat();

		ToolbarHelper::title(sprintf(Text::_('COM_ARS_TITLE_LOGS')), 'icon-ars');

		if ($user->authorise('core.delete', 'com_ars'))
		{
			$toolbar->delete('logs.delete')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		ToolbarHelper::back('COM_ARS_DASHBOARD_SHORT', 'index.php?option=com_cpanel&view=cpanel&dashboard=com_ars.ars');
	}
}
