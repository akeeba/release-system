<?php

/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

if (!function_exists('fnmatch'))
{

	function fnmatch($pattern, $string)
	{
		return @preg_match(
				'/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'), array('*'	 => '.*', '?'	 => '.?')) . '$/i', $string
		);
	}

}

class ArsTableItem extends F0FTable
{

	/**
	 * Instantiate the table object
	 *
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct($table, $key, &$db)
	{
		parent::__construct('#__ars_items', 'id', $db);

		$this->_columnAlias = array(
			'enabled'		 => 'published',
			'slug'			 => 'alias',
			'created_on'	 => 'created',
			'modified_on'	 => 'modified',
			'locked_on'		 => 'checked_out_time',
			'locked_by'		 => 'checked_out',
		);

		$this->access = 1;

		require_once JPATH_ADMINISTRATOR . '/components/com_ars/helpers/amazons3.php';
	}

	/**
	 * Checks the record for validity
	 *
	 * @return int True if the record is valid
	 */
	function check()
	{
		// If the release is missing, throw an error
		if (!$this->release_id)
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_CATEGORY'));
			return false;
		}

		// Get some useful info
		$db		 = $this->getDBO();
		$query	 = $db->getQuery(true)
			->select(array(
				$db->qn('title'),
				$db->qn('alias')
			))->from($db->qn('#__ars_items'))
			->where($db->qn('release_id') . ' = ' . $db->q($this->release_id));

		if ($this->id)
		{
			$query->where('NOT(' . $db->qn('id') . '=' . $db->q($this->id) . ')');
		}

		$db->setQuery($query);
		$info	 = $db->loadAssocList();
		$titles	 = array();
		$aliases = array();

		foreach ($info as $infoitem)
		{
			$titles[]	 = $infoitem['title'];
			$aliases[]	 = $infoitem['alias'];
		}

		// Let's get automatic item title/description records
		$subquery	 = $db->getQuery(true)
			->select($db->qn('category_id'))
			->from($db->qn('#__ars_releases'))
			->where($db->qn('id') . ' = ' . $db->q($this->release_id));

		$query		 = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__ars_autoitemdesc'))
			->where($db->qn('category') . ' IN (' . $subquery . ')')
			->where('NOT(' . $db->qn('published') . '=' . $db->q(0) . ')');

		$db->setQuery($query);

		$autoitems	 = $db->loadObjectList();
		$auto		 = (object) array('title'			 => '', 'description'	 => '', 'environments'	 => '');

		if (!empty($autoitems))
		{
			$fname = basename((($this->type == 'file') ? $this->filename : $this->url));

			foreach ($autoitems as $autoitem)
			{
				$pattern = $autoitem->packname;

				if (empty($pattern))
				{
					continue;
				}

				if (fnmatch($pattern, $fname))
				{
					$auto = $autoitem;
					break;
				}
			}
		}

		// Added environment ID
		if (!empty($this->environments) && is_array($this->environments))
		{
			// Filter out empty environments
			$temp = array();

			foreach ($this->environments as $eid)
			{
				if ($eid)
				{
					$temp[] = $eid;
				}
			}

			$this->environments = $temp;
		}

		if (empty($this->environments))
		{
			$this->environments = $auto->environments;
		}
		else
		{
			$this->environments = json_encode($this->environments);
		}

		// Check if a title exists
		if (!$this->title)
		{
			// No, try the automatic rule-based title
			$this->title = $auto->title;

			if (!$this->title)
			{
				// No, try to get the filename
				switch ($this->type)
				{
					case 'file':
						if ($this->filename)
						{
							$this->title = basename($this->filename);
						}

						break;

					case 'link':
						if ($this->url)
						{
							$this->title = basename($this->url);
						}

						break;
				}

				if (!$this->title)
				{
					// Aw, no title could be set. Sorry, I've got to throw an error.
					$this->setError(JText::_('ERR_ITEM_NEEDS_TITLE'));

					return false;
				}
			}
		}

		if (in_array($this->title, $titles))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_TITLE_UNIQUE'));

