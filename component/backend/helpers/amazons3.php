<?php
/**
 * Akeeba Engine
 * The modular PHP5 site backup engine
 *
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU GPL version 3 or, at your option, any later version
 * @package   akeebaengine
 * @version   $Id$
 *
 * This file contains the ArsHelperAmazons3 class which allows storing and
 * retrieving files from Amazon's Simple Storage Service (Amazon S3).
 * It is a subset of S3.php, written by Donovan Schonknecht and available
 * at http://undesigned.org.za/2007/10/22/amazon-s3-php-class under a
 * BSD-like license. I have merely removed the parts which weren't useful
 * to ARS and changed the naming.
 *
 * Note for this version: I have added multipart uploads, a feature which
 * wasn't included in the original version of the S3.php. As a result, this
 * file no longer reflects the original author's work and should not be
 * confused with it.
 *
 * Amazon S3 is a trademark of Amazon.com, Inc. or its affiliates.
 */

// Protection against direct access
defined('_JEXEC') or die();

use Akeeba\ARS\Amazon\Aws\Common\Credentials\Credentials;

if (!defined('AKEEBA_CACERT_PEM'))
{
	define('AKEEBA_CACERT_PEM', JPATH_ADMINISTRATOR . '/components/com_ars/assets/cacert.pem');
}

class ArsHelperAmazons3 extends JObject
{
	// ACL flags
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';
	const ACL_BUCKET_OWNER_READ = 'bucket-owner-read';
	const ACL_BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';

	/**
	 * Should I use SSL?
	 *
	 * @var  bool
	 */
	private static $useSSL = true;

	/**
	 * AWS access key
	 *
	 * @var  string
	 */
	private static $accessKey; // AWS Access key

	/**
	 * AWS secret key
	 *
	 * @var  string
	 */
	private static $secretKey; // AWS Secret key

	/**
	 * Which signature method should I use?
	 *
	 * @var  string
	 */
	private static $signatureMethod = 'v4';

	/**
	 * Should I use reduced redundancy storage (RRS)?
	 *
	 * @var  bool
	 */
	private static $rrs = false;

	/**
	 * Which region your bucket is in (required for v4 signature API)
	 *
	 * @var  string
	 */
	private static $region = '';

	/**
	 * Which bucket should I use if none is specified?
	 *
	 * @var  string
	 */
	private static $bucket = null;

	/**
	 * Which ACL should I use if none is specified?
	 *
	 * @var  string
	 */
	private static $acl = 'private'; // Default ACLs to use: private

	/**
	 * Timeout for signed requests, in seconds. Default: 15 minutes.
	 *
	 * @var int
	 */
	private static $timeForSignedRequests = 900;

	private $s3Client = null;

	/**
	 * Public constructor
	 */
	function __construct()
	{
		// Prepare the credentials object
		$amazonCredentials = new Credentials(
			self::$accessKey,
			self::$secretKey
		);

		// Prepare the client options array. See http://docs.aws.amazon.com/aws-sdk-php/guide/latest/configuration.html#client-configuration-options
		$clientOptions = array(
			'credentials' => $amazonCredentials,
			'scheme'      => self::$useSSL ? 'https' : 'http',
			'signature'   => self::$signatureMethod,
			'region'      => self::$region,
		);

		// If SSL is not enabled you must not provide the CA root file.
		if (self::$useSSL)
		{
			$clientOptions['ssl.certificate_authority'] = realpath(__DIR__ . '/../assets/cacert.pem');
		}
		else
		{
			$clientOptions['ssl.certificate_authority'] = false;
		}

		// Create the S3 client instance
		$this->s3Client = \Akeeba\ARS\Amazon\Aws\S3\S3Client::factory($clientOptions);
	}

	/**
	 * Get a static instance of this helper
	 */
	public static function &getInstance()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			if (!class_exists('Akeeba\\ARS\\Amazon\\Aws\\Autoloader'))
			{
				require_once __DIR__ . '/../Amazon/Autoloader.php';
			}

			$component = JComponentHelper::getComponent('com_ars');

			if (!($component->params instanceof JRegistry))
			{
				$params = new JRegistry($component->params);
			}
			else
			{
				$params = $component->params;
			}

			// Set up
			self::$accessKey = $params->get('s3access', '');
			self::$secretKey = $params->get('s3secret', '');
			self::$useSSL = $params->get('s3ssl', 1);
			self::$bucket = $params->get('s3bucket', '');
			self::$region = $params->get('s3region', 'us-east-1');
			self::$signatureMethod = $params->get('s3method', 's3');
			self::$rrs = $params->get('s3rrs', 0);
			self::$acl = $params->get('s3perms', 'private');
			self::$timeForSignedRequests = $params->get('s3time', 900);

			// Remove slashes from the bucket...
			self::$bucket = str_replace('/', '', self::$bucket);

