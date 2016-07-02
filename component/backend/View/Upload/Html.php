<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\Upload;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Admin\Model\Upload;
use FOF30\View\View as BaseView;

class Html extends BaseView
{

	/** @var  int  The Category we're managing files for */
	public $category = 0;

	/** @var  string  The current subfolder */
	public $folder = '';

	/** @var  array  A list of the subfolders */
	public $folders = [];

	/** @var  array  A list of the files */
	public $files = [];

	/** @var  string  Relative path to the current folder */
	public $pathToHere = '';

	/** @var  string  Relative path to the parent folder */
	public $parent = '';

	/** @var  \JRegistry  com_media configuration */
	public $mediaConfig = null;

	public function onBeforeMain($tpl = null)
	{
		$this->container->toolbar->renderToolbar();

		$this->layoutTemplate = 'default';
		$this->category = 0;
		$this->folder   = '';

		return true;
	}

	protected function onBeforeCategory($tpl = null)
	{
		$this->container->toolbar->renderToolbar();

		$this->layout = 'default_upload';

		/** @var Upload $model */
		$model    = $this->getModel();
		$files    = $model->getFiles();
		$folders  = $model->getFolders();
		$category = $model->getState('category', 0);
		$path     = $model->getCategoryFolder();
		$folder   = $model->getState('folder', '');

		if (substr($folder, 0, 5) == 's3://')
		{
			$folder = substr($folder, 5);
		}

		$parent = $model->getState('parent', null);

		$config = \JComponentHelper::getParams('com_media');

		$this->files       = $files;
		$this->folders     = $folders;
		$this->category    = $category;
		$this->pathToHere  = $path;
		$this->folder      = $folder;
		$this->parent      = $parent;
		$this->mediaConfig = $config;

		if (function_exists('ini_get'))
		{
			$safe_mode = ini_get('safe_mode');
		}
		else
		{
			$safe_mode = true;
		}

		$jconfig        = \JFactory::getConfig();
		$temp           = $jconfig->get('tmp_path', '');
		$isWritable     = @is_writable($temp) && !$safe_mode;
		$this->chunking = !$isWritable;

		$document = \JFactory::getDocument();

		// TODO What do I need this for?
		$document->addScript('http://bp.yahooapis.com/2.4.21/browserplus-min.js');

		return true;
	}
}