			return false;
		}

		$stripDesc	 = strip_tags($this->description);
		$stripDesc	 = trim($stripDesc);

		if (empty($this->description) || empty($stripDesc))
		{
			$this->description = $auto->description;
		}

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$source = $this->title;

			switch ($this->type)
			{
				case 'file':
					if ($this->filename)
					{
						$source = basename($this->filename);
					}

					break;

				case 'link':
					if ($this->url)
					{
						$source = basename($this->url);
					}

					break;
			}
			$this->alias = str_replace('.', '-', $source);

			// Create a smart alias
			$alias		 = strtolower($source);
			$alias		 = str_replace(' ', '-', $alias);
			$alias		 = str_replace('.', '-', $alias);
			$this->alias = (string) preg_replace('/[^A-Z0-9_-]/i', '', $alias);
		}

		if (!$this->alias)
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_ALIAS'));

			return false;
		}

		if (in_array($this->alias, $aliases))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_ALIAS_UNIQUE'));

			return false;
		}

		// Do we have a type?
		if (!in_array($this->type, array('link', 'file')))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_TYPE'));

			return false;
		}

		// Check for filename or url
		if (($this->type == 'file') && !($this->filename))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_FILENAME'));

			return false;
		}
		elseif (($this->type == 'link') && !($this->url))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_LINK'));

			return false;
		}

		JLoader::import('joomla.filter.filterinput');
		$filter = JFilterInput::getInstance(null, null, 1, 1);

		// Filter the description using a safe HTML filter
		if (!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Fix the groups
		if (is_array($this->groups))
		{
			$this->groups = implode(',', $this->groups);
		}

		// Set the access to registered if there are subscription groups defined
		if (!empty($this->groups) && ($this->access == 1))
		{
			$this->access = 2;
		}

		JLoader::import('joomla.utilities.date');
		$user	 = JFactory::getUser();
		$date	 = new JDate();

		if (!$this->created_by && empty($this->id))
		{
			$this->created_by	 = $user->id;
			$this->created		 = $date->toSql();
		}
		else
		{
			$this->modified_by	 = $user->id;
			$this->modified		 = $date->toSql();
		}

		if (is_null($this->published) || ($this->published == ''))
		{
			$this->published = 0;
		}

		// Apply an update stream, if possible
		if (empty($this->updatestream))
		{
			$db			 = $this->getDBO();

			$subquery	 = $db->getQuery(true)
				->select($db->qn('category_id'))
				->from('#__ars_releases')
				->where($db->qn('id') . ' = ' . $db->q($this->release_id));

			$query		 = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__ars_updatestreams'))
				->where($db->qn('category') . ' IN (' . $subquery . ')');

			$db->setQuery($query);
			$streams	 = $db->loadObjectList();

			if (!empty($streams))
			{
				$fname = basename((($this->type == 'file') ? $this->filename : $this->url));

				foreach ($streams as $stream)
				{
					$pattern = $stream->packname;
					$element = $stream->element;

					if (empty($pattern) && !empty($element))
					{
						$pattern = $element . '*';
					}

					if (empty($pattern))
					{
						continue;
					}

					if (fnmatch($pattern, $fname))
					{
						$this->updatestream = $stream->id;

						break;
					}
				}
			}
		}

		// Check for MD5 and SHA1 existence
		if (empty($this->md5) || empty($this->sha1) || empty($this->filesize))
		{
			if ($this->type == 'file')
			{
				$target		 = null;
				$folder		 = null;
				$filename	 = $this->filename;

				$release = F0FModel::getTmpInstance('Releases', 'ArsModel')
					->getItem($this->release_id);

				if ($release->id)
				{
					$category = F0FModel::getTmpInstance('Categories', 'ArsModel')
						->getItem($release->category_id);

					if ($category->id)
					{
						$folder = $category->directory;
					}
				}

				$url = null;

				if (!empty($folder))
				{
					$potentialPrefix = substr($folder, 0, 5);
					$potentialPrefix = strtolower($potentialPrefix);

					if ($potentialPrefix == 's3://')
					{
						$check	 = substr($folder, 5);
						$s3		 = ArsHelperAmazons3::getInstance();
						$items	 = $s3->getBucket('', $check);

						if (empty($items))
						{
							$folder = null;
							return false;
						}
						else
						{
							// Get a signed URL
							$s3	 = ArsHelperAmazons3::getInstance();
							$url = $s3->getAuthenticatedURL('', rtrim(substr($folder, 5), '/') . '/' . ltrim($filename, '/'));
						}
					}
					else
					{
						JLoader::import('joomla.filesystem.folder');

						if (!JFolder::exists($folder))
						{
							$folder	 = JPATH_ROOT . '/' . $folder;

							if (!JFolder::exists($folder))
							{
								$folder	 = null;
							}
						}

						if (!empty($folder))
						{
							$filename = $folder . '/' . $filename;
						}
					}
				}
			}

			if (!isset($url))
			{
				$url = null;
			}

			if (($this->type == 'link') || !is_null($url))
			{
				if (is_null($url))
				{
					$url	 = $this->url;
				}

				$config	 = JFactory::getConfig();

				if (version_compare(JVERSION, '3.0', 'ge'))
				{
					$target = $config->get('tmp_path') . '/temp.dat';
				}
				else
				{
					$target = $config->getValue('config.tmp_path') . '/temp.dat';
				}

				if (function_exists('curl_exec'))
				{
					// By default, try using cURL
					$process = curl_init($url);
					curl_setopt($process, CURLOPT_HEADER, 0);
					// Pretend we are IE7, so that webservers play nice with us
					curl_setopt($process, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');
					curl_setopt($process, CURLOPT_TIMEOUT, 5);
					curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
					// The @ sign allows the next line to fail if open_basedir is set or if safe mode is enabled
					@curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
					@curl_setopt($process, CURLOPT_MAXREDIRS, 20);
					$data	 = curl_exec($process);

					if ($data !== false)
					{
						JLoader::import('joomla.filesystem.file');
						$result = JFile::write($target, $data);
					}

					curl_close($process);
				}
				else
				{
					// Use Joomla!'s download helper
					JLoader::import('joomla.installer.helper');
					JInstallerHelper::downloadPackage($url, $target);
				}

				$filename = $target;
			}

			if (!empty($filename) && is_file($filename))
			{
				JLoader::import('joomla.filesystem.file');

				if (!JFile::exists($filename))
				{
					$filename = null;
				}
			}

			if (!empty($filename) && is_file($filename))
			{
				if (function_exists('hash_file'))
				{
					if (empty($this->md5))
					{
						$this->md5	 = hash_file('md5', $filename);
					}
					if (empty($this->sha1))
					{
						$this->sha1	 = hash_file('sha1', $filename);
					}
				}
				else
				{
					if (function_exists('md5_file') && empty($this->md5))
					{
						$this->md5 = md5_file($filename);
					}

					if (function_exists('sha1_file') && empty($this->sha1))
					{
						$this->sha1 = sha1_file($filename);
					}
				}

				if (empty($this->filesize))
				{
					$filesize		 = @filesize($filename);

					if ($filesize !== false)
					{
						$this->filesize	 = $filesize;
					}
				}
			}

			if (!empty($filename) && is_file($filename) && ($this->type == 'link'))
			{
				if (!@unlink($filename))
				{
					JFile::delete($filename);
				}
			}
		}

		return true;
	}

	/**
	 * Fires after loading a record, automatically unserialises the environments
	 * field (by default it's JSON-encoded)
	 *
	 * @param object $result The loaded row
	 *
	 * @return bool
	 */
	protected function onAfterLoad(&$result)
	{
		if (is_string($this->environments))
		{
			$this->environments = json_decode($this->environments);
		}

		parent::onAfterLoad($result);

		return $result;
	}

    protected function onBeforeStore($updateNulls)
    {
        // I'm going to save a new record, let's shift all old record by 1 and put this as the first one
        if(!$this->id)
        {
            $this->ordering = 1;

            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                ->update($db->qn('#__ars_items'))
                ->set($db->qn('ordering').' = '.$db->qn('ordering').' + '.$db->q(1));
            $db->setQuery($query)->execute();
        }

        return parent::onBeforeStore($updateNulls);
    }
}