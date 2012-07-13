<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsViewUploads extends FOFViewHtml
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$user = JFactory::getUser();
		$perms = (object)array(
			'create'	=> $user->authorise('core.create', 'com_ars'),
			'edit'		=> $user->authorise('core.edit', 'com_ars'),
			'editstate'	=> $user->authorise('core.edit.state', 'com_ars'),
			'delete'	=> $user->authorise('core.delete', 'com_ars'),
		);
		
		$this->perms = $perms;
	}
	
	protected function onAdd($tpl = null) {
		return $this->onDisplay($tpl);
	}
	
	protected function onDisplay($tpl = null) {
		$this->assign('category',0);
		$this->assign('folder','');
		
		return true;
	}
	
	protected function onCategory(&$tpl) {
		$tpl = 'upload';
		
		$model = $this->getModel();
		$files = $model->getFiles();
		$folders = $model->getFolders();
		$category = $model->getState('category',0);
		$path = $model->getCategoryFolder();
		$folder = $model->getState('folder','');
		if(substr($folder,0,5) == 's3://') {
			$folder = substr($folder,5);
		}
		$parent = $model->getState('parent',null);
		$config = JComponentHelper::getParams('com_media');

		$this->assign('files',$files);
		$this->assign('folders',$folders);
		$this->assign('category',$category);
		$this->assign('path',$path);
		$this->assign('folder',$folder);
		$this->assign('parent',$parent);
		$this->assign('mediaconfig', $config);
		
		if(function_exists('ini_get')) {
			$safe_mode = ini_get('safe_mode');
		} else {
			$safe_mode = true;
		}
		$jconfig = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$temp = $jconfig->get('tmp_path', '');
		} else {
			$temp = $jconfig->getValue('config.tmp_path', '');
		}
		$isWritable = @is_writable($temp) && !$safe_mode;
		$this->assign('chunking', !$isWritable);
		
		$document = JFactory::getDocument();
		$document->addScript('http://code.google.com/intl/en/apis/gears/gears_init.js');
		$document->addScript('http://bp.yahooapis.com/2.4.21/browserplus-min.js');
		
		return true;
	}
}