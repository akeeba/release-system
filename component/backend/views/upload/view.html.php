<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewUpload extends JView
{
	public function  display($tpl = null) {

		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'select.php';

		$task = JRequest::getCmd('task','');
		if($task == 'category')
		{
			$model = $this->getModel();
			$files = $model->getFiles();
			$folders = $model->getFolders();
			$category = $model->getState('category',0);
			$path = $model->getCategoryFolder();
			$folder = $model->getState('folder','');
			$parent = $model->getState('parent',null);
			$config = JComponentHelper::getParams('com_media');

			$this->assign('files',$files);
			$this->assign('folders',$folders);
			$this->assign('category',$category);
			$this->assign('path',$path);
			$this->assign('folder',$folder);
			$this->assign('parent',$parent);
			$this->assign('config', $config);
			$tpl = 'upload';

			$document = JFactory::getDocument();
			$document->addScript('http://code.google.com/intl/en/apis/gears/gears_init.js');
			$document->addScript('http://bp.yahooapis.com/2.4.21/browserplus-min.js');

			require_once JPATH_ROOT.DS.'components'.DS.'com_ars'.DS.'helpers'.DS.'html.php';

			if(function_exists('ini_get')) {
				$safe_mode = ini_get('safe_mode');
			} else {
				$safe_mode = true;
			}
			$jconfig = JFactory::getConfig();
			$temp = $jconfig->getValue('config.tmp_path', '');
			$isWritable = @is_writable($temp) && !$safe_mode;
			$this->assign('chunking', !$isWritable);
		}
		else
		{
			$this->assign('category',0);
			$this->assign('folder','');
			$tpl = null;
		}

		$subtitle_key = 'ARS_TITLE_'.strtoupper(JRequest::getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD').' &ndash; <small>'.JText::_($subtitle_key).'</small>','ars');
		JToolBarHelper::back(version_compare(JVERSION,'1.6.0','ge') ? 'JTOOLBAR_BACK' : 'Back', 'index.php?option='.JRequest::getCmd('option'));
		// Add submenus (those nifty text links below the toolbar!)
		// -- Categories
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=categories';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_CATEGORIES'), $link);
		// -- Releases
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=releases';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_RELEASES'), $link);
		// -- Items
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=items';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_ITEMS'), $link);

		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'includes.php';
		ArsHelperIncludes::includeMedia();
		
		JHTML::_('behavior.mootools');

		parent::display($tpl);
	}
}