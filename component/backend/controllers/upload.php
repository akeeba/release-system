<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerUpload extends JController
{

	/**
	 * Displays the selection box for a category
	 * @param bool $cachable Is this view cacheable?
	 */
	function  display($cachable = false) {
		parent::display($cachable);
	}

	/**
	 * Displays the files inside a category and allows uploading new files
	 */
	function category()
	{
		if(!JRequest::getVar(JUtility::getToken(), false))
		{
			JError::raiseError('403', JText::_('Access Denied'));
		}

		$catid = JRequest::getInt('id',0);
		$folder = JRequest::getString('folder','');
		$model = $this->getModel('Upload','ArsModel');
		$model->setState('category',(int)$catid);
		$model->setState('folder', $folder);

		$document =& JFactory::getDocument();
		$viewType	= $document->getType();
		$viewLayout	= JRequest::getCmd( 'layout', 'default' );

		$view = $this->getView('Upload','html','ArsView');
		$view->setModel($model, true);
		$view->setLayout($viewLayout);
		$view->display();
	}

	/**
	 * Saves a chunk of a file in the temp folder, used internally by the uploader
	 */

	/**
	 * Handles the file uploads
	 */
	function upload()
	{
		// Check the token
		if(!JRequest::getVar(JUtility::getToken(), false))
		{
			JError::raiseError('403', JText::_('Access Denied'));
		}
		
		// Get the user
		$user		= JFactory::getUser();

		// Get some data from the request
		//$folder	= JRequest::getString('folder','');
		$catid		= JRequest::getInt('id',0);
		$folder		= JRequest::getVar('folder', '', '', 'path');
		$file		= JRequest::getVar('Filedata', '', 'files', 'array');
		
		// Get output directory
		$model = $this->getModel('Upload','ArsModel');
		$model->setState('category',(int)$catid);
		$model->setState('folder',$folder);
		$outdir = $model->getCategoryFolder();
		if(empty($outdir) || !JFolder::exists($outdir))
		{
			JError::raiseError(500, 'Output directory not found');
			return;
		}		
		
		// Set FTP credentials, if given
		jimport('joomla.client.helper');
		JClientHelper::setCredentialsFromRequest('ftp');
		
		// Make the filename safe
		$file['name']	= JFile::makeSafe($file['name']);
		
		if (isset($file['name']))
		{
			// The request is valid
			$err = null;
			if(!class_exists('MediaHelper')) {
				require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_media'.DS.'helpers'.DS.'media.php');	
			}
			if (!MediaHelper::canUpload($file, $err))
			{
				// The file can't be upload
				JError::raiseNotice(100, JText::_($err));
				return false;
			}
			
			$filepath = JPath::clean($outdir.DS.$folder.DS.strtolower($file['name']));

			if (JFile::exists($filepath))
			{
				// File exists; delete before upload
				JFile::delete($filepath);
			}
			
			if(version_compare(JVERSION,'1.6.0','ge'))
			{
				// ACL check for Joomla! 1.6.x
				if (!$user->authorise('core.create', 'com_media'))
				{
					// File does not exist and user is not authorised to create
					JError::raiseWarning(403, JText::_('MSG_NO_UPLOAD_RIGHT'));
					return false;
				}
			}

			if (!JFile::upload($file['tmp_name'], $filepath))
			{
				// Error in upload
				JError::raiseWarning(100, JText::_('MSG_FILE_NOT_UPLOADED'));
				return false;
			}
		}
		else
		{
			$this->setRedirect('index.php', JText::_('MSG_UPLOAD_INVALID_REQUEST'), 'error');
			return false;
		}		

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JUtility::getToken(true).'=1';
		$this->setRedirect($url, JText::_('MSG_ALL_FILES_UPLOADED'));
	}


	/**
	 * Deletes an existing file
	 */
	public function delete()
	{
		if(!JRequest::getVar(JUtility::getToken(), false))
		{
			JError::raiseError('403', JText::_('Access Denied'));
		}

		$catid = JRequest::getInt('id',0);
		$folder = JRequest::getString('folder','');
		$file = JRequest::getString('file','');

		$model = $this->getModel('Upload','ArsModel');
		$model->setState('category',(int)$catid);
		$model->setState('folder', $folder);
		$model->setState('file', $file);

		$status = $model->delete();

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JUtility::getToken(true).'=1';
		if($status)
		{
			$this->setRedirect($url, JText::_('MSG_FILE_DELETED'));
		}
		else
		{
			$this->setRedirect($url, JText::_('MSG_FILE_NOT_DELETED'),'error');
		}
	}

	/**
	 * Create a new folder
	 */
	public function newfolder()
	{
		if(!JRequest::getVar(JUtility::getToken(), false))
		{
			JError::raiseError('403', JText::_('Access Denied'));
		}

		$catid = JRequest::getInt('id',0);
		$folder = JRequest::getString('folder','');
		$file = JRequest::getString('file','');

		$model = $this->getModel('Upload','ArsModel');
		$model->setState('category',(int)$catid);
		$model->setState('folder', $folder);
		$model->setState('file', $file);

		jimport('joomla.filesystem.folder');
		$parent = $model->getCategoryFolder();
		$newFolder = $parent.DS.JFolder::makeSafe($file);

		$status = JFolder::create($newFolder);

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JUtility::getToken(true).'=1';
		if($status)
		{
			$this->setRedirect($url, JText::_('MSG_FOLDER_CREATED'));
		}
		else
		{
			$this->setRedirect($url, JText::_('MSG_FOLDER_NOT_CREATED'),'error');
		}
	}

}