<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Admin\Helper\AmazonS3;
use FOF30\Container\Container;
use FOF30\Controller\Controller;

class Upload extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['main', 'category', 'upload', 'delete', 'newFolder'];
	}

	protected function checkUserPrivileges()
	{
		if (!$this->checkACL('core.manage'))
		{
			throw new \RuntimeException(\JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	public function main()
	{
		$this->layout = 'default';

		return $this->display();
	}

	public function category()
	{
		$this->checkUserPrivileges();

		$this->csrfProtection();

		$categoryId = $this->input->getInt('id', 0);
		$folder = $this->input->getString('folder', '');

		/** @var \Akeeba\ReleaseSystem\Admin\Model\Upload $model */
		$model = $this->getModel();

		$model->setState('category', (int)$categoryId);
		$model->setState('folder', $folder);

		$this->layout = $this->input->getCmd('layout', 'default');

		$this->display();
	}

	/**
	 * Handles the file uploads
	 */
	function upload()
	{
		$this->checkUserPrivileges();

		$this->csrfProtection();


		// Get the user
		$user = $this->container->platform->getUser();

		// Get some data from the request
		$categoryId = $this->input->getInt('id', 0);
		$folder = $this->input->getString('folder', '');
		$file = $this->input->files->get('upload', [], 'raw');

		// Check for an upload error
		if (isset($file['error']) && $file['error'] !== 0)
		{
			$url = 'index.php?option=com_ars&view=upload&task=category&id=' . (int)$categoryId
			       . '&folder=' . urlencode($folder)
			       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';

			$this->setRedirect($url, \JText::_('MSG_FILE_NOT_UPLOADED'), 'error');

			return;
		}

		// Get output directory
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Upload $model */
		$model = $this->getModel();

		$model->setState('category', (int)$categoryId);
		$model->setState('folder', $folder);

		$targetDirectory = $model->getCategoryFolder();

		$potentialPrefix = substr($targetDirectory, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';

		if ($useS3)
		{
			// When using S3, we are uploading to the temporary directory so that
			// we can then upload to S3 and remove from our server.
			$jConfig = \JFactory::getConfig();
			$s3Dir = $targetDirectory;
			$targetDirectory = $jConfig->get('tmp_path', '');
		}

		if (empty($targetDirectory) || !\JFolder::exists($targetDirectory))
		{
			throw new \RuntimeException('Output directory not found', 500);
		}

		// Set FTP credentials, if given
		\JLoader::import('joomla.client.helper');
		\JClientHelper::setCredentialsFromRequest('ftp');

		// Make the filename safe
		$file['name'] = \JFile::makeSafe($file['name']);

		if (!isset($file['name']))
		{
			$url = 'index.php?option=com_ars&view=upload&task=category&id=' . (int)$categoryId
			       . '&folder=' . urlencode($folder)
			       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';

			$this->setRedirect($url, \JText::_('MSG_UPLOAD_INVALID_REQUEST'), 'error');

			return;
		}

		// The request is valid
		$err = null;

		\JLoader::import('cms.helper.media');

		$mediaHelper = new \JHelperMedia();
		\JFactory::getLanguage()->load('com_media', JPATH_ADMINISTRATOR);

		if (!$mediaHelper->canUpload($file))
		{
			// The file can't be upload
			$url = 'index.php?option=com_ars&view=upload&task=category&id=' . (int)$categoryId
			       . '&folder=' . urlencode($folder)
			       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';
			$this->setRedirect($url);

			return;
		}

		$filePath = \JPath::clean($targetDirectory . '/' . strtolower($file['name']));

		if (\JFile::exists($filePath))
		{
			// File exists; delete before upload
			\JFile::delete($filePath);
		}

		// ACL check for Joomla! 1.6.x
		if (!$user->authorise('core.create', 'com_media'))
		{
			// File does not exist and user is not authorised to create
			throw new \RuntimeException(\JText::_('MSG_NO_UPLOAD_RIGHT'), 403);
		}

		if (!\JFile::upload($file['tmp_name'], $filePath, false, true))
		{
			throw new \RuntimeException(\JText::_('MSG_FILE_NOT_UPLOADED'), 403);
		}

		if ($useS3)
		{
			$s3 = AmazonS3::getInstance();

			$s3TargetDir = trim(substr($s3Dir, 5), '/');

			if (!empty($s3TargetDir))
			{
				$s3TargetDir .= '/';
			}

			$success = $s3->putObject($filePath, $s3TargetDir . $file['name']);

			if (!@unlink($filePath))
			{
				\JFile::delete($filePath);
			}

			if (!$success)
			{
				$url = 'index.php?option=com_ars&view=Upload&task=category&id=' . (int)$categoryId
				       . '&folder=' . urlencode($this->input->getString('folder'))
				       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';
				$this->setRedirect($url, $s3->getError(), 'error');

				return;
			}
		}

		$url = 'index.php?option=com_ars&view=upload&task=category&id=' . (int)$categoryId
		       . '&folder=' . urlencode($this->input->getString('folder'))
		       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';
		$this->setRedirect($url, \JText::_('MSG_ALL_FILES_UPLOADED'));
	}

	/**
	 * Deletes an existing file
	 */
	public function delete()
	{
		if (!$this->checkACL('core.delete'))
		{
			throw new \RuntimeException(\JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->csrfProtection();

		$categoryId = $this->input->getInt('id', 0);
		$folder = $this->input->getString('folder', '');
		$file = $this->input->getString('file', '');

		/** @var \Akeeba\ReleaseSystem\Admin\Model\Upload $model */
		$model = $this->getModel();

		$model->setState('category', (int)$categoryId);
		$model->setState('folder', $folder);
		$model->setState('file', $file);

		$url = 'index.php?option=com_ars&view=upload&task=category&id=' . (int)$categoryId
		       . '&folder=' . urlencode($this->input->getString('folder'))
		       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';

		try
		{
			$model->delete();

			$this->setRedirect($url, \JText::_('MSG_FILE_DELETED'));
		}
		catch (\Exception $e)
		{
			$this->setRedirect($url, \JText::_('MSG_FILE_NOT_DELETED'), 'error');
		}
	}

	/**
	 * Create a new folder
	 */
	public function newFolder()
	{
		if (!$this->checkACL('core.create'))
		{
			throw new \RuntimeException(\JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->csrfProtection();

		$categoryId = $this->input->getInt('id', 0);
		$folder = $this->input->getString('folder', '');
		$file = $this->input->getString('file', '');

		/** @var \Akeeba\ReleaseSystem\Admin\Model\Upload $model */
		$model = $this->getModel();

		$model->setState('category', (int)$categoryId);
		$model->setState('folder', $folder);
		$model->setState('file', $file);

		$parent = $model->getCategoryFolder();

		$potentialPrefix = substr($parent, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';

		if ($useS3)
		{
			$trimmedParent = $parent;

			if (substr($parent, 0, 5) == 's3://')
			{
				$trimmedParent = substr($parent, 5);
			}

			$newFolder = $trimmedParent . '/' . $file;
			$newFolder = trim($newFolder, '/') . '/';
			$s3 = AmazonS3::getInstance();
			$status = $s3->putObject('$folder$', $newFolder, true);
		}
		else
		{
			\JLoader::import('joomla.filesystem.folder');

			$newFolder = $parent . '/' . \JFolder::makeSafe($file);
			$status = \JFolder::create($newFolder);
		}

		$url = 'index.php?option=com_ars&view=upload&task=category&id=' . (int)$categoryId
		       . '&folder=' . urlencode($this->input->getString('folder'))
		       . '&' . \JFactory::getSession()->getFormToken(true) . '=1';

		if ($status)
		{
			$this->setRedirect($url, \JText::_('MSG_FOLDER_CREATED'));

			return;
		}

		$this->setRedirect($url, JText::_('MSG_FOLDER_NOT_CREATED'), 'error');
	}
}
