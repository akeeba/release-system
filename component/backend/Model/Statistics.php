<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die;

use FOF30\Database\Installer;
use FOF30\Model\Model;
use JUri;

class Statistics extends Model
{
	/**
	 * Make sure the #__akeeba_common table exists or create it from scratch
	 *
	 * @return  $this
	 */
	public function checkAndFixCommonTables()
	{
		$db = $this->container->db;
		$dbInstaller = new Installer($db, JPATH_ADMINISTRATOR . '/components/com_ars/sql/common');
		$dbInstaller->updateSchema();

		return $this;
	}

	/**
	 * Get an existing unique site ID or create a new one
	 *
	 * @return  string
	 */
	public function getSiteId()
	{
		// Can I load a site ID from the database?
		$siteId  = $this->getCommonVariable('stats_siteid', null);

		// Can I load the site Url from the database?
		$siteUrl = $this->getCommonVariable('stats_siteurl', null);

		// No id or the saved URL is not the same as the current one (ie site restored to a new url)?
		// Create a new, random site ID and save it to the database
		if (empty($siteId) || (md5(JUri::base()) != $siteUrl))
		{
			$siteUrl = md5(JUri::base());
			$this->setCommonVariable('stats_siteurl', $siteUrl);

			$randomData = $this->getRandomData(120);
			$siteId = sha1($randomData);

			$this->setCommonVariable('stats_siteid', $siteId);
		}

		return $siteId;
	}

	/**
	 * Send site information to the remove collection service
	 *
	 * @param   bool  $useIframe  Should I use an IFRAME?
	 *
	 * @return  bool
	 */
	public function collectStatistics($useIframe)
	{
		// Do not collect statistics on localhost
		if (
			(strpos(JUri::root(), 'localhost') !== false) ||
			(strpos(JUri::root(), '127.0.0.1') !== false)
		)
		{
			return false;
		}

		// Make sure the common tables are installed
		$this->checkAndFixCommonTables();

		// Make sure there is a site ID set
		$siteId = $this->getSiteId();

		// UsageStats file is missing, no need to continue
		if (!file_exists(JPATH_ROOT . '/administrator/components/com_ars/assets/stats/usagestats.php'))
		{
			return false;
		}

		if (!class_exists('AkeebaUsagestats', false))
		{
			require_once JPATH_ROOT . '/administrator/components/com_ars/assets/stats/usagestats.php';
		}

		// UsageStats file is missing, no need to continue
		if (!class_exists('AkeebaUsagestats'))
		{
			return false;
		}

		$params = \JComponentHelper::getParams('com_ars');

		$lastrun = $this->getCommonVariable('stats_lastrun', 0);

		// Data collection is turned off
		if (!$params->get('stats_enabled', 1))
		{
			return false;
		}

		// It's not time to collect the stats
		if (time() < ($lastrun + 3600 * 24))
		{
			return false;
		}

		$db = $this->container->db;
		$stats = new \AkeebaUsagestats();

		$stats->setSiteId($siteId);

		/** @var Updates $updateModel */
		$updateModel = $this->container->factory->model('Updates');
		$version = $updateModel->getVersion();

		// I can't use list since dev release don't have any dots
		$at_parts = explode('.', $version);
		$at_major = $at_parts[0];
		$at_minor = isset($at_parts[1]) ? $at_parts[1] : '';
		$at_revision = isset($at_parts[2]) ? $at_parts[2] : '';

		list($php_major, $php_minor, $php_revision) = explode('.', phpversion());
		$php_qualifier = strpos($php_revision, '~') !== false ? substr($php_revision, strpos($php_revision, '~')) : '';

		list($cms_major, $cms_minor, $cms_revision) = explode('.', JVERSION);
		list($db_major, $db_minor, $db_revision) = explode('.', $db->getVersion());
		$db_qualifier = strpos($db_revision, '~') !== false ? substr($db_revision, strpos($db_revision, '~')) : '';

		$db_driver = get_class($db);

		if(stripos($db_driver, 'mysql') !== false)
		{
			$stats->setValue('dt', 1);
		}
		elseif(stripos($db_driver, 'sqlsrv') !== false || stripos($db_driver, 'sqlazure'))
		{
			$stats->setValue('dt', 2);
		}
		elseif(stripos($db_driver, 'postgresql') !== false)
		{
			$stats->setValue('dt', 3);
		}
		else
		{
			$stats->setValue('dt', 0);
		}

		$stats->setValue('sw', 11); // software
		$stats->setValue('pro', 0); // pro
		$stats->setValue('sm', $at_major); // software_major
		$stats->setValue('sn', $at_minor); // software_minor
		$stats->setValue('sr', $at_revision); // software_revision
		$stats->setValue('pm', $php_major); // php_major
		$stats->setValue('pn', $php_minor); // php_minor
		$stats->setValue('pr', $php_revision); // php_revision
		$stats->setValue('pq', $php_qualifier); // php_qualifiers
		$stats->setValue('dm', $db_major); // db_major
		$stats->setValue('dn', $db_minor); // db_minor
		$stats->setValue('dr', $db_revision); // db_revision
		$stats->setValue('dq', $db_qualifier); // db_qualifiers
		$stats->setValue('ct', 1); // cms_type
		$stats->setValue('cm', $cms_major); // cms_major
		$stats->setValue('cn', $cms_minor); // cms_minor
		$stats->setValue('cr', $cms_revision); // cms_revision

		// Store the last execution time. We must store it even if we fail since we don't want a failed stats collection
		// to cause the site to stop responding.
		$this->setCommonVariable('stats_lastrun', time());

		$return = $stats->sendInfo($useIframe);

		return $return;
	}

