<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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
	function save()
	{
		// HTTP headers for no cache etc
		header('Content-type: text/plain; charset=UTF-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		$config = JFactory::getConfig();
		$tempdir = $config->getValue('config.tmp_path','');
		if(empty($tempdir)) die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "No temp directory."}, "id" : "id"}');

		$tempdir = $tempdir.DS.'plupload';
		jimport('joomla.filesystem.folder');

		$maxFileAge = 60 * 60; // Temp file age in seconds

		// 5 minutes execution time
		@set_time_limit(5 * 60);

		// Get parameters
		$chunk = JRequest::getInt('chunk',0);
		$chunks = JRequest::getInt('chunks',0);
		$fileName = JRequest::getString('name','');
		if(empty($fileName)) die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "No filename specified."}, "id" : "id"}');

		// Create target dir
		if(!JFolder::exists($tempdir))
			JFolder::create($tempdir);

		// Sanitize filename
		jimport('joomla.filesystem.file');
		$fileName = JFile::makeSafe($fileName);

		// Remove old temp files
		$files = JFolder::files($tempdir);
		if(!empty($files))
		{
			foreach($files as $file)
			{
				$filePath = $targetDir . DS . $file;

				// Remove temp files if they are older than the max age
				if (preg_match('/\\.tmp$/', $file) && (@filemtime($filePath) < time() - $maxFileAge))
					JFile::delete($filePath);
			}
		}

		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];

		$outFile = $tempdir . DS . $fileName;

		if($chunks != 0)
		{
			// CHUNKED UPLOADS
			if( JFile::exists($outFile) && ($chunk == 0) )
			{
				JFile::delete($outFile);
			}
			
			$out = fopen($outFile, 'ab');

			if($out === false)
			{
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			}

			if (strpos($contentType, "multipart") !== false) {
				if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");
					if($in !== false) {
						while ($buff = fread($in, 4096))
							@fwrite($out, $buff);
					} else
						die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					@fclose($in);

					unlink($_FILES['file']['tmp_name']);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			} else {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in !== false) {
					while ($buff = fread($in, 4096))
						@fwrite($out, $buff);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
				@fclose($in);
			}
		}
		else
		{
			// REGULAR MULTIPART UPLOADS
			if( JFile::exists($outFile) )
			{
				JFile::delete($outFile);
			}

			if (strpos($contentType, "multipart") !== false) {
				if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
					if(!JFile::upload($_FILES['file']['tmp_name'], $outFile))
						die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
				}  else
					die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			} else {
				$buffer = '';
				$in = fopen("php://input", "rb");
				if ($in !== false) {
					while ($buff = fread($in, 4096))
						$buffer .= $buff;
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
				@fclose($in);

				if(!JFile::write($outFile, $buffer))
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			}
		}

		// Open and read contents of temp file
		
		if(JFile::exists($outFile))
		{
			$buffer = JFile::read($outFile);
			JFile::delete($outFile);
		}
		else
		{
			$buffer = '';
		}

		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Read binary input stream and append it to temp file
				$in = fopen($_FILES['file']['tmp_name'], "rb");
				if ($in) {
					while ($buff = fread($in, 4096))
						$buffer .= $buff;
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

				unlink($_FILES['file']['tmp_name']);

				if(!JFile::write($outFile, $buffer))
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
		} else {
			// Read binary input stream and append it to temp file
			$in = fopen("php://input", "rb");

			if ($in) {
				while ($buff = fread($in, 4096))
					$buffer .= $buff;
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

			unlink($_FILES['file']['tmp_name']);

			if(!JFile::write($outFile, $buffer))
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}

		// Return JSON-RPC response
		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
	}

	/**
	 * Finalizes the uploads by moving the files to their final location,
	 * overwitting existing files
	 */
	function finish()
	{
		if(!JRequest::getVar(JUtility::getToken(), false, 'POST'))
		{
			JError::raiseError('403', JText::_('Access Denied'));
		}

		// Get temp path
		$config = JFactory::getConfig();
		$tempdir = $config->getValue('config.tmp_path','');
		if(empty($tempdir))
		{
			JError::raiseError(500, 'Temporary directory not found');
			return;
		}

		$tempdir = $tempdir.DS.'plupload';
		jimport('joomla.filesystem.folder');
		$tempdir = JFolder::makeSafe($tempdir);

		// Get output directory
		$catid = JRequest::getInt('id',0);
		$folder = JRequest::getString('folder','');
		$model = $this->getModel('Upload','ArsModel');
		$model->setState('category',(int)$catid);
		$model->setState('folder',$folder);
		$outdir = $model->getCategoryFolder();
		if(empty($outdir) || !JFolder::exists($outdir))
		{
			JError::raiseError(500, 'Output directory not found');
			return;
		}

		jimport('joomla.filesystem.file');
		$i = 0;
		$done = false;
		while(!$done)
		{
			$tmpname = JRequest::getString("uploader_{$i}_tmpname", null);
			$name = JRequest::getString("uploader_{$i}_name", null);
			$status = JRequest::getString("uploader_{$i}_status", null);

			if(empty($tmpname) || empty($name))
			{
				$done = true;
			}
			else
			{
				if($status == 'done')
				{
					if(JFile::exists($tempdir.DS.$tmpname))
					{
						if(JFile::exists($outdir.DS.$name))
							JFile::delete($outdir.DS.$name);
						JFile::move($tempdir.DS.$tmpname, $outdir.DS.$name);
					}
				}
			}
			$i++;
		}

		$url = 'index.php?option=com_ars&view=upload&task=category&id='.(int)$catid
			.'&folder='.urlencode(JRequest::getString('folder'))
			.'&'.JUtility::getToken(true).'=1';
		$this->setRedirect($url, JText::_('MSG_ALL_FILES_UPLOADED'));
	}

	/**
	 * Returns a new security token for use in forms
	 */
	public function token()
	{
		@ob_end_clean();
		echo '###'.JUtility::getToken(true).'###';
		die();
	}

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