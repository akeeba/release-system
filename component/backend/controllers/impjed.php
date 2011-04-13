<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'controllers'.DS.'default.php';

class ArsControllerImpjed extends ArsControllerDefault
{
	function  display($cachable = false) {
		parent::display($cachable);
	}
	
	/**
	 * Get all the packages packages of a JoomlaCode FRS repository
	 */
	function jcpackages()
	{
		$project = JRequest::getCmd('project','');

		if(empty($project))
		{
			$data = array();
		}
		else
		{
			$model = $this->getModel('Impjed','ArsModel');
			$data = $model->getPackages($project);
		}

		$basePath = (!$this->isJoomla16) ? $this->_basePath : $this->basePath;
		$view = $this->getView('Impjed', 'raw', 'ArsView', array( 'base_path'=>$basePath));
		$view->setLayout('default');
		$view->assign('data', $data);
		$view->display();
	}

	/**
	 * Get all the releases of a JoomlaCode FRS pacakge
	 */
	function jcreleases()
	{
		$project = JRequest::getCmd('project','');
		$package = JRequest::getCmd('package','');

		if(empty($project) || empty($package))
		{
			$data = array();
		}
		else
		{
			$model = $this->getModel('Impjed','ArsModel');
			$data = $model->getReleases($project, $package);
		}

		$basePath = (!$this->isJoomla16) ? $this->_basePath : $this->basePath;
		$view = $this->getView('Impjed', 'raw', 'ArsView', array( 'base_path'=>$basePath));
		$view->setLayout('default');
		$view->assign('data', $data);
		$view->display();
	}

	/**
	 * Get all the files of a JoomlaCode FRS release
	 */
	function jcfiles()
	{
		$project = JRequest::getCmd('project','');
		$package = JRequest::getCmd('package','');
		$release = JRequest::getCmd('release','');

		if(empty($project) || empty($package) || empty($release))
		{
			$data = array();
		}
		else
		{
			$model = $this->getModel('Impjed','ArsModel');
			$data = $model->getFiles($project, $package, $release);
		}

		$basePath = (!$this->isJoomla16) ? $this->_basePath : $this->basePath;
		$view = $this->getView('Impjed', 'raw', 'ArsView', array( 'base_path'=>$basePath));
		$view->setLayout('default');
		$view->assign('data', $data);
		$view->display();
	}

	/**
	 * Imports a remote file into an ARS release
	 */
	function import()
	{
		$release = JRequest::getInt('release', 0);
		$url = JRequest::getString('url', '');

		if(empty($url) || empty($release))
		{
			$data = false;
		}
		else
		{
			$model = $this->getModel('Impjed','ArsModel');
			$item = $model->createArsFile($release, $url);
			$data = (is_numeric($item) && ($item > 0));
		}

		$basePath = (!$this->isJoomla16) ? $this->_basePath : $this->basePath;
		$view = $this->getView('Impjed', 'raw', 'ArsView', array( 'base_path'=>$basePath));
		$view->setLayout('default');
		$view->assign('data', $data);
		$view->display();
	}
}