	/**
	 * Load a variable from the common variables table. If it doesn't exist it returns $default
	 *
	 * @param   string  $key      The key to load
	 * @param   mixed   $default  The default value if the key doesn't exist
	 *
	 * @return  mixed  The contents of the key or null if it's not present
	 */
	public function getCommonVariable($key, $default = null)
	{
		$db = $this->container->db;
		$query = $db->getQuery(true)
					->select($db->qn('value'))
					->from($db->qn('#__akeeba_common'))
					->where($db->qn('key') . ' = ' . $db->q($key));

		try
		{
			$db->setQuery($query);
			$result = $db->loadResult();
		}
		catch (\Exception $e)
		{
			$result = $default;
		}

		return $result;
	}

	/**
	 * Set a variable to the common variables table.
	 *
	 * @param   string  $key    The key to save
	 * @param   mixed   $value  The value to save
	 *
	 * @return  void
	 */
	public function setCommonVariable($key, $value)
	{
		$db = $this->container->db;
		$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__akeeba_common'))
					->where($db->qn('key') . ' = ' . $db->q($key));

		try
		{
			$db->setQuery($query);
			$count = $db->loadResult();
		}
		catch (\Exception $e)
		{
			return;
		}

		if (!$count)
		{
			$query = $db->getQuery(true)
						->insert($db->qn('#__akeeba_common'))
						->columns(array($db->qn('key'), $db->qn('value')))
						->values($db->q($key) . ', ' . $db->q($value));
		}
		else
		{
			$query = $db->getQuery(true)
						->update($db->qn('#__akeeba_common'))
						->set($db->qn('value') . ' = ' . $db->q($value))
						->where($db->qn('key') . ' = ' . $db->q($key));
		}

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
		}
	}

	/**
	 *
	 * Returns a cryptographically secure random value.
	 *
	 * @param   int  $bytes  How many bytes to return
	 *
	 * @return  string
	 */
	public function getRandomData($bytes = 32)
	{
		if (extension_loaded('openssl') && (version_compare(PHP_VERSION, '5.3.4') >= 0 || IS_WIN))
		{
			$strong = false;
			$randBytes = openssl_random_pseudo_bytes($bytes, $strong);

			if ($strong)
			{
				return $randBytes;
			}
		}

		if (extension_loaded('mcrypt'))
		{
			return mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
		}

		return $this->genRandomBytes($bytes);
	}

	/**
	 * Generate random bytes. Adapted from Joomla! 3.2.
	 *
	 * @param   integer  $length  Length of the random data to generate
	 *
	 * @return  string  Random binary data
	 */
	public function genRandomBytes($length = 32)
	{
		$length = (int) $length;
		$sslStr = '';

		/*
		 * Collect any entropy available in the system along with a number
		 * of time measurements of operating system randomness.
		 */
		$bitsPerRound = 2;
		$maxTimeMicro = 400;
		$shaHashLength = 20;
		$randomStr = '';
		$total = $length;

		// Check if we can use /dev/urandom.
		$urandom = false;
		$handle = null;

		// This is PHP 5.3.3 and up
		if (function_exists('stream_set_read_buffer') && @is_readable('/dev/urandom'))
		{
			$handle = @fopen('/dev/urandom', 'rb');

			if ($handle)
			{
				$urandom = true;
			}
		}

		while ($length > strlen($randomStr))
		{
			$bytes = ($total > $shaHashLength)? $shaHashLength : $total;
			$total -= $bytes;

			/*
			 * Collect any entropy available from the PHP system and filesystem.
			 * If we have ssl data that isn't strong, we use it once.
			 */
			$entropy = rand() . uniqid(mt_rand(), true) . $sslStr;
			$entropy .= implode('', @fstat(fopen(__FILE__, 'r')));
			$entropy .= memory_get_usage();
			$sslStr = '';

			if ($urandom)
			{
				stream_set_read_buffer($handle, 0);
				$entropy .= @fread($handle, $bytes);
			}
			else
			{
				/*
				 * There is no external source of entropy so we repeat calls
				 * to mt_rand until we are assured there's real randomness in
				 * the result.
				 *
				 * Measure the time that the operations will take on average.
				 */
				$samples = 3;
				$duration = 0;

				for ($pass = 0; $pass < $samples; ++$pass)
				{
					$microStart = microtime(true) * 1000000;
					$hash = sha1(mt_rand(), true);

					for ($count = 0; $count < 50; ++$count)
					{
						$hash = sha1($hash, true);
					}

					$microEnd = microtime(true) * 1000000;
					$entropy .= $microStart . $microEnd;

					if ($microStart >= $microEnd)
					{
						$microEnd += 1000000;
					}

					$duration += $microEnd - $microStart;
				}

				$duration = $duration / $samples;

				/*
				 * Based on the average time, determine the total rounds so that
				 * the total running time is bounded to a reasonable number.
				 */
				$rounds = (int) (($maxTimeMicro / $duration) * 50);

				/*
				 * Take additional measurements. On average we can expect
				 * at least $bitsPerRound bits of entropy from each measurement.
				 */
				$iter = $bytes * (int) ceil(8 / $bitsPerRound);

				for ($pass = 0; $pass < $iter; ++$pass)
				{
					$microStart = microtime(true);
					$hash = sha1(mt_rand(), true);

					for ($count = 0; $count < $rounds; ++$count)
					{
						$hash = sha1($hash, true);
					}

					$entropy .= $microStart . microtime(true);
				}
			}

			$randomStr .= sha1($entropy, true);
		}

		if ($urandom)
		{
			@fclose($handle);
		}

		return substr($randomStr, 0, $length);
	}
}