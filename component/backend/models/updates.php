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
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('element') . ' = ' . $db->q('com_ars'));
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
		// Default response (no update)
		$updateResponse = array(
			'hasUpdate'		=> false,
			'version'		=> '',
			'infoURL'		=> ''
		);

		if (empty($this->extension_id))
		{
			return $updateResponse;
		}

		$db = $this->getDbo();

		// If we are forcing the reload, set the last_check_timestamp to 0
		// and remove cached component update info in order to force a reload
		if ($force)
		{
			// Find the update site Ids
			$updateSiteIds = $this->getUpdateSiteIds();

			if (empty($updateSiteIds))
			{
				return $updateResponse;
			}

			// Set the last_check_timestamp to 0
			$query = $db->getQuery(true)
				->update($db->qn('#__update_sites'))
				->set($db->qn('last_check_timestamp') . ' = ' . $db->q('0'))
				->where($db->qn('update_site_id') .' IN ('.implode(', ', $updateSiteIds).')');
			$db->setQuery($query);
			$db->execute();

			// Remove cached component update info from #__updates
			$query = $db->getQuery(true)
				->delete($db->qn('#__updates'))
				->where($db->qn('update_site_id') .' IN ('.implode(', ', $updateSiteIds).')');
			$db->setQuery($query);
			$db->execute();
		}

		// Use the update cache timeout specified in com_installer
		$comInstallerParams = JComponentHelper::getParams('com_installer', false);
		$timeout = 3600 * $comInstallerParams->get('cachetimeout', '6');

		// Load any updates from the network into the #__updates table
		$this->updater->findUpdates($this->extension_id, $timeout);

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
	 * Refreshes the Joomla! update sites for this extension as needed
	 *
	 * @return  void
	 */
	public function refreshUpdateSites()
	{
		if (empty($this->extension_id))
		{
			return;
		}

		// Create the update site definition we want to store to the database
		$update_site = array(
			'name'		=> 'Akeeba Release System',
			'type'		=> 'extension',
			'location'	=> 'http://cdn.akeebabackup.com/updates/ars.xml',
			'enabled'	=> 1,
			'last_check_timestamp'	=> 0,
			'extra_query'	=> null
		);

		$getUpdates = false;
		$db = $this->getDbo();

		// Get the update sites for our extension
		$updateSiteIds = $this->getUpdateSiteIds();

		if (!count($updateSiteIds))
		{
			// No update sites defined. Create a new one.
			$newSite = (object)$update_site;
			$db->insertObject('#__update_sites', $newSite);

			$id = $db->insertid();

			$updateSiteExtension = (object)array(
				'update_site_id'	=> $id,
				'extension_id'		=> $this->extension_id,
			);
			$db->insertObject('#__update_sites_extensions', $updateSiteExtension);

			$getUpdates = true;
		}
		else
		{
			// Loop through all update sites
			foreach ($updateSiteIds as $id)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' = ' . $db->q($id));
				$db->setQuery($query);
				$aSite = $db->loadObject();

				// Does the name and location match?
				if (($aSite->name == $update_site['name']) && ($aSite->location == $update_site['location']))
				{
					continue;
				}

				$update_site['update_site_id'] = $id;
				$newSite = (object)$update_site;
				$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);

				$getUpdates = true;
			}
		}

		// Reload the update information
		if ($getUpdates)
		{
			$this->getUpdates(true);
		}
	}

	/**
	 * Gets the update site Ids for our extension.
	 *
	 * @return 	mixed	An array of Ids or null if the query failed.
	 */
	private function getUpdateSiteIds()
	{
		// Get the update sites for our extension
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($this->extension_id));
		$db->setQuery($query);
		$updateSiteIds = $db->loadColumn(0);

		return $updateSiteIds;
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