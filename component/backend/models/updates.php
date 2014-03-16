<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * The updates provisioning Model
 */
class ArsModelUpdates extends FOFModel
{
	/** @var JUpdater The Joomla! updater object */
	protected $updater = null;

	/** @var int The extension_id of this component */
	protected $extension_id = 0;

	/** @var string The currently installed version, as reported by the #__extensions table */
	protected $version = 'dev';

	/**
	 * Public constructor. Initialises the protected members as well.
	 *
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Get an instance of the updater
		$this->updater = JUpdater::getInstance();

		// Find the extension ID
		$component = $this->input->getCmd('option', '');
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('element') . ' = ' . $db->q($component));
		$db->setQuery($query);
		$extension = $db->loadObject();

		if (is_object($extension))
		{
			$this->extension_id = $extension->extension_id;
			$data = json_decode($extension->manifest_cache, true);

			if (isset($data['version']))
			{
				$this->version = $data['version'];
			}
			else
			{
				$this->version = 'dev';
			}
		}
	}

	/**
	 * Retrieves the update information of the component, returning an array with the following keys:
	 * hasUpdate		True if an update is available
	 * version			The version of the available update
	 * infoURL			The URL to the download page of the update
	 *
	 * @param   bool  $force  Set to true if you want to forcibly reload the update information
	 *
	 * @return  array  See the method description for more information
	 */
	public function getUpdates($force = false)
	{
		$db = $this->getDbo();

		// If we are forcing the reload, set the last_check_timestamp to 0
		// and remove cached component update info in order to force a reload
		if ($force)
		{
			// Find the update site ID
			$query = $db->getQuery(true)
				->select($db->qn('update_site_id'))
				->from($db->qn('#__update_sites_extensions'))
				->where($db->qn('extension_id') . ' = ' . $db->q($this->extension_id));
			$db->setQuery($query);
			$updateSiteId = $db->loadResult();

			// Set the last_check_timestamp to 0
			$query = $db->getQuery(true)
				->update($db->qn('#__update_sites'))
				->set($db->qn('last_check_timestamp') . ' = ' . $db->q('0'))
				->where($db->qn('update_site_id') . ' = ' . $db->q($updateSiteId));
			$db->setQuery($query);
			$db->execute();

			// Remove cached component update info from #__updates
			$query = $db->getQuery(true)
				->delete($db->qn('#__updates'))
				->where($db->qn('update_site_id') . ' = ' . $db->q($updateSiteId));
			$db->setQuery($query);
			$db->execute();
		}

		// Use the update cache timeout specified in com_installer
		$comInstallerParams = JComponentHelper::getParams('com_installer', false);
		$timeout = 3600 * $comInstallerParams->get('cachetimeout', '6');

		// Load any updates from the network into the #__updates table
		$this->updater->findUpdates($this->extension_id, $timeout);

		// Default response (no update)
		$updateResponse = array(
			'hasUpdate'		=> false,
			'version'		=> '',
			'infoURL'		=> ''
		);

		// Get the update record from the database
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__updates'))
			->where($db->qn('extension_id') . ' = ' . $db->q($this->extension_id));
		$db->setQuery($query);
		$updateRecord = $db->loadObject();

		// If we have an update record in the database return the information found there
		if (is_object($updateRecord))
		{
			$updateResponse = array(
				'hasUpdate'		=> true,
				'version'		=> $updateRecord->version,
				'infoURL'		=> $updateRecord->infourl,
			);
		}

		return $updateResponse;
	}

	/**
	 * Get the currently installed version as reported by the #__extensions table
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Override the currently installed version as reported by the #__extensions table
	 *
	 * @param string $version
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}
}