<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsToolbar extends F0FToolbar
{
	protected function getMyViews()
	{
		$views = array(
			'cpanels',
			'categories',
			'releases',
			'items',
			'impjeds'
		);

		return $views;
	}

	public function onBrowse()
	{
		parent::onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::back('COM_ARS_TITLE_CPANELS', 'index.php?option=com_ars');
	}

	public function onImpjeds()
	{
		JToolBarHelper::title(JText::_($this->input->getCmd('option', 'com_foobar')) . ' &ndash; <small>' . JText::_('COM_ARS_TITLE_IMPJEDS') . '</small>', str_replace('com_', '', $this->input->getCmd('option', 'com_foobar')));
		JToolBarHelper::back('COM_ARS_TITLE_CPANELS', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onEnvironmentsBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option', 'com_foobar') . '_TITLE_' . strtoupper($this->input->getCmd('view', 'cpanel'));
		JToolBarHelper::title(JText::_($this->input->getCmd('option', 'com_foobar')) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>', str_replace('com_', '', $this->input->getCmd('option', 'com_foobar')));

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
			$msg = JText::_($this->input->getCmd('option', 'com_foobar') . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList($msg);
		}

		JToolBarHelper::divider();
		JToolBarHelper::back('COM_ARS_TITLE_CPANELS', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onLogsBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option', 'com_foobar') . '_TITLE_' . strtoupper($this->input->getCmd('view', 'cpanel'));
		JToolBarHelper::title(JText::_($this->input->getCmd('option', 'com_foobar')) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>', str_replace('com_', '', $this->input->getCmd('option', 'com_foobar')));

		if ($this->perms->delete)
		{
			$msg = JText::_($this->input->getCmd('option', 'com_foobar') . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList($msg);
		}

		JToolBarHelper::divider();
		JToolBarHelper::back('COM_ARS_TITLE_CPANELS', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onUploads()
	{
		JToolBarHelper::title(JText::_($this->input->getCmd('option', 'com_foobar')) . ' &ndash; <small>' . JText::_('COM_ARS_TITLE_UPLOADS') . '</small>', str_replace('com_', '', $this->input->getCmd('option', 'com_foobar')));
		JToolBarHelper::back('COM_ARS_TITLE_CPANELS', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}

	public function onAutodescsBrowse()
	{
		$this->_onBrowseWithCopy();
	}

	public function onCategoriesBrowse()
	{
		$this->_onBrowseWithCopy();
	}

	public function onReleasesBrowse()
	{
		$this->_onBrowseWithCopy();
	}

	public function onItemsBrowse()
	{
		$this->_onBrowseWithCopy();
	}

	public function _onBrowseWithCopy()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option', 'com_foobar') . '_TITLE_' . strtoupper($this->input->getCmd('view', 'cpanel'));
		JToolBarHelper::title(JText::_($this->input->getCmd('option', 'com_foobar')) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>', str_replace('com_', '', $this->input->getCmd('option', 'com_foobar')));

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
			$msg = JText::_($this->input->getCmd('option', 'com_foobar') . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList($msg);
		}

		JToolBarHelper::divider();
		JToolBarHelper::back('COM_ARS_TITLE_CPANELS', 'index.php?option=com_ars');

		$this->renderSubmenu();
	}
}