			// Get the instance
			$instance = new ArsHelperAmazons3();
		}

		return $instance;
	}

	/**
	 * Save a file to Amazon S3
	 *
	 * @param   string  $fileOrContent  The absolute filesystem path or, if $rawContent is true, the raw content to save
	 * @param   string  $path           The path in Amazon S3 where the data will be stored
	 * @param   bool    $rawContent     Does the $fileOrContent parameter contain raw data to upload?
	 *
	 * @return  bool  True on success
	 */
	public function putObject($fileOrContent, $path, $rawContent = false)
	{
		$uploadOperation = array(
			'Bucket'       => self::$bucket,
			'Key'          => $path,
			'SourceFile'   => $fileOrContent,
			'ACL'          => self::$acl,
			'StorageClass' => self::$rrs ? 'REDUCED_REDUNDANCY' : 'STANDARD'
		);

		if ($rawContent)
		{
			// Ref: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-s3.html

			unset($uploadOperation['SourceFile']);
			$uploadOperation['Body'] = $fileOrContent;
		}

		try
		{
			$this->s3Client->putObject($uploadOperation);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Get the contents of a file stored on Amazon S3
	 *
	 * @param   string  $path  The path of the file stored on S3
	 *
	 * @return  string|bool  The data returned from S3, false if it failed
	 */
	public function getObject($path)
	{
		try
		{
			$result = $this->s3Client->getObject(array(
				'Bucket'    => self::$bucket,
				'Key'       => $path
			));

			return $result['Body'];
		}
		catch (Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}
	}

	/**
	 * Delete a file from Amazon S3
	 *
	 * @param   string  $path  The path to the Amazon S3 file to delete
	 *
	 * @return  bool  True on success
	 */
	public function deleteObject($path)
	{
		try
		{
			$result = $this->s3Client->deleteObject(array(
				'Bucket'    => self::$bucket,
				'Key'       => $path
			));

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}
	}

	/*
	* Get contents for a bucket
	*
	* If maxKeys is null this method will loop through truncated result sets
	*
	* @param string $bucket Bucket name
	* @param string $prefix Prefix
	* @param string $marker Marker (last file listed)
	* @param string $maxKeys Max keys (maximum number of keys to return)
	* @param string $delimiter Delimiter
	* @param boolean $returnCommonPrefixes Set to true to return CommonPrefixes
	* @return array | false
	*/
	public function getBucket($bucket = null, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false)
	{
		$operation = array(
			'Bucket' => empty($bucket) ? self::$bucket : $bucket
		);


		if ($prefix !== null && $prefix !== '')
		{
			$operation['Prefix'] = $prefix;
		}

		if ($marker !== null && $marker !== '')
		{
			$operation['Marker'] = $marker;
		}

		if ($maxKeys !== null && $maxKeys !== '')
		{
			$operation['MaxKeys'] = $maxKeys;
		}

		if ($delimiter !== null && $delimiter !== '')
		{
			$operation['Delimiter'] = $delimiter;
		}

		try
		{
			$opResult = $this->s3Client->listObjects($operation);
		}
		catch (Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}

		$results = array();
		$nextMarker = null;

		foreach ($opResult->Contents as $c)
		{
			$results[(string)$c['Key']] = array(
				'name' => (string)$c['Key'],
				'time' => strtotime((string)$c['LastModified']),
				'size' => (int)$c['Size'],
				'hash' => substr((string)$c['ETag'], 1, -1)
			);
			$nextMarker = (string)$c['Key'];
		}


		if ($returnCommonPrefixes)
		{
			foreach ($opResult->CommonPrefixes as $c)
			{
				$results[(string)$c['Prefix']] = array('prefix' => (string)$c['Prefix']);
			}
		}

		if (!$opResult->IsTruncated)
		{
			return $results;
		}

		if (isset($opResult->NextMarker))
		{
			$nextMarker = (string)$opResult->NextMarker;
		}

		// Loop through truncated results if maxKeys isn't specified
		if ($maxKeys == null && $nextMarker !== null)
		{
			do
			{
				$operation['Marker'] = $nextMarker;

				try
				{
					$opResult = $this->s3Client->listObjects($operation);
				}
				catch (Exception $e)
				{
					$opResult = false;

					continue;
				}

				foreach ($opResult->Contents as $c)
				{
					$results[(string)$c['Key']] = array(
						'name' => (string)$c['Key'],
						'time' => strtotime((string)$c['LastModified']),
						'size' => (int)$c['Size'],
						'hash' => substr((string)$c['ETag'], 1, -1)
					);
					$nextMarker = (string)$c['Key'];
				}

				if ($returnCommonPrefixes)
				{
					foreach ($opResult->CommonPrefixes as $c)
					{
						$results[(string)$c['Prefix']] = array('prefix' => (string)$c['Prefix']);
					}
				}

				if (isset($operation->NextMarker))
				{
					$nextMarker = (string)$opResult->NextMarker;
				}
			}
			while ($opResult !== false && (string)$opResult->IsTruncated);
		}

		return $results;
	}

	/**
	 * Get a query string authenticated URL
	 *
	 * @param string  $bucket     Bucket name
	 * @param string  $uri        Object URI
	 * @param integer $lifetime   Lifetime in seconds
	 * @param boolean $hostBucket Use the bucket name as the hostname
	 * @param boolean $https      Use HTTPS ($hostBucket should be false for SSL verification)
	 *
	 * @return string
	 */
	public static function getAuthenticatedURL($bucket, $uri, $lifetime = null, $hostBucket = false, $https = false)
	{
		if (empty($bucket))
		{
			$bucket = self::$bucket;
		}
		if (is_null($lifetime))
		{
			$lifetime = self::$timeForSignedRequests;
		}
		$expires = time() + $lifetime;
		$uri = str_replace('%2F', '/', rawurlencode($uri)); // URI should be encoded (thanks Sean O'Dea)
		return sprintf(($https ? 'https' : 'http') . '://%s/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',
			$hostBucket ? $bucket : $bucket . '.s3.amazonaws.com', $uri, self::$accessKey, $expires,
			urlencode(self::__getHash("GET\n\n\n{$expires}\n/{$bucket}/{$uri}")));
	}

}