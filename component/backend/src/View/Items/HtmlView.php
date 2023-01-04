<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\View\Items;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\ARS\Administrator\Model\ReleasesModel;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;

	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  1.6
	 */
	public $activeFilters = [];

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

	public function display($tpl = null)
	{
		/** @var ReleasesModel $model */
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

		$this->addToolbar();

		if ($this->getLayout() === 'modal')
		{
			$this->document->addScriptOptions(
				'ars.itemsProxyCallback',
				Factory::getApplication()->input->getCmd('function', 'arsSelectItem')
			)
				->getWebAssetManager()
				->useScript('com_ars.item_select');
		}

		parent::display($tpl);
	}

	private function addToolbar()
	{
		$user = Factory::getApplication()->getIdentity();

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(sprintf(Text::_('COM_ARS_TITLE_ITEMS')), 'icon-ars');

		$catId = $this->state->get('filter.category_id');
		$relId = $this->state->get('filter.release_id');

		if (empty($catId) && !empty($relId))
		{
			$release = new ReleaseTable(Factory::getContainer()->get('DatabaseDriver'));

			if ($release->load($relId))
			{
				$catId = $release->category_id;
			}
		}

		$assetName    = 'com_ars' . (empty($catId) ? '' : ('.category.' . $catId));
		$canCreate    = $user->authorise('core.create', $assetName);
		$canDelete    = $user->authorise('core.delete', $assetName);
		$canEdit      = $user->authorise('core.edit', $assetName);
		$canEditState = $user->authorise('core.edit.state', $assetName);

		if ($canCreate)
		{
			ToolbarHelper::addNew('item.add');
		}

		if ($canDelete || $canEditState || $canCreate)
		{
			/** @var DropdownButton $dropdown */
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if ($canEditState)
			{
				$childBar->publish('items.publish')
					->icon('fa fa-check-circle')
					->text('JTOOLBAR_PUBLISH')
					->listCheck(true);

				$childBar->unpublish('items.unpublish')
					->icon('fa fa-times-circle')
					->text('JTOOLBAR_UNPUBLISH')
					->listCheck(true);

				$childBar->checkin('releases.checkin')->listCheck(true);
			}

			if ($canDelete)
			{
				$childBar->delete('items.delete')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}

			if ($canCreate && $canEdit && $canEditState)
			{
				$childBar->popupButton('batch')
					->text('JTOOLBAR_BATCH')
					->selector('collapseModal')
					->listCheck(true);
			}
		}

		ToolbarHelper::back('COM_ARS_DASHBOARD_SHORT', 'index.php?option=com_cpanel&view=cpanel&dashboard=com_ars.ars');
	}
}