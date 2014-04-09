<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsControllerImpjed extends F0FController
{
	/**
	 * Get all the packages packages of a JoomlaCode FRS repository
	 */
	public function jcpackages()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$project = $this->input->getCmd('project', '');

		if(empty($project))
		{
			$data = array();
		}
		else
		{
			$data = $this->getThisModel()->getPackages($project);
		}

		$json = json_encode($data);
		echo "###$json###";
		JFactory::getApplication()->close();
	}

	/**
	 * Get all the releases of a JoomlaCode FRS pacakge
	 */
	public function jcreleases()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$project = $this->input->getCmd('project', '');
		$package = $this->input->getCmd('package', '');

		if(empty($project) || empty($package))
		{
			$data = array();
		}
		else
		{
			$data = $this->getThisModel()->getReleases($project, $package);
		}

		$json = json_encode($data);
		echo "###$json###";
		JFactory::getApplication()->close();
	}

	/**
	 * Get all the files of a JoomlaCode FRS release
	 */
	public function jcfiles()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$project = $this->input->getCmd('project', '');
		$package = $this->input->getCmd('package', '');
		$release = $this->input->getCmd('release', '');

		if(empty($project) || empty($package) || empty($release))
		{
			$data = array();
		}
		else
		{
			$data = $this->getThisModel()->getFiles($project, $package, $release);
		}

		$json = json_encode($data);
		echo "###$json###";
		JFactory::getApplication()->close();
	}

	/**
	 * Imports a remote file into an ARS release
	 */
	public function import()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$release = $this->input->getInt('release', 0);
		$url = $this->input->getString('url', '');

		if(empty($url) || empty($release))
		{
			$data = false;
		}
		else
		{
			$item = $this->getThisModel()->createArsFile($release, $url);
			$data = (is_numeric($item) && ($item > 0));
		}

		$json = json_encode($data);
		echo "###$json###";
		JFactory::getApplication()->close();
	}
}