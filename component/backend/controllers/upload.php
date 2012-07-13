<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

// Load the Amazon S3 helper
require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/amazons3.php';

class ArsControllerUpload extends FOFController
{
	/**
	 * Displays the files inside a category and allows uploading new files
	 */
	function category()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.manage', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		if(!FOFInput::getVar(JFactory::getSession()->getToken(), false, $this->input))
		{
			JError::raiseError('403', JText::_('JGLOBAL_AUTH_ACCESS_DENIED'));
		}

		$catid	= FOFInput::getInt('id', 0, $this->input);
		$folder	= FOFInput::getString('folder', '', $this->input);
		
		$this->getThisModel()
			->category((int)$catid)
			->folder($folder);

		$this->layout = FOFInput::getCmd('layout', 'default');
		
		$this->display();
	}

	/**
	 * Handles the file uploads
	 */
	function upload()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		if(!FOFInput::getVar(JFactory::getSession()->getToken(), false, $this->input))
		{
			JError::raiseError('403', JText::_('JGLOBAL_AUTH_ACCESS_DENIED'));
		}
		
		// Get the user
		$user		= JFactory::getUser();

		// Get some data from the request
		$catid		= FOFInput::getInt('id', 0, $this->input);
		$folder		= FOFInput::getString('folder', '', $this->input);
		$file		= FOFInput::getVar('Filedata', '', $_FILES, 'array');
		
		// Get output directory
		$this->getThisModel()
			->category((int)$catid)
			->folder($folder);
		$outdir = $this->getThisModel()->getCategoryFolder();
		
		$potentialPrefix = substr($outdir,0,5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';
		
		if($useS3) {
			// When using S3, we are uploading to the temporary directory so that
			// we can then upload to S3 and remove from our server.
			$jconfig = JFactory::getConfig();
			$s3dir = $outdir;
			$outdir = $jconfig->get('tmp_path','');
		}
		
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
				require_once(JPATH_ADMINISTRATOR.'/components/com_media/helpers/media.php');	
			}
			if (!MediaHelper::canUpload($file, $err))
			{
				// The file can't be upload
				$lang = JFactory::getLanguage();
				$lang->load('com_media', JPATH_ADMINISTRATOR);
				JError::raiseNotice(100, JText::_($err));
				return false;
			}
			
			$filepath = JPath::clean($outdir.'/'.strtolower($file['name']));

			if (JFile::exists($filepath))
			{
				// File exists; delete before upload
				JFile::delete($filepath);
			}
			
			// ACL check for Joomla! 1.6.x
			if (!$user->authorise('core.create', 'com_media'))
			{
				// File does not exist and user is not authorised to create
				JError::raiseWarning(403, JText::_('MSG_NO_UPLOAD_RIGHT'));
				return false;
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
		
		if($useS3) {
			$s3 = ArsHelperAmazons3::getInstance();
			
			$s3targetdir = trim(substr($s3dir,5),'/');
			if(!empty($s3targetdir)) $s3targetdir .= '/';
			
			$input = $s3->inputFile($filepath);
			$success = $s3->putObject($input, '', $s3targetdir.$file['name']);
			if(!@unlink($filepath)) {
				JFile::delete($filepath);
			}
			if(!$success) {
				$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
					.'&folder='.urlencode(JRequest::getString('folder'))
					.'&'.JFactory::getSession()->getToken(true).'=1';
				$this->setRedirect($url, $s3->getError(), 'error');
				return false;
			}
		}

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JFactory::getSession()->getToken(true).'=1';
		$this->setRedirect($url, JText::_('MSG_ALL_FILES_UPLOADED'));
	}


	/**
	 * Deletes an existing file
	 */
	public function delete()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.delete', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		if(!FOFInput::getVar(JFactory::getSession()->getToken(), false, $this->input))
		{
			JError::raiseError('403', JText::_('JGLOBAL_AUTH_ACCESS_DENIED'));
		}

		$catid	= FOFInput::getInt('id', 0, $this->input);
		$folder	= FOFInput::getString('folder', '', $this->input);
		$file	= FOFInput::getString('file', '', $this->input);

		$status = $this->getThisModel()
			->category((int)$catid)
			->folder($folder)
			->file($file)
			->delete();

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JFactory::getSession()->getToken(true).'=1';
		
		if($status) {
			$this->setRedirect($url, JText::_('MSG_FILE_DELETED'));
		} else {
			$this->setRedirect($url, JText::_('MSG_FILE_NOT_DELETED'),'error');
		}
	}

	/**
	 * Create a new folder
	 */
	public function newfolder()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		if(!FOFInput::getVar(JFactory::getSession()->getToken(), false, $this->input))
		{
			JError::raiseError('403', JText::_('JGLOBAL_AUTH_ACCESS_DENIED'));
		}

		$catid	= FOFInput::getInt('id', 0, $this->input);
		$folder	= FOFInput::getString('folder', '', $this->input);
		$file	= FOFInput::getString('file', '', $this->input);

		$parent = $this->getThisModel()
			->category((int)$catid)
			->folder($folder)
			->file($file)
			->getCategoryFolder();

		$potentialPrefix = substr($parent, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';
		
		if($useS3) {
			if(substr($parent,0,5) == 's3://') {
				$trimmedParent = substr($parent,5);
			} else {
				$trimmedParent = $parent;
			}
			$newFolder = $trimmedParent.'/'.$file;
			$newFolder = trim($newFolder, '/') . '/';
			$s3 = ArsHelperAmazons3::getInstance();
			$status = $s3->putObject('', '', $newFolder);
		} else {
			jimport('joomla.filesystem.folder');
			
			$newFolder = $parent.'/'.JFolder::makeSafe($file);
			$status = JFolder::create($newFolder);
		}

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JFactory::getSession()->getToken(true).'=1';
		if($status) {
			$this->setRedirect($url, JText::_('MSG_FOLDER_CREATED'));
		} else {
			$this->setRedirect($url, JText::_('MSG_FOLDER_NOT_CREATED'),'error');
		}
	}
}