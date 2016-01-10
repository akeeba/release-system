<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

defined('_JEXEC') or die;

if (!class_exists('Akeeba\\ARS\\Amazon\\Aws\\Autoloader'))
{
	require_once __DIR__ . '/../Amazon/Autoloader.php';
}

use Akeeba\ARS\Amazon\Aws\Common\Credentials\Credentials;
use Akeeba\ARS\Amazon\Aws\S3\S3Client;

class AmazonS3 extends \JObject
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
			$clientOptions['ssl.certificate_authority'] =
				realpath(JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');
		}
		else
		{
			$clientOptions['ssl.certificate_authority'] = false;
		}

		// Create the S3 client instance
		$this->s3Client = S3Client::factory($clientOptions);
	}

	/**
	 * Get a static instance of this helper
	 */
	public static function &getInstance()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$component = \JComponentHelper::getComponent('com_ars');

			if (!($component->params instanceof \JRegistry))
			{
				$params = new \JRegistry($component->params);
			}
			else
			{
				$params = $component->params;
			}

			// Set up
			self::$accessKey             = $params->get('s3access', '');
			self::$secretKey             = $params->get('s3secret', '');
			self::$useSSL                = $params->get('s3ssl', 1);
			self::$bucket                = $params->get('s3bucket', '');
			self::$region                = $params->get('s3region', 'us-east-1');
			self::$signatureMethod       = $params->get('s3method', 's3');
			self::$rrs                   = $params->get('s3rrs', 0);
			self::$acl                   = $params->get('s3perms', 'private');
			self::$timeForSignedRequests = $params->get('s3time', 900);

			// Remove slashes from the bucket...
			self::$bucket = str_replace('/', '', self::$bucket);

			// Get the instance
			$instance = new AmazonS3();
		}

		return $instance;
	}

	/**
	 * Save a file to Amazon S3
	 *
	 * @param   string $fileOrContent The absolute filesystem path or, if $rawContent is true, the raw content to save
	 * @param   string $path          The path in Amazon S3 where the data will be stored
	 * @param   bool   $rawContent    Does the $fileOrContent parameter contain raw data to upload?
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
	 * @param   string $path The path of the file stored on S3
	 *
	 * @return  string|bool  The data returned from S3, false if it failed
	 */
	public function getObject($path)
	{
		try
		{
			$result = $this->s3Client->getObject(array(
				'Bucket' => self::$bucket,
				'Key'    => $path
			));

			return $result['Body'];
		}
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}
	}

	/**
	 * Delete a file from Amazon S3
	 *
	 * @param   string $path The path to the Amazon S3 file to delete
	 *
	 * @return  bool  True on success
	 */
	public function deleteObject($path)
	{
		try
		{
			$result = $this->s3Client->deleteObject(array(
				'Bucket' => self::$bucket,
				'Key'    => $path
			));

			return true;
		}
		catch (\Exception $e)
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
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}

		$results    = array();
		$nextMarker = null;

		if ((isset($opResult['Contents'])) && !empty($opResult['Contents']))
		{
			foreach ($opResult['Contents'] as $c)
			{
				$results[ (string)$c['Key'] ] = array(
					'name' => (string)$c['Key'],
					'time' => strtotime((string)$c['LastModified']),
					'size' => (int)$c['Size'],
					'hash' => substr((string)$c['ETag'], 1, -1)
				);
				$nextMarker                   = (string)$c['Key'];
			}
		}

		if ($returnCommonPrefixes && isset($opResult['CommonPrefixes']))
		{
			foreach ($opResult['CommonPrefixes'] as $c)
			{
				$results[ (string)$c['Prefix'] ] = array('prefix' => (string)$c['Prefix']);
			}
		}

		if (!$opResult['IsTruncated'])
		{
			return $results;
		}

		if (isset($opResult['NextMarker']))
		{
			$nextMarker = (string)$opResult['NextMarker'];
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
				catch (\Exception $e)
				{
					$opResult = false;

					continue;
				}

				if (isset($opResult['Contents']) && !empty($opResult['Contents']))
				{
					foreach ($opResult['Contents'] as $c)
					{
						$results[ (string)$c['Key'] ] = array(
							'name' => (string)$c['Key'],
							'time' => strtotime((string)$c['LastModified']),
							'size' => (int)$c['Size'],
							'hash' => substr((string)$c['ETag'], 1, -1)
						);
						$nextMarker                   = (string)$c['Key'];
					}
				}

				if ($returnCommonPrefixes && isset($opResult['CommonPrefixes']))
				{
					foreach ($opResult['CommonPrefixes'] as $c)
					{
						$results[ (string)$c['Prefix'] ] = array('prefix' => (string)$c['Prefix']);
					}
				}

				if (isset($opResult['NextMarker']))
				{
					$nextMarker = (string)$opResult['NextMarker'];
				}
			}
			while ($opResult !== false && (string)$opResult['IsTruncated']);
		}

		return $results;
	}

	/**
	 * Get a query string authenticated URL
	 *
	 * @param   string $path Object URI
	 *
	 * @return  string
	 */
	public function getAuthenticatedURL($path)
	{
		// Pre-signed URLs need to use the old S3 signature method. Therefore we need a new S3Client object.

		$amazonCredentials = new Credentials(
			self::$accessKey,
			self::$secretKey
		);

		$clientOptions = array(
			'credentials' => $amazonCredentials,
			'scheme'      => self::$useSSL ? 'https' : 'http',
			'signature'   => 's3',
		);

		if (self::$useSSL)
		{
			$clientOptions['ssl.certificate_authority'] =
				realpath(JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');
		}
		else
		{
			$clientOptions['ssl.certificate_authority'] = false;
		}

		// Create the S3 client instance
		$s3Client = S3Client::factory($clientOptions);

		return $s3Client->getObjectUrl(self::$bucket, $path, '+' . self::$timeForSignedRequests . ' seconds');
	}
}