<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Toolbar;

defined('_JEXEC') or die;

use JToolBarHelper;
use JText;

class Toolbar extends \FOF30\Toolbar\Toolbar
{
	/**
	 * Returns the views of the component to be displayed in the toolbar submenu
	 *
	 * @return  array  A list of all views, in the order to be displayed in the toolbar submenu
	 */
	protected function getMyViews()
	{
		$views = array(
			'ControlPanel',
			'Categories',
			'Releases',
			'Items',
			'UpdateStreams',
			'Logs',
		);

		return $views;
	}

	public function onBrowse()
	{
		parent::onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_ars');
	}

	public function onControlPanels()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;

		JToolBarHelper::title(JText::_($option), str_replace('com_', '', $option));

		JToolBarHelper::preferences($option);
	}

	public function onEnvironmentsBrowse()
	{
		$option = $this->container->componentName;

		// Set toolbar title
		$subtitle_key = $option . '_TITLE_ENVIRONMENTS';
		JToolBarHelper::title(JText::_($option) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>', str_replace('com_', '', $option));

		// Add toolbar buttons
		if ($this->perms->create)
		{
			JToolBarHelper::addNew();
		}

		if ($this->perms->edit)
		{
			JToolBarHelper::editList();
		}

		if ($this->perms->create || $this->perms->edit)
		{
			JToolBarHelper::divider();
		}

		if ($this->perms->delete)
		{
			$msg = JText::_($option . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList($msg);
		}

		JToolBarHelper::divider();
		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onLogsBrowse()
	{
		$option = $this->container->componentName;

		// Set toolbar title
		$subtitle_key = $option . '_TITLE_LOGS';
		JToolBarHelper::title(JText::_($option) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>', str_replace('com_', '', $option));

		if ($this->perms->delete)
		{
			$msg = JText::_($option . '_CONFIRM_DELETE');

			JToolBarHelper::deleteList($msg);
		}

		JToolBarHelper::divider();
		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onUploads()
	{
		$option = $this->container->componentName;

		JToolBarHelper::title(JText::_($option) . ' &ndash; <small>' . JText::_('COM_ARS_TITLE_UPLOADS') . '</small>', str_replace('com_', '', $option));
		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onAutoDescriptionsBrowse()
	{
		$this->_onBrowseWithCopy();
	}

	public function onUpdatestreamsBrowse()
	{
		$this->_onBrowseWithCopy();
	}

	public function _onBrowseWithCopy()
	{
		$option = $this->container->componentName;
		$view = $this->container->input->getCmd('view', 'ControlPanel');

		// Set toolbar title
		$subtitle_key = $option . '_TITLE_' . $view;
		JToolBarHelper::title(JText::_($option) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>', str_replace('com_', '', $option));

		// Add toolbar buttons
		if ($this->perms->create)
		{
			JToolBarHelper::addNew();
		}

		if ($this->perms->edit)
		{
			JToolBarHelper::editList();
		}

		if ($this->perms->create || $this->perms->edit)
		{
			JToolBarHelper::divider();
		}

		if ($this->perms->editstate)
		{
			JToolBarHelper::publishList();
			JToolBarHelper::unpublishList();
			JToolBarHelper::divider();
		}

		if ($this->perms->create)
		{
			JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'COM_ARS_COMMON_COPY_LABEL', false);
			JToolBarHelper::divider();
		}

		if ($this->perms->delete)
		{
			$msg = JText::_($option . '_CONFIRM_DELETE');

			JToolBarHelper::deleteList($msg);
		}

		JToolBarHelper::divider();
		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}
}